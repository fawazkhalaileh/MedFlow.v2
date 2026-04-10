<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In - MedFlow CRM</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; }
body {
  font-family: 'DM Sans', -apple-system, sans-serif;
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #0a1628 0%, #1a2d52 40%, #0f4c81 100%);
  position: relative;
}
body::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(circle at 30% 40%, rgba(37,99,235,0.15) 0%, transparent 60%),
    radial-gradient(circle at 70% 80%, rgba(5,150,105,0.10) 0%, transparent 50%);
  pointer-events: none;
}
.login-card {
  position: relative; width: 420px; padding: 48px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  backdrop-filter: blur(40px);
  -webkit-backdrop-filter: blur(40px);
  animation: slideUp 0.6s ease-out both;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}
.login-logo {
  font-family: 'Instrument Serif', Georgia, serif;
  font-size: 2.4rem; color: #fff;
  margin-bottom: 8px; letter-spacing: -0.5px;
}
.login-logo span { color: #2563eb; }
.login-subtitle { color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 36px; }
.alert-error {
  background: rgba(220,38,38,0.12);
  border: 1px solid rgba(220,38,38,0.3);
  border-radius: 10px; color: #fca5a5;
  font-size: 0.85rem; padding: 12px 16px; margin-bottom: 20px;
}
.login-field { margin-bottom: 20px; }
.login-field label {
  display: block; color: rgba(255,255,255,0.6);
  font-size: 0.75rem; font-weight: 600;
  margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.6px;
}
.login-field input {
  width: 100%; padding: 14px 16px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px; color: #fff;
  font-size: 0.95rem; font-family: 'DM Sans', sans-serif;
  transition: border-color 0.2s, background 0.2s; outline: none;
}
.login-field input::placeholder { color: rgba(255,255,255,0.25); }
.login-field input:focus { border-color: #2563eb; background: rgba(37,99,235,0.08); }
.login-field input.is-invalid { border-color: rgba(220,38,38,0.6); background: rgba(220,38,38,0.06); }
.field-error { color: #fca5a5; font-size: 0.78rem; margin-top: 6px; }
.remember-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.remember-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; }
.remember-row label {
  color: rgba(255,255,255,0.5); font-size: 0.85rem; cursor: pointer;
  margin: 0; text-transform: none; letter-spacing: normal; font-weight: 400;
}
.login-btn {
  width: 100%; padding: 14px; background: #2563eb; color: #fff;
  border: none; border-radius: 10px; font-size: 0.95rem; font-weight: 600;
  cursor: pointer; transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
  font-family: 'DM Sans', sans-serif; margin-top: 12px;
}
.login-btn:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 8px 25px rgba(37,99,235,0.35); }
.login-btn:active { transform: translateY(0); }
.login-footer { margin-top: 28px; text-align: center; color: rgba(255,255,255,0.3); font-size: 0.78rem; }
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">Med<span>Flow</span></div>
  <div class="login-subtitle">Clinic Management Platform - Sign in to continue</div>

  @if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf

    <div class="login-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email"
        value="{{ old('email') }}"
        placeholder="admin@medflow.local"
        autocomplete="email"
        class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
        required autofocus>
      @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="login-field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password"
        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
        autocomplete="current-password"
        class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
        required>
      @error('password')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <div class="remember-row">
      <input type="checkbox" id="remember" name="remember">
      <label for="remember">Keep me signed in</label>
    </div>

    <button type="submit" class="login-btn">Sign In</button>
  </form>

  <div class="login-footer">MedFlow CRM v2.0 - Powered by AI</div>
</div>
</body>
</html>
