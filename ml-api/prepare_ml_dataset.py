"""
Prepare ML dataset from ml_events table.
Requires: pandas, sqlalchemy, pymysql (or appropriate DB driver)
"""
import pandas as pd
import numpy as np
from sqlalchemy import create_engine
from datetime import datetime
import os

# Configure via env or hardcode for local dev
DB_URI = os.environ.get('PROJECT_DB_URI') or "mysql+pymysql://user:pass@127.0.0.1/project_bus"

def is_holiday_date(dt):
    # simple holiday list; extend as needed
    monthday = dt.strftime("%m-%d")
    return monthday in {"12-25","01-01"}

def main():
    engine = create_engine(DB_URI)
    q = "SELECT * FROM ml_events ORDER BY id ASC LIMIT 10000"
    df = pd.read_sql(q, engine)

    if df.empty:
        print("No ml_events rows found.")
        return

    # parse dates/times
    df['event_datetime'] = pd.to_datetime(df['event_date'].astype(str) + ' ' + df['event_time'].astype(str), errors='coerce')
    df['date'] = pd.to_datetime(df['event_date'], errors='coerce')
    df['hour'] = df['event_datetime'].dt.hour.fillna(0).astype(int)
    df['is_weekend'] = df['date'].dt.dayofweek.isin([5,6]).astype(int)
    df['is_holiday'] = df['date'].apply(lambda d: 1 if (d is not pd.NaT and is_holiday_date(d)) else 0)

    # ensure numeric fields
    df['distance_km'] = pd.to_numeric(df['distance_km'], errors='coerce').fillna(0)
    df['avg_speed'] = pd.to_numeric(df['avg_speed'], errors='coerce').fillna(df['distance_km'] * 0.5)  # fallback

    # Feature engineering: cyclic day_of_week and hour
    df['day_of_week'] = df['date'].dt.dayofweek.fillna(0).astype(int)
    df['dow_sin'] = np.sin(2 * np.pi * df['day_of_week'] / 7.0)
    df['dow_cos'] = np.cos(2 * np.pi * df['day_of_week'] / 7.0)
    df['hour_sin'] = np.sin(2 * np.pi * df['hour'] / 24.0)
    df['hour_cos'] = np.cos(2 * np.pi * df['hour'] / 24.0)

    # bookings (fill missing with 0)
    df['bookings'] = pd.to_numeric(df['bookings'], errors='coerce').fillna(0).astype(int)

    # select relevant columns
    out_cols = [
        'date','hour','route_id','origin','destination','distance_km','avg_speed',
        'is_weekend','is_holiday','bookings','dow_sin','dow_cos','hour_sin','hour_cos'
    ]
    for c in out_cols:
        if c not in df.columns:
            df[c] = 0

    out = df[out_cols]
    out.to_csv('ml_events_prepared.csv', index=False)
    print("Saved ml_events_prepared.csv rows:", len(out))

if __name__ == '__main__':
    main()
