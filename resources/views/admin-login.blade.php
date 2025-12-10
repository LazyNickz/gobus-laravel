<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GoBus — Admin Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{url('frontend/admin.css')}}">
</head>

<body>

  <div class="login-wrap">
    <div class="login-card">
      
      <h2>
        <span style="color:#ffeb54;">GoBus</span>
        <span style="color:#1c6df6;">Admin</span>
      </h2>

      <div class="login-sub">Administrator access — keep this URL private</div>

      <!-- server-side admin error -->
      @if($errors->has('login'))
        <div class="card" style="background:#fff8f8;border:1px solid #ffd6d6;color:#a40000;margin-bottom:12px">
          {{ $errors->first('login') }}
        </div>
      @endif

      <form id="loginForm" method="POST" action="{{ url('/admin-login') }}">
        @csrf
        <label>Email</label>
        <input id="loginEmail" name="email" type="text" placeholder="Enter your email" value="{{ old('email') }}" />
        <div id="loginEmailErr" class="error-text"></div>

        <label>Password</label>
         <input id="loginPass" name="password" type="password" placeholder="Enter your password" />
        <div id="loginPassErr" class="error-text"></div>

        <div class="row">
          <button class="btn btn-primary" type="submit">Login</button>
        </div>
      </form>

    </div>
  </div>

</body>
</html>
