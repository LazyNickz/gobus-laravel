<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>My Reservations — GoBus</title>
  <link rel="stylesheet" href="{{url('frontend/book.css')}}"/>
  <script defer src="reservations.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <header class="navbar">
    <div class="brand"><span class="logo" style="color:2b9cff">GoBus</span></div>
    <nav class="menu">
      <a href="book.html">Home</a>
      <a href="my-reservations.html" class="active">My Reservations</a>
      <a href="about.html">About</a>
    </nav>
    <div class="nav-right">
      <button class="login-btn"><i class="fa fa-user"></i> Login</button>
    </div>
  </header>

  <main style="max-width:1400px;margin:28px auto;padding:18px;">
    <div id="reservationsContainer"></div>
  </main>

  <footer class="site-footer">© GoBus — Demo</footer>
  <div id="userReservationModal" class="modal-overlay">
  <div class="modal">
    <div class="modal-header">
      <h3>Reservation Details</h3>
      <button class="close" onclick="document.getElementById('userReservationModal').classList.remove('open')">&times;</button>
    </div>

    <div id="userDetailBody"></div>
  </div>
</div>

</body>
</html>
