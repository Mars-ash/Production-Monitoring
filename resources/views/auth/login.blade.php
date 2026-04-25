<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Daily Live Production</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d253f 0%, #1e3a5f 50%, #2c5282 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1e3a5f, #2c5282);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .login-header .icon-wrapper i {
            font-size: 1.8rem;
            color: #fff;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1d23;
            margin-bottom: 0.25rem;
        }

        .login-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-floating .form-control {
            border-radius: 10px;
            border: 1.5px solid #dee2e6;
            height: calc(3.5rem + 2px);
        }

        .form-floating .form-control:focus {
            border-color: #1e3a5f;
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.15);
        }

        .btn-login {
            background: linear-gradient(135deg, #1e3a5f, #2c5282);
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.3px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(30, 58, 95, 0.4);
        }

        .alert-error {
            border-radius: 10px;
            border: none;
            background: #fff5f5;
            color: #dc3545;
            font-size: 0.9rem;
        }

        /* Hilangkan centang hijau dan border hijau saat input valid */
        .was-validated .form-control:valid {
            border-color: #dee2e6 !important;
            background-image: none !important;
        }
        .was-validated .form-control:valid:focus {
            border-color: #1e3a5f !important;
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.15) !important;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="icon-wrapper">
                <i class="bi bi-gear-fill"></i>
            </div>
            <h1>Daily Live Production</h1>
        </div>

        @if($errors->any())
            <div class="alert alert-error d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate id="loginForm">
            @csrf

            <div class="form-floating mb-3">
                <input
                    type="text"
                    class="form-control @error('username') is-invalid @enderror"
                    id="username"
                    name="username"
                    placeholder="Username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                >
                <label for="username"><i class="bi bi-person me-1"></i>Username</label>
                <div class="invalid-feedback">
                    Username wajib diisi.
                </div>
            </div>

            <div class="form-floating mb-4">
                <input
                    type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    id="password"
                    name="password"
                    placeholder="Password"
                    required
                >
                <label for="password"><i class="bi bi-lock me-1"></i>Password</label>
                <div class="invalid-feedback">
                    Password wajib diisi.
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
        </form>
    </div>

    <script>
        (function () {
            'use strict'
            var form = document.getElementById('loginForm');
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })()
    </script>
</body>
</html>
