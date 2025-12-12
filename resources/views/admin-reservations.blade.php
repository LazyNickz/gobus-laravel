<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Reservations | GoBus</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- add CSRF token so frontend JS can include it on requests -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{url('frontend/admin.css')}}">
  <script defer src="{{ asset('frontend/admin.js') }}"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body onload="initReservationsPage(); setupSidebar();">
  <header class="header">
    <div class="brand"><span class="logo" style="color:#2b9cff">GoBus admin</span></div>
  </header>

  <div class="admin-wrap">
    <aside class="sidebar">
  <div class="item" data-target="/admin/schedules" onclick="location.href='/admin/schedules'"><i class="fa fa-calendar"></i> Schedules</div>
  <div class="item active" data-target="/admin/reservations" onclick="location.href='/admin/reservations'"><i class="fa fa-list"></i> Reservations</div>
      <div style="flex:1"></div>
      <div class="item" onclick="logoutAdmin()"><i class="fa fa-sign-out-alt"></i> Logout</div>
    </aside>

    <main class="content">
      <div class="card" id="reservationsTable"></div>
    </main>
  </div>
<div id="adminReservationModal">
  <div class="admin-modal-box">
    <h2>Reservation Details</h2>
    <div id="adminDetailBody"></div>
    <button onclick="document.getElementById('adminReservationModal').classList.remove('open')"
            style="margin-top:20px; width:100%; padding:10px; border:none; background:#1c6df6; color:white; border-radius:8px; font-weight:700;">
      Close
    </button>
  </div>
</div>

</body>
</html>
