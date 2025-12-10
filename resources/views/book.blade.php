<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>GoBus — Home</title>

  <link rel="stylesheet" href="{{url('frontend/book.css')}}" />
  <script defer src="book.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>

<body>
  <!-- NAVBAR -->
  <header class="navbar">
    <div class="brand"><span class="logo" style="color:#2b9cff">GoBus</span></div>

    <nav class="menu">
      <a href="book.html" class="active">Home</a>
      <a href="#" id="navReservations">My Reservations</a>
      <a href="about.html">About</a>
    </nav>

    <div class="nav-right">
      <button class="login-btn"><i class="fa fa-user"></i> Login</button>
    </div>
  </header>

  <!-- HERO SECTION -->
<section class="hero-banner">
  <div class="hero-left">
    <h1 class="hero-title-new">Stop Pining, Start<br> Traveling With Us</h1>
    <p class="hero-subtext">Your comfort, safety, and adventure — all in every GoBus trip.</p>
  </div>
</section>

  <main class="floating-card">

    <!-- TABS -->
    <div class="tabs">
      <button id="onewayBtn" class="trip active">One-way</button>
      <button id="roundBtn" class="trip">Round-trip</button>
    </div>

    <!-- FORM -->
    <div class="form-grid">

  <div class="field with-swap">
    <label>From</label>
    <input id="fromSelect" type="text" placeholder="Select Origin" />
  </div>

  <div class="swap-icon"><i class="fa-solid fa-right-left"></i></div>

  <div class="field with-swap">
    <label>To</label>
    <input id="toSelect" type="text" placeholder="Select Destination" />
  </div>

  <div class="field date-field">
    <label>Departure</label>
    <input id="departDate" type="text" placeholder="Select departure date" readonly />
    <div id="calendarPopupDepart" class="calendar-popup hidden"></div>
  </div>

  <div class="field date-field round-only hidden">
  <label>Return</label>
  <input id="returnDate" type="text" placeholder="Select return date" readonly />
  <div id="calendarPopupReturn" class="calendar-popup hidden"></div>
</div>

  <div class="field small-input">
    <label>Adults</label>
    <input id="adults" type="number" min="1" value="1" />
  </div>

  <div class="field small-input">
    <label>Children</label>
    <input id="children" type="number" min="0" value="0" />
  </div>

  <div class="field">
    <label>Bus type</label>
    <select id="busType">
      <option value="auto">Auto allocate</option>
      <option value="deluxe">Deluxe (20)</option>
      <option value="regular">Regular (40)</option>
    </select>
  </div>

  <div class="full-search-wrapper">
    <button id="searchSchedules" class="search-btn-big">SEARCH</button>
  </div>

</div>

  </main>

  <div id="modalSchedules" class="modal-sheet hidden">
    <div class="modal-content">

      <div class="modal-header">
        <h3>Available Schedules</h3>
        <button class="close-modal" data-close="modalSchedules">&times;</button>
      </div>

      <div id="scheduleList" class="schedule-list"></div>

      <div class="modal-actions">
        <button id="selectSeatBtn" class="primary-btn hidden">SELECT SEAT</button>
      </div>

    </div>
  </div>
<!--      MODAL 2: SEAT SELECTION    -->
<div id="modalSeats" class="modal-sheet hidden">
  <div class="modal-content">

    <!-- Fixed Header -->
    <div class="modal-seat-header">
      <button id="backToSchedules" class="back-modal">←</button>

      <h2 class="modal-seat-title">Select Your Seat</h2>

      <button class="close-modal" data-close="modalSeats">&times;</button>
    </div>

    <!-- Scrollable Body -->
    <div class="modal-seat-body">
      <div id="seatMap" class="seat-map"></div>

      <div class="legend">
        <span><span class="box available"></span> Available</span>
        <span><span class="box booked"></span> Booked</span>
        <span><span class="box selected"></span> Selected</span>
      </div>
    </div>

    <!-- Fixed Footer -->
    <div class="modal-seat-footer">
      <button id="bookNowBtn">Confirm</button>
    </div>

  </div>
</div>

<!-- =============================== -->
<!--      MODAL 3: CONFIRMATION     -->
<!-- =============================== -->
<div id="modalConfirm" class="modal-sheet hidden">
  <div class="modal-content">

    <div class="modal-header">
      <h3>Confirm Your Booking</h3>
      <button class="close-modal" data-close="modalSeats">&times;</button>
    </div>

    <div id="confirmDetails" class="confirm-details"></div>

    <div class="modal-actions">
      <button id="finalBookBtn" class="primary-btn">BOOK NOW</button>
    </div>

  </div>
</div>

  <!-- LOGIN MODAL -->
<!-- LOGIN MODAL -->
<div id="loginModal" class="modal-sheet hidden">
  <div class="modal-content login-card">

    <button class="close-login">&times;</button>

    <h2 class="login-title"><span style="color:#2b9cff;">GoBus</span> Login</h2>

    <div class="login-field">
      <label>Email</label>
      <input type="email" id="loginEmail">
    </div>

    <div class="login-field">
      <label>Password</label>
      <input type="password" id="loginPass">
    </div>

    <button id="loginSubmit" class="primary-btn login-submit">Login</button>

    <p class="bottom-text">
      Don’t have an account yet?
      <a href="#" id="openRegister">Register here</a>
    </p>

  </div>
</div>

<!-- REGISTER MODAL -->
<!-- REGISTER MODAL -->
<div id="registerModal" class="modal-sheet hidden">
  <div class="modal-content login-card">

    <button class="close-register">&times;</button>

    <h2 class="login-title"><span style="color:#2b9cff;">GoBus</span> Register</h2>

    <div class="login-field">
      <label>Full Name</label>
      <input type="text" id="regName">
      <small class="error-msg"></small>
    </div>

    <div class="login-field">
      <label>Email</label>
      <input type="email" id="regEmail">
      <small class="error-msg"></small>
    </div>

    <div class="login-field">
      <label>Password</label>
      <input type="password" id="regPass">
      <small class="error-msg pass-rules"></small>
    </div>

    <button id="registerSubmit" class="primary-btn login-submit">Create Account</button>

    <p class="bottom-text">
      Already have an account?
      <a href="#" id="openLogin">Login here</a>
    </p>

  </div>
</div>


  <footer class="site-footer">© GoBus — Demo</footer>

</body>
</html>
