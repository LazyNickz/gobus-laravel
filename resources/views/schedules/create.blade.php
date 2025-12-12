<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Create Schedule</title>
</head>
<body>
    <h1>Create Schedule</h1>
    <form method="POST" action="{{ route('schedules.store') }}">
        @csrf
        <div>
            <label>From</label>
            <input name="route_from" value="{{ old('route_from') }}">
            @error('route_from')<div style="color:red">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>To</label>
            <input name="route_to" value="{{ old('route_to') }}">
            @error('route_to')<div style="color:red">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>Departure</label>
            <input type="datetime-local" name="departure_time" value="{{ old('departure_time') }}">
            @error('departure_time')<div style="color:red">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>Arrival</label>
            <input type="datetime-local" name="arrival_time" value="{{ old('arrival_time') }}">
            @error('arrival_time')<div style="color:red">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>Bus number</label>
            <input name="bus_number" value="{{ old('bus_number') }}">
        </div>
        <div>
            <label>Seats</label>
            <input type="number" name="seats" value="{{ old('seats', 40) }}">
            @error('seats')<div style="color:red">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>Available seats</label>
            <input type="number" name="available_seats" value="{{ old('available_seats') }}">
        </div>
        <div>
            <label>Fare</label>
            <input type="number" step="0.01" name="fare" value="{{ old('fare', 0) }}">
            @error('fare')<div style="color:red">{{ $message }}</div>@enderror
        </div>
        <button type="submit">Save</button>
    </form>

    <a href="{{ route('schedules.index') }}">Back to search</a>
</body>
</html>
