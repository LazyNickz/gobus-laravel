<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Login | GoBus</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ url('frontend/admin.css') }}">
    <style>

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2b9cff 0%, #1e87e6 100%);
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        .login-header p {
            color: #666;
            margin: 8px 0 0 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2b9cff;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2b9cff 0%, #1e87e6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        .success-message {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>GoBus Admin</h1>
                <p>Sign in to access the admin panel</p>
            </div>

            @if(session('error'))
                <div class="error-message">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif



            <form method="POST" action="{{ route('admin.login.submit') }}" id="loginForm">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="error-message" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    @error('password')
                        <div class="error-message" style="margin-top: 8px;">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    Sign In
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
                <p>Demo credentials:</p>
                <p><strong>Email:</strong> admin@gobus.local</p>
                <p><strong>Password:</strong> admin123</p>
            </div>
        </div>
    </div>

    <script>
        // Form submission handling with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.textContent = 'Signing in...';
        });
    </script>
</body>
</html>
