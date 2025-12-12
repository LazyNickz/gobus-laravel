from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
import joblib
import pandas as pd
from pathlib import Path
from typing import Optional

app = FastAPI()

# Use file-relative paths so launching uvicorn from elsewhere still finds files
BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "random_forest_regressor_model.pkl"
SCALER_PATH = BASE_DIR / "scaler.pkl"

# Load model and scaler once at startup with clear errors
try:
    model = joblib.load(MODEL_PATH)
except Exception as e:
    raise RuntimeError(f"Failed to load model at {MODEL_PATH}: {e}")

try:
    scaler = joblib.load(SCALER_PATH)
except Exception:
    scaler = None  # scaler is optional; proceed without scaling

# Determine model expected features once
if hasattr(model, "feature_names_in_"):
    MODEL_FEATURES = list(getattr(model, "feature_names_in_"))
else:
    MODEL_FEATURES = [
        'distance_km', 'avg_speed', 'is_weekend', 'is_holiday',
        'date', 'time', 'days_to_christmas', 'days_to_new_year',
        'route', 'origin', 'destination'
    ]


# Enhanced schema with supply and demand features
class PredictRequest(BaseModel):
    distance_km: float = Field(..., ge=0)
    avg_speed: float = Field(..., gt=0)
    is_weekend: int = Field(..., ge=0, le=1)
    is_holiday: int = Field(..., ge=0, le=1)
    date: str = Field(..., description="YYYY-MM-DD")
    time: str = Field(..., description="HH:MM")
    route: Optional[str] = None
    origin: Optional[str] = None
    destination: Optional[str] = None
    days_to_christmas: Optional[int] = None
    days_to_new_year: Optional[int] = None
    # Supply and demand specific features
    available_seats: Optional[int] = Field(None, ge=0, description="Available seats for the trip")
    total_seats: Optional[int] = Field(None, ge=0, description="Total seats in the vehicle")
    hour_of_day: Optional[int] = Field(None, ge=0, le=23, description="Hour of day (0-23)")
    day_of_week: Optional[int] = Field(None, ge=0, le=6, description="Day of week (0=Monday, 6=Sunday)")
    month: Optional[int] = Field(None, ge=1, le=12, description="Month (1-12)")
    is_peak_hour: Optional[int] = Field(None, ge=0, le=1, description="Is peak travel hour")
    is_peak_day: Optional[int] = Field(None, ge=0, le=1, description="Is peak travel day")
    # keep optional compatibility fields in case controller sends them
    distance: Optional[float] = None
    distance_speed_interaction: Optional[float] = None


def preprocess_input(data: dict):
    """
    Build a single-row DataFrame containing exactly the model's expected columns.
    Use the provided keys from the new schema and compute a few derived features
    only when the MODEL_FEATURES require them.
    Enhanced with supply and demand features.
    """
    def getf(k, default=None):
        v = data.get(k, default)
        if isinstance(v, str) and v.strip() == "":
            return None
        return v

    # Normalize: accept either provided distance_km or distance
    dist_km = getf('distance_km', getf('distance', 0))
    avg_speed = getf('avg_speed', 0)
    is_weekend = int(getf('is_weekend', 0))
    is_holiday = int(getf('is_holiday', 0))
    date_str = getf('date', None)
    time_str = getf('time', None)
    days_to_christmas = getf('days_to_christmas', None)
    days_to_new_year = getf('days_to_new_year', None)
    route = getf('route', None)
    origin = getf('origin', None)
    destination = getf('destination', None)
    
    # Supply and demand features
    available_seats = getf('available_seats', None)
    total_seats = getf('total_seats', None)
    hour_of_day = getf('hour_of_day', None)
    day_of_week = getf('day_of_week', None)
    month = getf('month', None)
    is_peak_hour = getf('is_peak_hour', None)
    is_peak_day = getf('is_peak_day', None)

    # If days_to_* not provided, compute from date or now
    try:
        ref = pd.to_datetime(date_str) if date_str else pd.Timestamp.now()
    except Exception:
        ref = pd.Timestamp.now()
    if days_to_christmas is None:
        days_to_christmas = (pd.Timestamp('2025-12-25') - ref).days
    if days_to_new_year is None:
        days_to_new_year = (pd.Timestamp('2026-01-01') - ref).days

    # Extract time features from time string if not provided
    if hour_of_day is None:
        try:
            if time_str:
                hour_of_day = int(str(time_str).split(':')[0])
            else:
                hour_of_day = ref.hour
        except Exception:
            hour_of_day = 12  # Default to noon

    # Extract day of week from date if not provided
    if day_of_week is None:
        try:
            if date_str:
                day_of_week = ref.weekday()
            else:
                day_of_week = 0  # Default to Monday
        except Exception:
            day_of_week = 0

    # Extract month if not provided
    if month is None:
        try:
            if date_str:
                month = ref.month
            else:
                month = ref.month
        except Exception:
            month = 12  # Default to December

    # Calculate peak hour and peak day if not provided
    if is_peak_hour is None:
        # Peak hours: 7-9 AM, 5-7 PM
        is_peak_hour = 1 if (7 <= hour_of_day <= 9 or 17 <= hour_of_day <= 19) else 0

    if is_peak_day is None:
        # Peak days: Friday, Sunday (end of week travel)
        is_peak_day = 1 if day_of_week in [4, 6] else 0

    # Build row dict with only model features (fallback defaults where sensible)
    row = {}
    for feat in MODEL_FEATURES:
        if feat == 'distance_km':
            row[feat] = float(dist_km or 0)
        elif feat == 'avg_speed':
            row[feat] = float(avg_speed or 0)
        elif feat == 'is_weekend':
            row[feat] = int(is_weekend)
        elif feat == 'is_holiday':
            row[feat] = int(is_holiday)
        elif feat in ('date','travel_date'):
            row[feat] = date_str or ""
        elif feat == 'time':
            row[feat] = time_str or ""
        elif feat in ('days_to_christmas','days_to_new_year'):
            row[feat] = int(days_to_christmas) if feat=='days_to_christmas' else int(days_to_new_year)
        elif feat in ('route','route_id','route_hash'):
            # prefer route string; if route_hash expected compute deterministic hash
            if route:
                if feat == 'route_hash':
                    try:
                        row[feat] = int(abs(hash(str(route))) % 10000)
                    except Exception:
                        row[feat] = 0
                else:
                    row[feat] = route
            else:
                od = None
                if origin and destination:
                    od = f"{origin}-{destination}"
                if od is not None:
                    if feat == 'route_hash':
                        try:
                            row[feat] = int(abs(hash(str(od))) % 10000)
                        except Exception:
                            row[feat] = 0
                    else:
                        row[feat] = od
                else:
                    row[feat] = getf(feat, None)
        elif feat == 'origin':
            row[feat] = origin or ""
        elif feat == 'destination':
            row[feat] = destination or ""
        elif feat == 'distance_speed_interaction':
            # accept precomputed or compute from distance_km and avg_speed
            if getf('distance_speed_interaction') is not None:
                row[feat] = float(getf('distance_speed_interaction'))
            else:
                try:
                    row[feat] = float(dist_km) * float(avg_speed)
                except Exception:
                    row[feat] = 0.0
        elif feat == 'hour_of_day':
            row[feat] = int(hour_of_day)
        elif feat == 'day_of_week':
            row[feat] = int(day_of_week)
        elif feat == 'month':
            row[feat] = int(month)
        elif feat == 'is_peak_hour':
            row[feat] = int(is_peak_hour)
        elif feat == 'is_peak_day':
            row[feat] = int(is_peak_day)
        elif feat == 'available_seats':
            row[feat] = int(available_seats or 20)  # Default 20 seats available
        elif feat == 'total_seats':
            row[feat] = int(total_seats or 40)  # Default 40 total seats
        elif feat == 'seat_occupancy_rate':
            # Calculate occupancy rate: (total - available) / total
            if available_seats is not None and total_seats is not None and total_seats > 0:
                row[feat] = (total_seats - available_seats) / total_seats
            else:
                row[feat] = 0.5  # Default 50% occupancy
        else:
            # generic mapping: use value if provided, else sensible default (0)
            row[feat] = getf(feat, 0)

    # Build DataFrame with exactly the model features (fill missing with zeros where sensible)
    df = pd.DataFrame([row], columns=MODEL_FEATURES)
    df = df.fillna(0)

    # Apply scaler alignment/transform if scaler exists
    if scaler is not None:
        try:
            if hasattr(scaler, 'feature_names_in_'):
                scaler_cols = list(scaler.feature_names_in_)
                X = df.reindex(columns=scaler_cols, fill_value=0).to_numpy()
            else:
                X = df.to_numpy()
            X_scaled = scaler.transform(X)
            out_cols = scaler_cols if hasattr(scaler, 'feature_names_in_') else MODEL_FEATURES
            df_scaled = pd.DataFrame(X_scaled, columns=out_cols)
            return df_scaled.reindex(columns=MODEL_FEATURES, fill_value=0)
        except Exception as e:
            raise RuntimeError(f"Scaler transform failed: {e}")

    return df

@app.post("/predict")
def predict(request: PredictRequest):
    try:
        # request dict already validates required keys per new schema
        data = request.dict()
        df = preprocess_input(data)

        if hasattr(model, 'feature_names_in_'):
            model_cols = list(model.feature_names_in_)
            X_for_model = df.reindex(columns=model_cols, fill_value=0).to_numpy()
        else:
            X_for_model = df.to_numpy()

        prediction = model.predict(X_for_model)
        return {"prediction": float(prediction[0])}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))


class PredictWeekRequest(BaseModel):
    base_request: PredictRequest
    days_ahead: int = Field(default=7, ge=1, le=30, description="Number of days to predict ahead")

@app.post("/predict-week")
def predict_week(request: PredictWeekRequest):
    try:
        base_request = request.base_request
        days_ahead = request.days_ahead
        
        predictions = []
        
        # Parse base date
        base_date = pd.to_datetime(base_request.date)
        
        for i in range(days_ahead):
            # Create request for each day
            day_request = PredictRequest(
                distance_km=base_request.distance_km,
                avg_speed=base_request.avg_speed,
                is_weekend=int((base_date + pd.Timedelta(days=i)).weekday() >= 5),
                is_holiday=base_request.is_holiday,  # You may want to calculate this per day
                date=(base_date + pd.Timedelta(days=i)).strftime('%Y-%m-%d'),
                time=base_request.time,
                route=base_request.route,
                origin=base_request.origin,
                destination=base_request.destination,
                days_to_christmas=base_request.days_to_christmas - i if base_request.days_to_christmas else None,
                days_to_new_year=base_request.days_to_new_year - i if base_request.days_to_new_year else None
            )
            
            # Get prediction for this day
            data = day_request.dict()
            df = preprocess_input(data)

            if hasattr(model, 'feature_names_in_'):
                model_cols = list(model.feature_names_in_)
                X_for_model = df.reindex(columns=model_cols, fill_value=0).to_numpy()
            else:
                X_for_model = df.to_numpy()

            prediction = model.predict(X_for_model)
            
            predictions.append({
                "date": (base_date + pd.Timedelta(days=i)).strftime('%Y-%m-%d'),
                "prediction": float(prediction[0]),
                "day_of_week": (base_date + pd.Timedelta(days=i)).strftime('%A')
            })
        
        return {"predictions": predictions}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))


@app.get("/health")
def health():
    try:
        model_features = None
        scaler_features = None
        if hasattr(model, "feature_names_in_"):
            model_features = list(getattr(model, "feature_names_in_"))
        if scaler is not None and hasattr(scaler, "feature_names_in_"):
            scaler_features = list(getattr(scaler, "feature_names_in_"))
        return {
            "status": "ok",
            "model_loaded": True,
            "scaler_loaded": scaler is not None,
            "model_features": model_features,
            "scaler_features": scaler_features
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


class SupplyDemandPredictRequest(BaseModel):
    base_request: PredictRequest
    seat_availability_scenarios: list = Field(
        default=[5, 10, 15, 20, 25, 30, 35], 
        description="List of available seat counts to test"
    )
    time_scenarios: Optional[list] = Field(
        None, 
        description="List of time strings (HH:MM) to test different departure times"
    )


@app.post("/predict-supply-demand")
def predict_supply_demand(request: SupplyDemandPredictRequest):
    """
    Predict demand for different supply (seat availability) and time scenarios.
    This endpoint helps with supply and demand pricing by showing how demand varies
    with different seat availability and departure times.
    """
    try:
        base_request = request.base_request
        seat_scenarios = request.seat_availability_scenarios
        time_scenarios = request.time_scenarios or [
            "06:00", "08:00", "10:00", "12:00", "14:00", "16:00", "18:00", "20:00"
        ]
        
        results = {
            "base_prediction": None,
            "supply_analysis": [],
            "time_analysis": [],
            "optimal_combinations": []
        }
        
        # First, get base prediction with original request
        base_data = base_request.dict()
        base_df = preprocess_input(base_data)
        
        if hasattr(model, 'feature_names_in_'):
            model_cols = list(model.feature_names_in_)
            X_for_model = base_df.reindex(columns=model_cols, fill_value=0).to_numpy()
        else:
            X_for_model = base_df.to_numpy()
        
        base_prediction = model.predict(X_for_model)
        results["base_prediction"] = float(base_prediction[0])
        
        # Supply Analysis: Test different seat availability levels
        for available_seats in seat_scenarios:
            test_data = base_data.copy()
            test_data["available_seats"] = available_seats
            test_data["total_seats"] = test_data.get("total_seats", 40)
            
            # Calculate occupancy rate
            if test_data["total_seats"] > 0:
                test_data["seat_occupancy_rate"] = (test_data["total_seats"] - available_seats) / test_data["total_seats"]
            
            df = preprocess_input(test_data)
            if hasattr(model, 'feature_names_in_'):
                model_cols = list(model.feature_names_in_)
                X_for_model = df.reindex(columns=model_cols, fill_value=0).to_numpy()
            else:
                X_for_model = df.to_numpy()
            
            prediction = model.predict(X_for_model)
            
            # Determine demand level based on prediction
            demand_level = "low"
            if prediction[0] > base_prediction[0] * 1.2:
                demand_level = "high"
            elif prediction[0] > base_prediction[0] * 1.1:
                demand_level = "medium-high"
            elif prediction[0] < base_prediction[0] * 0.9:
                demand_level = "low"
            else:
                demand_level = "medium"
            
            results["supply_analysis"].append({
                "available_seats": available_seats,
                "total_seats": test_data["total_seats"],
                "occupancy_rate": round(test_data.get("seat_occupancy_rate", 0), 2),
                "predicted_demand": float(prediction[0]),
                "demand_change": float(prediction[0] - base_prediction[0]),
                "demand_change_percent": round(((prediction[0] - base_prediction[0]) / base_prediction[0]) * 100, 1),
                "demand_level": demand_level
            })
        
        # Time Analysis: Test different departure times
        for time_str in time_scenarios:
            test_data = base_data.copy()
            test_data["time"] = time_str
            
            # Extract hour for peak detection
            try:
                hour = int(time_str.split(':')[0])
                test_data["hour_of_day"] = hour
                test_data["is_peak_hour"] = 1 if (7 <= hour <= 9 or 17 <= hour <= 19) else 0
            except Exception:
                pass
            
            df = preprocess_input(test_data)
            if hasattr(model, 'feature_names_in_'):
                model_cols = list(model.feature_names_in_)
                X_for_model = df.reindex(columns=model_cols, fill_value=0).to_numpy()
            else:
                X_for_model = df.to_numpy()
            
            prediction = model.predict(X_for_model)
            
            # Determine demand level based on prediction
            demand_level = "low"
            if prediction[0] > base_prediction[0] * 1.2:
                demand_level = "high"
            elif prediction[0] > base_prediction[0] * 1.1:
                demand_level = "medium-high"
            elif prediction[0] < base_prediction[0] * 0.9:
                demand_level = "low"
            else:
                demand_level = "medium"
            
            results["time_analysis"].append({
                "departure_time": time_str,
                "predicted_demand": float(prediction[0]),
                "demand_change": float(prediction[0] - base_prediction[0]),
                "demand_change_percent": round(((prediction[0] - base_prediction[0]) / base_prediction[0]) * 100, 1),
                "demand_level": demand_level
            })
        
        # Find optimal combinations (lowest demand = best for passengers, highest demand = best for business)
        supply_data = sorted(results["supply_analysis"], key=lambda x: x["predicted_demand"])
        time_data = sorted(results["time_analysis"], key=lambda x: x["predicted_demand"])
        
        results["optimal_combinations"] = {
            "best_for_passengers": {
                "lowest_demand_supply": {
                    "available_seats": supply_data[0]["available_seats"],
                    "predicted_demand": supply_data[0]["predicted_demand"],
                    "demand_level": supply_data[0]["demand_level"]
                },
                "lowest_demand_time": {
                    "departure_time": time_data[0]["departure_time"],
                    "predicted_demand": time_data[0]["predicted_demand"],
                    "demand_level": time_data[0]["demand_level"]
                }
            },
            "best_for_business": {
                "highest_demand_supply": {
                    "available_seats": supply_data[-1]["available_seats"],
                    "predicted_demand": supply_data[-1]["predicted_demand"],
                    "demand_level": supply_data[-1]["demand_level"]
                },
                "highest_demand_time": {
                    "departure_time": time_data[-1]["departure_time"],
                    "predicted_demand": time_data[-1]["predicted_demand"],
                    "demand_level": time_data[-1]["demand_level"]
                }
            }
        }
        

        return results
        
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))


class PeakOffPeakRequest(BaseModel):
    date: str = Field(..., description="YYYY-MM-DD")
    route: Optional[str] = None
    origin: Optional[str] = None
    destination: Optional[str] = None
    include_weekly_pattern: bool = Field(default=True, description="Include weekly pattern analysis")
    include_seasonal_pattern: bool = Field(default=True, description="Include seasonal pattern analysis")


@app.post("/classify-peak-off-peak")
def classify_peak_off_peak(request: PeakOffPeakRequest):
    """
    Classify time periods as peak, off-peak, or normal based on historical patterns.
    This endpoint provides insights for optimal pricing strategies.
    """
    try:
        date_str = request.date
        route = request.route
        origin = request.origin
        destination = request.destination
        
        # Parse the date
        try:
            date_obj = pd.to_datetime(date_str)
        except Exception:
            raise HTTPException(status_code=400, detail="Invalid date format. Use YYYY-MM-DD")
        
        # Analyze different time periods throughout the day
        time_periods = [
            {"time": "06:00", "period": "Early Morning"},
            {"time": "07:00", "period": "Morning Rush"},
            {"time": "08:00", "period": "Morning Rush"},
            {"time": "09:00", "period": "Morning Rush"},
            {"time": "10:00", "period": "Mid-Morning"},
            {"time": "11:00", "period": "Late Morning"},
            {"time": "12:00", "period": "Lunch Time"},
            {"time": "13:00", "period": "Early Afternoon"},
            {"time": "14:00", "period": "Mid-Afternoon"},
            {"time": "15:00", "period": "Late Afternoon"},
            {"time": "16:00", "period": "Evening Rush"},
            {"time": "17:00", "period": "Evening Rush"},
            {"time": "18:00", "period": "Evening Rush"},
            {"time": "19:00", "period": "Evening"},
            {"time": "20:00", "period": "Night"},
            {"time": "21:00", "period": "Late Night"}
        ]
        
        results = {
            "date": date_str,
            "day_of_week": date_obj.strftime('%A'),
            "day_classification": classify_day_type(date_obj),
            "time_analysis": [],
            "overall_recommendation": {},
            "route_specific_insights": {}
        }
        
        # Analyze each time period
        for period_info in time_periods:
            time_str = period_info["time"]
            period_name = period_info["period"]
            
            # Create prediction request for this time
            predict_request = PredictRequest(
                distance_km=240,  # Default Manila-Baguio distance
                avg_speed=60,     # Average speed
                is_weekend=int(date_obj.weekday() >= 5),
                is_holiday=0,     # Would need holiday calendar
                date=date_str,
                time=time_str,
                route=route,
                origin=origin,
                destination=destination
            )
            
            # Get prediction
            test_data = predict_request.dict()
            df = preprocess_input(test_data)
            
            if hasattr(model, 'feature_names_in_'):
                model_cols = list(getattr(model, "feature_names_in_"))
                X_for_model = df.reindex(columns=model_cols, fill_value=0).to_numpy()
            else:
                X_for_model = df.to_numpy()
            
            prediction = model.predict(X_for_model)[0]
            
            # Classify this time period
            time_classification = classify_time_period(time_str, prediction, date_obj.weekday())
            
            results["time_analysis"].append({
                "time": time_str,
                "period_name": period_name,
                "predicted_demand": float(prediction),
                "classification": time_classification["classification"],
                "demand_level": time_classification["demand_level"],
                "pricing_recommendation": time_classification["pricing_recommendation"],
                "factors": time_classification["factors"]
            })
        
        # Generate overall recommendation
        peak_periods = [t for t in results["time_analysis"] if t["classification"] == "peak"]
        off_peak_periods = [t for t in results["time_analysis"] if t["classification"] == "off-peak"]
        normal_periods = [t for t in results["time_analysis"] if t["classification"] == "normal"]
        
        results["overall_recommendation"] = {
            "best_off_peak_times": [t["time"] for t in off_peak_periods[:3]],
            "peak_periods_to_avoid": [t["time"] for t in peak_periods],
            "optimal_pricing_strategy": generate_pricing_strategy(peak_periods, off_peak_periods, normal_periods),
            "revenue_optimization_tips": generate_revenue_tips(peak_periods, off_peak_periods)
        }
        
        # Route-specific insights (if route provided)
        if route or (origin and destination):
            route_name = route or f"{origin}-{destination}"
            results["route_specific_insights"] = {
                "route": route_name,
                "demand_pattern": analyze_route_pattern(results["time_analysis"]),
                "competitive_advantage": generate_competitive_advantage(results["time_analysis"]),
                "customer_recommendations": generate_customer_recommendations(results["time_analysis"])
            }
        
        return results
        
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))


def classify_day_type(date_obj):
    """Classify the day type based on date and patterns"""
    weekday = date_obj.weekday()
    
    if weekday >= 5:  # Weekend
        return "weekend"
    elif weekday in [0, 4]:  # Monday and Friday
        return "peak_weekday"
    elif weekday in [1, 2, 3]:  # Tuesday, Wednesday, Thursday
        return "normal_weekday"
    else:
        return "special_day"


def classify_time_period(time_str, prediction, weekday):
    """Classify a time period based on prediction and patterns"""
    try:
        hour = int(time_str.split(':')[0])
    except:
        hour = 12
    
    # Base classification from historical patterns
    if 7 <= hour <= 9:  # Morning rush
        base_classification = "peak"
    elif 17 <= hour <= 19:  # Evening rush
        base_classification = "peak"
    elif 22 <= hour or hour <= 5:  # Late night/early morning
        base_classification = "off-peak"
    elif 10 <= hour <= 16:  # Mid-day
        base_classification = "normal"
    else:
        base_classification = "normal"
    
    # Adjust based on day of week
    if weekday >= 5:  # Weekend
        if base_classification == "peak":
            base_classification = "normal"  # Rush hours less intense on weekends
    elif weekday == 4:  # Friday
        if base_classification == "normal":
            base_classification = "peak"  # Friday afternoon/evening
    
    # Adjust based on prediction value (relative thresholds)
    if prediction > 0.8:
        demand_level = "high"
        if base_classification != "peak":
            base_classification = "peak"
    elif prediction < 0.3:
        demand_level = "low"
        if base_classification != "off-peak":
            base_classification = "off-peak"
    elif prediction > 0.6:
        demand_level = "medium-high"
    elif prediction < 0.5:
        demand_level = "medium-low"
    else:
        demand_level = "medium"
    
    # Generate pricing recommendation
    pricing_recommendation = generate_pricing_recommendation(base_classification, demand_level)
    
    # Identify contributing factors
    factors = identify_contributing_factors(hour, weekday, prediction)
    
    return {
        "classification": base_classification,
        "demand_level": demand_level,
        "pricing_recommendation": pricing_recommendation,
        "factors": factors
    }


def generate_pricing_recommendation(classification, demand_level):
    """Generate pricing recommendations based on classification and demand level"""
    recommendations = {
        "peak": {
            "high": "Apply premium pricing (15-25% increase) - High demand, limited supply",
            "medium-high": "Apply moderate premium pricing (10-15% increase)",
            "medium": "Apply slight premium pricing (5-10% increase)",
            "medium-low": "Consider premium pricing (5% increase)",
            "low": "Monitor demand - unexpected low demand during peak period"
        },
        "normal": {
            "high": "Standard pricing with slight premium (5-10% increase)",
            "medium-high": "Standard pricing",
            "medium": "Standard pricing",
            "medium-low": "Consider slight discount (5% decrease) to boost demand",
            "low": "Apply discount (10-15% decrease) to attract customers"
        },
        "off-peak": {
            "high": "Monitor - unexpectedly high demand during off-peak",
            "medium-high": "Consider slight premium (5% increase)",
            "medium": "Apply discount (5-10% decrease) to increase demand",
            "medium-low": "Apply significant discount (10-20% decrease)",
            "low": "Apply maximum discount (15-25% decrease) to fill seats"
        }
    }
    
    return recommendations.get(classification, {}).get(demand_level, "Standard pricing")


def identify_contributing_factors(hour, weekday, prediction):
    """Identify factors contributing to demand prediction"""
    factors = []
    
    # Time-based factors
    if 7 <= hour <= 9:
        factors.append("Morning rush hour")
    if 17 <= hour <= 19:
        factors.append("Evening rush hour")
    if 10 <= hour <= 16:
        factors.append("Mid-day period")
    if 22 <= hour or hour <= 5:
        factors.append("Off-peak hours")
    
    # Day-based factors
    if weekday >= 5:
        factors.append("Weekend travel")
    elif weekday == 4:
        factors.append("Friday - end of work week")
    elif weekday == 0:
        factors.append("Monday - start of work week")
    elif weekday in [1, 2, 3]:
        factors.append("Mid-week travel")
    
    # Demand level factors
    if prediction > 0.7:
        factors.append("High predicted demand")
    elif prediction < 0.3:
        factors.append("Low predicted demand")
    
    return factors


def generate_pricing_strategy(peak_periods, off_peak_periods, normal_periods):
    """Generate overall pricing strategy recommendations"""
    return {
        "peak_strategy": "Premium pricing during high-demand periods to maximize revenue",
        "off_peak_strategy": "Discounted pricing to attract customers and improve seat utilization",
        "normal_strategy": "Competitive standard pricing to maintain market position",
        "dynamic_adjustments": "Real-time price adjustments based on seat availability and booking patterns"
    }


def generate_revenue_tips(peak_periods, off_peak_periods):
    """Generate revenue optimization tips"""
    tips = [
        "Focus marketing efforts on off-peak periods with attractive pricing",
        "Consider bundle deals during off-peak times",
        "Monitor competitor pricing during peak periods",
        "Implement early-bird discounts for advance bookings",
        "Use dynamic pricing to optimize revenue across all time periods"
    ]
    
    if len(peak_periods) > 8:
        tips.append("Consider adding more capacity during extended peak periods")
    
    if len(off_peak_periods) > 6:
        tips.append("Strong opportunity for off-peak promotions and discounts")
    
    return tips


def analyze_route_pattern(time_analysis):
    """Analyze demand patterns for specific routes"""
    high_demand_times = [t["time"] for t in time_analysis if t["demand_level"] in ["high", "medium-high"]]
    low_demand_times = [t["time"] for t in time_analysis if t["demand_level"] in ["low", "medium-low"]]
    
    return {
        "high_demand_periods": high_demand_times,
        "low_demand_periods": low_demand_times,
        "pattern_type": "mixed" if high_demand_times and low_demand_times else "stable",
        "optimization_opportunities": [
            "Increase capacity during high-demand periods",
            "Implement dynamic pricing based on demand patterns",
            "Target marketing to low-demand periods"
        ]
    }


def generate_competitive_advantage(time_analysis):
    """Generate competitive advantage strategies"""
    return {
        "price_elasticity": "Adjust prices based on real-time demand to stay competitive",
        "value_proposition": "Offer transparent pricing with clear demand explanations",
        "customer_insights": "Use demand predictions to provide better customer service",
        "operational_efficiency": "Optimize fleet allocation based on demand patterns"
    }


def generate_customer_recommendations(time_analysis):
    """Generate recommendations for customers"""
    off_peak_times = [t["time"] for t in time_analysis if t["classification"] == "off-peak"]
    peak_times = [t["time"] for t in time_analysis if t["classification"] == "peak"]
    
    recommendations = {
        "best_times_to_book": off_peak_times[:3] if off_peak_times else ["Flexible timing recommended"],
        "times_to_avoid": peak_times if peak_times else ["No significant peak periods identified"],
        "money_saving_tips": [
            "Book during off-peak periods for better prices",
            "Consider alternative departure times",
            "Book in advance to avoid price increases"
        ],
        "convenience_vs_price": "Balance preferred travel times with potential price variations"
    }
    
    return recommendations
