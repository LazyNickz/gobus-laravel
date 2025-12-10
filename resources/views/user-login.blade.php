<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GoBus — User Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{url('frontend/admin.css')}}">
</head>

<body>

  <div class="login-wrap">
    <div class="login-card">
      
      <h2>
        <span style="color:#ffeb54;">GoBus</span>
        <span style="color:#1c6df6;">User</span>
      </h2>

      <div class="login-sub">User access — sign in to continue</div>

      <!-- show server-side login error -->
      @if($errors->has('login'))
        <div class="card" style="background:#fff8f8;border:1px solid #ffd6d6;color:#a40000;margin-bottom:12px">
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

        <label>Email</label>
        <input id="userEmail" name="email" type="text" autocomplete="off" placeholder="Enter your email" value="" />
        <div id="userEmailErr" class="error-text"></div>

        <label>Password</label>
        <input id="userPass" name="password" type="password" autocomplete="new-password" placeholder="Enter your password" value="" />
        <div id="userPassErr" class="error-text"></div>

        <div class="row">
          <button class="btn btn-primary" type="submit">Login</button>
        </div>
      </form>

      <!-- Register link opens modal (modal submits server-side) -->
      <div style="margin-top:12px; text-align:center;">
        <a href="#" onclick="document.getElementById('userRegisterModal').classList.add('open');return false;">Create an account</a>
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
      <div class="card">
        <!-- show validation errors -->
        @if($errors->any() && $errors->has('email') || $errors->has('name') || $errors->has('password'))
          <div class="error-text" style="margin-bottom:10px;">
            Please correct the errors in the form.
          </div>
        @endif

        <form id="userRegisterForm" autocomplete="off" method="POST" action="{{ url('/user-register') }}">
          @csrf
          <label>Name</label>
          <input id="registerName" name="name" type="text" autocomplete="off" placeholder="Full name" value="" />
          <div id="registerNameErr" class="error-text">@error('name'){{ $message }}@enderror</div>

          <label>Email</label>
          <input id="registerEmail" name="email" type="text" autocomplete="off" placeholder="Email address" value="" />
          <div id="registerEmailErr" class="error-text">@error('email'){{ $message }}@enderror</div>

          <label>Password</label>
          <input id="registerPass" name="password" type="password" autocomplete="new-password" placeholder="Password" value="" />
          <div id="registerPassErr" class="error-text">@error('password'){{ $message }}@enderror</div>

          <label>Confirm Password</label>
          <input id="registerPassConfirm" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Confirm password" value="" />
          <div id="registerPassConfirmErr" class="error-text"></div>

          <div class="row" style="margin-top:12px;">
            <button class="btn btn-primary" type="submit">Register</button>
            <button class="btn btn-ghost" type="button" onclick="document.getElementById('userRegisterModal').classList.remove('open')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script defer src="{{ asset('frontend/admin.js') }}"></script>

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
    const loginCardErr = document.querySelector('.card[style*="fff8f8"]');
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
