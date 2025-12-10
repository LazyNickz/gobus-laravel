<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GoBus — Profile</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="{{ url('frontend/admin.css') }}">
</head>
<body>
  <div class="header">
    <div class="brand">GoBus</div>
    <div style="display:flex;align-items:center;gap:12px">
      <a href="/user-reservations" class="btn btn-ghost">Explore</a>
      <a href="/user-logout" class="btn btn-ghost">Logout</a>
    </div>
  </div>

  <div class="content" style="padding-top:40px">
    <div class="card" style="max-width:700px;margin:0 auto">
      <h2>Profile</h2>
      @if(!empty($user))
        @php $first = explode(' ', trim($user['name']))[0] ?? $user['name']; @endphp
        <div style="display:flex;align-items:center;gap:16px;margin-top:12px">
          <div class="avatar" style="width:64px;height:64px;font-size:20px">{{ strtoupper(substr($first,0,1)) }}</div>
          <div>
            <div style="font-weight:800;font-size:18px">{{ $first }}</div>
            <div style="color:var(--muted)">{{ $user['email'] }}</div>
          </div>
        </div>
      @else
        <p>Please <a href="/user-login">login</a>.</p>
      @endif

      <hr style="margin:16px 0" />
      <h3>Quick links</h3>
      <ul>
        <li><a href="/user/reservations">My Reservations</a></li>
        <li><a href="/user/profile">Profile</a></li>
      </ul>
    </div>
  </div>
</body>
</html>
