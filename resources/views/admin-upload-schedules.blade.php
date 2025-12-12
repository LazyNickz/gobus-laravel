<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Upload Schedules — Admin</title>
  <link rel="stylesheet" href="{{ url('frontend/admin.css') }}">
  <style>
    .container{max-width:900px;margin:40px auto;padding:18px}
    pre.sample{background:#f8fafc;border:1px solid #eef2f7;padding:12px;border-radius:8px}
  </style>
</head>
<body>
  <div class="container">
    <h1 class="brand">Upload Schedules</h1>

    @if(session('error'))
      <div class="card" style="border-left:4px solid #ff4d4d;color:#333;margin-bottom:12px">{{ session('error') }}</div>
    @endif
    @if(session('success'))
      <div class="card" style="border-left:4px solid #22c55e;color:#333;margin-bottom:12px">{{ session('success') }}</div>
    @endif

    <div class="card">

      <p>Upload a CSV file with header: <strong>route_from,route_to,departure_time,arrival_time,bus_number,seats,available_seats,fare,status</strong></p>
      <p>Use the format exactly as shown in the sample below. All fields are required except arrival_time, bus_number, and available_seats.</p>


      <form action="{{ route('admin.upload.schedules') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div style="margin-bottom:12px">
          <label>CSV file</label>
          <input type="file" name="csv_file" accept=".csv,text/csv" required>
        </div>
        <button class="btn btn-primary" type="submit">Upload and Create Schedules</button>
      </form>

      <hr style="margin:18px 0">


      <h3>Sample CSV</h3>
      <pre class="sample">route_from,route_to,departure_time,arrival_time,bus_number,seats,available_seats,fare,status
Manila,Baguio,2025-12-15 08:00:00,2025-12-15 12:00:00,BUS001,40,40,1200.00,active
Cebu,Davao,2025-12-16 10:00:00,2025-12-16 18:00:00,BUS002,45,45,1800.00,active</pre>

      <p style="margin-top:10px;color:#6b7280">After upload the schedules become available to user searches and ML events are created (source=admin).</p>
    </div>
  </div>
</body>
</html>
