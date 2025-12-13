<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GoBus — User Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-blue: #0066cc;
      --secondary-yellow: #ffd700;
      --light-blue: #e6f3ff;
      --dark-blue: #003d7a;
      --success-green: #28a745;
      --danger-red: #dc3545;
    }

    .booking-header {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
      color: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
      border: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      color: white;
      font-weight: 600;
      padding: 12px 24px;
      cursor: pointer;
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,102,204,0.3);
    }

    .btn-secondary {
      background: white;
      border: 2px solid var(--primary-blue);
      color: var(--primary-blue);
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 600;
      padding: 12px 24px;
      cursor: pointer;
    }

    .btn-secondary:hover {
      background: var(--primary-blue);
      color: white;
    }

    .booking-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border: 1px solid #f3f4f6;
      transition: all 0.3s ease;
    }

    .booking-card:hover {
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      transform: translateY(-1px);
    }

    .form-input {
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      padding: 0.75rem;
      transition: all 0.2s ease;
      width: 100%;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0,102,204,0.1);
    }

    .form-label {
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
      display: block;
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(6,12,20,0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 3000;
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal {
      width: 100%;
      max-width: 480px;
      background: #ffffff;
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.25);
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
    }

    .modal .close {
      background: transparent;
      border: 0;
      font-size: 24px;
      cursor: pointer;
      color: #444;
    }

    .error-text {
      color: var(--danger-red);
      font-size: 0.875rem;
      margin-top: 0.25rem;
    }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Header -->
  <header class="booking-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">
        <!-- Logo -->
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center">
            <i class="fas fa-bus text-blue-900 text-xl"></i>
          </div>
          <h1 class="text-2xl font-bold">GoBus</h1>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:flex items-center space-x-8">
          <a href="/book" class="text-white hover:text-yellow-300 transition-colors font-medium">Book</a>
          <a href="/manage" class="text-white hover:text-yellow-300 transition-colors font-medium">Manage</a>
          <a href="/routes" class="text-white hover:text-yellow-300 transition-colors font-medium">Routes</a>
          <a href="/help" class="text-white hover:text-yellow-300 transition-colors font-medium">Help</a>
        </nav>

        <!-- Login Button -->
        <div class="flex items-center space-x-4">
          @if(isset($user) && $user)
            <span class="text-white text-sm">Welcome, {{ $user['name'] ?? $user['email'] }}</span>
            <a href="/user-logout" class="btn-secondary px-4 py-2 text-sm">Logout</a>
          @else
            <a href="/user-login" class="btn-secondary px-4 py-2 text-sm">Login</a>
          @endif
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-center items-center min-h-[calc(100vh-200px)]">
      <div class="booking-card w-full max-w-md">
        <div class="text-center mb-6">
          <h2 class="text-3xl font-bold text-gray-900 mb-2">
            <span style="color:#ffd700;">GoBus</span>
            <span style="color:#0066cc;">User</span>
          </h2>
          <div class="text-gray-600">User access — sign in to continue</div>
        </div>

        <!-- show server-side login error -->
        @if($errors->has('login'))
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            {{ $errors->first('login') }}
          </div>
        @endif

        <!-- set autocomplete off to discourage browser caching -->
        <form id="userLoginForm" autocomplete="off" method="POST" action="{{ url('/user-login') }}">
          @csrf
          <!-- preserve next param if present -->
          @if(request()->has('next'))
            <input type="hidden" name="next" value="{{ request()->get('next') }}">
          @endif

          <div class="mb-4">
            <label class="form-label">Email</label>
            <input id="userEmail" name="email" type="text" autocomplete="off" placeholder="Enter your email" value="" class="form-input" />
            <div id="userEmailErr" class="error-text"></div>
          </div>

          <div class="mb-6">
            <label class="form-label">Password</label>
            <input id="userPass" name="password" type="password" autocomplete="new-password" placeholder="Enter your password" value="" class="form-input" />
            <div id="userPassErr" class="error-text"></div>
          </div>

          <div class="mb-4">
            <button class="btn-primary w-full" type="submit">Login</button>
          </div>
        </form>

        <!-- Register link opens modal (modal submits server-side) -->
        <div class="text-center">
          <a href="#" onclick="document.getElementById('userRegisterModal').classList.add('open');return false;" class="text-blue-600 hover:text-blue-800 font-medium">
            Create an account
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- User Registration Modal (server-side) -->
  <div id="userRegisterModal" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="registerTitle">
      <div class="modal-header">
        <h3 id="registerTitle">Create an account</h3>
        <button class="close" onclick="document.getElementById('userRegisterModal').classList.remove('open')">×</button>
      </div>
      <div class="booking-card">
        <!-- show validation errors -->
        @if($errors->any() && ($errors->has('email') || $errors->has('name') || $errors->has('password')))
          <div class="error-text mb-4">
            Please correct the errors in the form.
          </div>
        @endif

        <form id="userRegisterForm" autocomplete="off" method="POST" action="{{ url('/user-register') }}">
          @csrf
          <div class="mb-4">
            <label class="form-label">Name</label>
            <input id="registerName" name="name" type="text" autocomplete="off" placeholder="Full name" value="" class="form-input" />
            <div id="registerNameErr" class="error-text">@error('name'){{ $message }}@enderror</div>
          </div>

          <div class="mb-4">
            <label class="form-label">Email</label>
            <input id="registerEmail" name="email" type="text" autocomplete="off" placeholder="Email address" value="" class="form-input" />
            <div id="registerEmailErr" class="error-text">@error('email'){{ $message }}@enderror</div>
          </div>

          <div class="mb-4">
            <label class="form-label">Password</label>
            <input id="registerPass" name="password" type="password" autocomplete="new-password" placeholder="Password" value="" class="form-input" />
            <div id="registerPassErr" class="error-text">@error('password'){{ $message }}@enderror</div>
          </div>

          <div class="mb-6">
            <label class="form-label">Confirm Password</label>
            <input id="registerPassConfirm" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Confirm password" value="" class="form-input" />
            <div id="registerPassConfirmErr" class="error-text"></div>
          </div>

          <div class="flex gap-3">
            <button class="btn-primary flex-1" type="submit">Register</button>
            <button class="btn-secondary flex-1" type="button" onclick="document.getElementById('userRegisterModal').classList.remove('open')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  // reset auth forms and clear error UI — run on load and when page restored from bfcache
  function resetAuthForms(){
    // inputs to clear
    const ids = ['userEmail','userPass','registerName','registerEmail','registerPass','registerPassConfirm'];
    ids.forEach(id=>{
      const el = document.getElementById(id);
      if(el){
        el.value = '';
        el.classList.remove('input-invalid');
      }
      const err = document.getElementById(id + 'Err');
      if(err) err.textContent = '';
    });

    // also clear generic server-side login error card if present
    const loginCardErr = document.querySelector('.bg-red-50');
    if(loginCardErr) loginCardErr.style.display = 'none';
  }

  document.addEventListener('DOMContentLoaded', resetAuthForms);
  // pageshow handles bfcache navigation where DOMContentLoaded may not run
  window.addEventListener('pageshow', function(event){
    if (event.persisted) resetAuthForms();
  });
  </script>

  <script>
  document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('userLoginForm');
    if(!form) return;

    form.addEventListener('submit', function(ev){
      // prefer AJAX; if ajaxUserLogin not available, allow normal submit
      if (typeof window.ajaxUserLogin !== 'function') return;

      ev.preventDefault();
      // clear errors
      document.getElementById('userEmailErr').textContent = '';
      document.getElementById('userPassErr').textContent = '';

      const email = (document.getElementById('userEmail').value || '').trim();
      const password = (document.getElementById('userPass').value || '').trim();

      // basic client-side guard
      if(!email || !password){
        if(!email) document.getElementById('userEmailErr').textContent = 'Email required';
        if(!password) document.getElementById('userPassErr').textContent = 'Password required';
        return;
      }

      // call AJAX helper (returns {ok,status,data})
      window.ajaxUserLogin(email, password).then(resp => {
        if(resp.ok && resp.data && resp.data.ok){
          // success -> redirect (server returns redirect target)
          window.location.href = resp.data.redirect || '/user/reservations';
          return;
        }

        // handle validation / auth errors
        if(resp.status === 422){
          // Laravel validation errors or custom JSON error
          const d = resp.data || {};
          if(d.error){
            document.getElementById('userPassErr').textContent = d.error;
          } else if(d.errors){
            // show first error on corresponding fields
            if(d.errors.email) document.getElementById('userEmailErr').textContent = d.errors.email.join(' ');
            if(d.errors.password) document.getElementById('userPassErr').textContent = d.errors.password.join(' ');
          } else {
            document.getElementById('userPassErr').textContent = 'Invalid credentials';
          }
        } else {
          document.getElementById('userPassErr').textContent = 'Login failed. Try again.';
        }
      }).catch(err=>{
        console.error('ajaxUserLogin error', err);
        document.getElementById('userPassErr').textContent = 'Network error';
      });
    });
  });
  </script>
</body>
</html>
