<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin — GoBus</title>
  <link rel="stylesheet" href="{{url('frontend/book.css')}}" />
  <script defer src="manage.js"></script>
</head>
<body>
  <header class="navbar">
    <div class="brand"><span class="logo" style="color:yellow">GoBus</span></div>
    <nav class="menu"><a href="index.html">Home</a><a href="book.html">Book</a><a href="manage.html" class="active">Manage</a></nav>
    <div class="nav-right"><button onclick="location.href='index.html'">Back</button></div>
  </header>

  <main class="floating-card">
    <h3>Admin — Schedule Management</h3>

    <section>
      <div>
        <strong>Active days</strong>
        <div id="daysControls"></div>
      </div>

      <div style="margin-top:12px;">
        <strong>Times (per scheduled day)</strong>
        <div id="timesList"></div>
        <input id="newTime" placeholder="HH:MM (24h)" />
        <button id="addTime">Add time</button>
      </div>

      <div style="margin-top:12px;">
        <strong>Default capacities</strong>
        <div>Deluxe capacity: <input id="capDeluxe" type="number" min="1" value="20" /></div>
        <div>Regular capacity: <input id="capRegular" type="number" min="1" value="40" /></div>
        <button id="saveCaps">Save capacities</button>
      </div>

      <hr/>
      <h4>Reservations</h4>
      <div id="reservationsList"></div>
    </section>
  </main>
</body>
</html>
