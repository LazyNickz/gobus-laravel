{{-- Simple predict form with result display --}}
<form id="predict-form" action="/predict" method="POST">
    @csrf

    <!-- Distance in km -->
    <label>
        Distance (km)
        <input id="distance-km" type="number" name="distance_km" placeholder="Distance (km)" required min="0" step="0.01"
               value="{{ is_scalar(old('distance_km', request()->get('distance_km', ''))) ? old('distance_km', request()->get('distance_km', '')) : '' }}">
    </label>

    <!-- Average speed -->
    <label>
        Average Speed
        <input id="avg-speed" type="number" name="avg_speed" placeholder="Average Speed" required min="0.1" step="0.1"
               value="{{ is_scalar(old('avg_speed', request()->get('avg_speed', ''))) ? old('avg_speed', request()->get('avg_speed', '')) : '' }}">
    </label>

    <!-- Is weekend / holiday -->
    <label>
        Is Weekend
        <input id="is-weekend" type="checkbox" name="is_weekend" value="1" {{ !is_array(request()->get('is_weekend')) && request()->get('is_weekend') ? 'checked' : '' }}>
    </label>

    <label>
        Is Holiday
        <input id="is-holiday" type="checkbox" name="is_holiday" value="1" {{ !is_array(request()->get('is_holiday')) && request()->get('is_holiday') ? 'checked' : '' }}>
    </label>

    <!-- Date and Time -->
    <label>
        Date (YYYY-MM-DD)
        <input id="date-input" type="date" name="date" value="{{ is_scalar(old('date', request()->get('date', ''))) ? old('date', request()->get('date', '')) : '' }}">
    </label>

    <label>
        Time (HH:MM)
        <input id="time-input" type="time" name="time" value="{{ is_scalar(old('time', request()->get('time', ''))) ? old('time', request()->get('time', '')) : '' }}">
    </label>

    <!-- Route/origin/destination -->
    <label>
        Route (e.g. Manila-Baguio)
        <input id="route-input" type="text" name="route" value="{{ is_scalar(old('route', request()->get('route', ''))) ? old('route', request()->get('route', '')) : '' }}">
    </label>

    <label>
        Origin
        <input id="origin-input" type="text" name="origin" value="{{ is_scalar(old('origin', request()->get('origin', ''))) ? old('origin', request()->get('origin', '')) : '' }}">
    </label>

    <label>
        Destination
        <input id="destination-input" type="text" name="destination" value="{{ is_scalar(old('destination', request()->get('destination', ''))) ? old('destination', request()->get('destination', '')) : '' }}">
    </label>

    <!-- Optional days to holidays (you can leave blank to auto-compute) -->
    <label>
        Days to Christmas (optional)
        <input id="days-to-christmas" type="number" name="days_to_christmas" min="0" step="1"
               value="{{ is_scalar(old('days_to_christmas', request()->get('days_to_christmas', ''))) ? old('days_to_christmas', request()->get('days_to_christmas', '')) : '' }}">
    </label>

    <label>
        Days to New Year (optional)
        <input id="days-to-new-year" type="number" name="days_to_new_year" min="0" step="1"
               value="{{ is_scalar(old('days_to_new_year', request()->get('days_to_new_year', ''))) ? old('days_to_new_year', request()->get('days_to_new_year', '')) : '' }}">
    </label>

    <button id="predict-button" type="submit">Predict</button>
</form>

<!-- Display result or error returned by controller -->
@if(isset($prediction) && $prediction !== null)
    <div style="margin-top:1em;color:green;">
        Prediction: {{ is_array($prediction) ? json_encode($prediction) : $prediction }}
    </div>
@endif

@if(isset($error) && $error)
    <div style="margin-top:1em;color:red;">
        Error: {{ is_array($error) ? json_encode($error) : $error }}
    </div>
@endif

{{-- Show ML API health info when available --}}
@if(isset($ml_health) && $ml_health)
    <div style="margin-top:1em;color:#333;font-family:monospace;">
        <strong>ML API health:</strong>
        <pre>{{ is_array($ml_health) ? json_encode($ml_health, JSON_PRETTY_PRINT) : (string)$ml_health }}</pre>
    </div>
@endif

<!-- Small client-side UX: disable button while submitting and auto-populate derived fields -->
<script>
(function () {
    const MS_PER_DAY = 24 * 60 * 60 * 1000;

    const dateInput = document.getElementById('date-input');
    const daysToChristmasInput = document.getElementById('days-to-christmas');
    const daysToNewYearInput = document.getElementById('days-to-new-year');
    const isWeekendCheckbox = document.getElementById('is-weekend');
    const isHolidayCheckbox = document.getElementById('is-holiday');

    // Simple holiday list (add more dates as needed) - format 'MM-DD' or full 'YYYY-MM-DD' to match exact
    const HOLIDAYS = [
        '12-25', // Christmas
        '01-01'  // New Year's Day
        // extend with 'YYYY-MM-DD' entries for fixed-date holidays if needed
    ];

    function parseDateLocal(value) {
        if (!value) return null;
        // value is 'YYYY-MM-DD' from date input
        const parts = value.split('-');
        if (parts.length !== 3) return null;
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const d = parseInt(parts[2], 10);
        return new Date(y, m, d);
    }

    function daysBetween(a, b) {
        // floor difference in days
        return Math.ceil((b - a) / MS_PER_DAY);
    }

    function nextOccurrence(dateObj, monthZeroBased, dayOfMonth) {
        const year = dateObj.getFullYear();
        let cand = new Date(year, monthZeroBased, dayOfMonth);
        if (dateObj > cand) {
            cand = new Date(year + 1, monthZeroBased, dayOfMonth);
        }
        return cand;
    }

    function matchesHoliday(dateObj) {
        if (!dateObj) return false;
        const mmdd = String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(dateObj.getDate()).padStart(2, '0');
        if (HOLIDAYS.includes(mmdd)) return true;
        // also match full-year entries if you add them to HOLIDAYS
        const ymd = dateObj.getFullYear() + '-' + String(dateObj.getMonth() + 1).padStart(2,'0') + '-' + String(dateObj.getDate()).padStart(2,'0');
        if (HOLIDAYS.includes(ymd)) return true;
        return false;
    }

    function updateDerivedFromDate() {
        const value = dateInput.value;
        const d = parseDateLocal(value);
        if (!d) return;

        // weekend detection (Saturday=6, Sunday=0)
        const dow = d.getDay();
        isWeekendCheckbox.checked = (dow === 0 || dow === 6);

        // holiday detection using list / set
        isHolidayCheckbox.checked = matchesHoliday(d);

        // days to next christmas (Dec 25)
        const nextXmas = nextOccurrence(d, 11, 25); // month 11 = Dec
        const daysToXmas = daysBetween(d, nextXmas);
        daysToChristmasInput.value = daysToXmas;

        // days to next new year (Jan 1)
        // if date is exactly Jan 1 of a year, days to next new year is 0 (or 365) - we compute next Jan1 >= date
        let candidateNY = new Date(d.getFullYear(), 0, 1);
        if (d > candidateNY) {
            candidateNY = new Date(d.getFullYear() + 1, 0, 1);
        }
        const daysToNY = daysBetween(d, candidateNY);
        daysToNewYearInput.value = daysToNY;
    }

    // initialize on load if date has value
    document.addEventListener('DOMContentLoaded', function () {
        if (dateInput && dateInput.value) {
            updateDerivedFromDate();
        }
    });

    // update when user changes date
    if (dateInput) {
        dateInput.addEventListener('change', updateDerivedFromDate);
    }

    // keep previous behavior for submit button
    document.getElementById('predict-form').addEventListener('submit', function () {
        var btn = document.getElementById('predict-button');
        btn.disabled = true;
        btn.textContent = 'Predicting...';
    });
})();
</script>