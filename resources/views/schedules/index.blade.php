<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Schedules</title>
</head>
<body>
    <h1>Search Schedules</h1>
    <form method="GET" action="{{ route('schedules.index') }}">
        <input type="text" name="route_from" placeholder="From" value="{{ $filters['route_from'] ?? '' }}">
        <input type="text" name="route_to" placeholder="To" value="{{ $filters['route_to'] ?? '' }}">
        <input type="date" name="date" value="{{ $filters['date'] ?? '' }}">
        <button type="submit">Search</button>
        <a href="{{ route('schedules.create') }}">Create schedule (admin)</a>
    </form>

    @if(session('success'))
        <div style="color:green">{{ session('success') }}</div>
    @endif

    <h2>Results</h2>
    @if($schedules->count())
        <ul>
            @foreach($schedules as $s)
                <li>
                    <strong>{{ $s->route_from }} → {{ $s->route_to }}</strong>
                    <div>Departure: {{ $s->departure_time }}</div>
                    <div>Arrival: {{ $s->arrival_time }}</div>
                    <div>Bus: {{ $s->bus_number }} | Seats: {{ $s->available_seats }}/{{ $s->seats }} | Fare: {{ $s->fare }}</div>
                    <div><a href="{{ route('schedules.show', $s->id) }}">Details</a></div>
                </li>
            @endforeach
        </ul>

        {{ $schedules->links() }}
    @else
        <p>No schedules found.</p>
    @endif
</body>
</html>
