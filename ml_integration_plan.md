# ML API Integration Plan for Trip Selection

## Information Gathered
- ✅ ML API is operational and returning demand predictions
- ✅ trip-selection.blade.php has UI elements for ML predictions
- ❌ Currently using hardcoded pricing (₱800 base, ₱850 weekend, ₱1000 holiday)
- ❌ No actual ML API calls in the JavaScript

## Plan: Integrate ML API into Trip Selection

### 1. Backend Integration
- Replace hardcoded pricing logic with ML API calls
- Use `/predict-bulk` endpoint to get 7-day demand forecasts
- Apply ML-derived price multipliers to base prices

### 2. Frontend Integration
- Add JavaScript functions to call ML API
- Update date cards with real demand levels (low/medium/high)
- Implement dynamic pricing based on predictions
- Add demand indicators and price adjustment badges

### 3. Enhanced Features
- Real-time demand level display
- ML-based weekend/holiday pricing
- Demand color coding (green=low, yellow=medium, red=high)
- Price multiplier indicators (↓ lower, ↑ higher)

## Files to be Modified
1. **trip-selection.blade.php** - Main integration file
   - Add ML API call functions
   - Update date card generation logic
   - Enhance JavaScript for dynamic pricing

## Implementation Steps
1. Add ML API call function
2. Update date card rendering with predictions
3. Replace hardcoded pricing with ML-based pricing
4. Add demand level indicators
5. Test integration

## Expected Outcome
- Trip selection page will show real ML-powered demand predictions
- Dynamic pricing based on actual demand forecasts
- Enhanced user experience with intelligent pricing recommendations
