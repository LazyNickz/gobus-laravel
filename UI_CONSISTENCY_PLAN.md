# UI Consistency Plan: Search Results ↔ Trip Selection

## Issues Identified

### 1. Progress Step Inconsistency
- **Search Results**: Shows "Search Results" as step 1, "Select Trip" as step 2 (active)
- **Trip Selection**: Shows "Select Trip" as step 1 (active), "Passenger Details" as step 2
- **Fix**: Align progress steps - Search Results should show Select Trip as active, Trip Selection should show Select Trip as active

### 2. Booking Summary Layout Differences
- **Search Results**: 4-column grid (From, To, Date, Passengers)
- **Trip Selection**: 5-column grid with "Edit Search" button
- **Fix**: Standardize to 4-column layout with consistent styling

### 3. Card Design Inconsistencies
- **Search Results**: Uses `info-card` for main content
- **Trip Selection**: Uses `trip-card` for trips, `info-card` for sidebar
- **Fix**: Create unified card system with consistent padding, borders, shadows

### 4. Button Placement and Styling
- **Search Results**: Action buttons at bottom of form
- **Trip Selection**: "Edit Search" button in summary bar
- **Fix**: Standardize button placement and add consistent spacing

### 5. Color Scheme Application
- Both use CSS custom properties but apply them inconsistently
- **Fix**: Ensure consistent use of CSS variables across both pages

## Solution Plan

### Phase 1: Standardize Progress Steps
1. Update search-results.blade.php progress to show "Select Trip" as active
2. Ensure consistent step numbering across both pages

### Phase 2: Unified Booking Summary
1. Standardize 4-column layout for both pages
2. Remove "Edit Search" button from trip-selection to match search-results
3. Ensure consistent spacing and typography

### Phase 3: Consistent Card System
1. Create unified `.booking-card` class with consistent:
   - Padding: 1.5rem
   - Border radius: 12px
   - Box shadow: 0 4px 6px rgba(0,0,0,0.05)
   - Border: 1px solid #f3f4f6
2. Apply to both main content areas and sidebar cards

### Phase 4: Button Standardization
1. Ensure consistent button classes and spacing
2. Standardize primary/secondary button usage
3. Align button placement in layouts

### Phase 5: Color and Typography Consistency
1. Verify CSS custom properties are applied consistently
2. Ensure font weights and sizes match across pages
3. Standardize icon usage and sizing

## Implementation Steps
1. Update search-results.blade.php progress indicator
2. Update trip-selection.blade.php booking summary layout
3. Create unified card styling
4. Test both pages for visual consistency
5. Verify responsive behavior on both pages
