<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — MedFlow CRM</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<style>
/* ─── RESET ────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; }

/* ─── BASE ─────────────────────────────────────────────────── */
body {
  font-family: 'DM Sans', -apple-system, sans-serif;
  min-height: 100vh;
  min-height: 100dvh; /* dynamic viewport height — no browser-bar jank on mobile */
  background: #060d1f;
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: 1fr;
}

/* ═══════════════════════════════════════════════════════════
   LEFT PANEL — desktop only
═══════════════════════════════════════════════════════════ */
.panel-left {
  position: relative;
  background: linear-gradient(160deg, #0a1628 0%, #0f2447 55%, #0d3566 100%);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px 52px;
  overflow: hidden;
}

.panel-left::before {
  content: '';
  position: absolute;
  top: -80px; left: -80px;
  width: 380px; height: 380px;
  background: radial-gradient(circle, rgba(37,99,235,0.22) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}
.panel-left::after {
  content: '';
  position: absolute;
  bottom: -60px; right: -60px;
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(5,150,105,0.15) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}
.dots {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255,255,255,0.045) 1px, transparent 1px);
  background-size: 28px 28px;
  pointer-events: none;
}

.brand { position: relative; z-index: 1; }
.brand-logo {
  font-family: 'Instrument Serif', Georgia, serif;
  font-size: 2.2rem;
  color: #fff;
  letter-spacing: -0.5px;
  line-height: 1;
  margin-bottom: 10px;
}
.brand-logo em { color: #3b82f6; font-style: normal; }
.brand-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(37,99,235,0.15);
  border: 1px solid rgba(37,99,235,0.3);
  border-radius: 20px;
  padding: 4px 12px;
  color: #93c5fd;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.8px;
  text-transform: uppercase;
}
.brand-tag::before {
  content: '';
  width: 6px; height: 6px;
  background: #3b82f6;
  border-radius: 50%;
  display: inline-block;
}

.hero { position: relative; z-index: 1; }
.hero-headline {
  font-family: 'Instrument Serif', Georgia, serif;
  font-size: 3rem;
  color: #fff;
  line-height: 1.18;
  letter-spacing: -1px;
  margin-bottom: 20px;
}
.hero-headline em { font-style: italic; color: #93c5fd; }
.hero-sub {
  color: rgba(255,255,255,0.45);
  font-size: 0.9rem;
  line-height: 1.7;
  max-width: 340px;
  margin-bottom: 36px;
}
.stat-row { display: flex; gap: 28px; }
.stat-item { display: flex; flex-direction: column; gap: 3px; }
.stat-value {
  color: #fff;
  font-size: 1.55rem;
  font-weight: 700;
  letter-spacing: -0.5px;
  line-height: 1;
}
.stat-label {
  color: rgba(255,255,255,0.35);
  font-size: 0.73rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.6px;
}
.left-footer {
  position: relative;
  z-index: 1;
  color: rgba(255,255,255,0.2);
  font-size: 0.75rem;
}

/* Divider */
.panel-divider {
  position: fixed;
  left: 50%;
  top: 0; bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom,
    transparent 0%,
    rgba(255,255,255,0.07) 20%,
    rgba(255,255,255,0.07) 80%,
    transparent 100%);
  pointer-events: none;
  z-index: 10;
}

/* ═══════════════════════════════════════════════════════════
   RIGHT PANEL
═══════════════════════════════════════════════════════════ */
.panel-right {
  background: #060d1f;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 64px;
  position: relative;
  overflow-y: auto;
}
.panel-right::before {
  content: '';
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(37,99,235,0.06) 0%, transparent 70%);
  border-radius: 50%;
  pointer-events: none;
}

/* ── Mobile top-brand bar (hidden on desktop) ── */
.mobile-brand {
  display: none;
  text-align: center;
  margin-bottom: 32px;
}
.mobile-brand .brand-logo {
  font-family: 'Instrument Serif', Georgia, serif;
  font-size: 2rem;
  color: #fff;
  letter-spacing: -0.5px;
  margin-bottom: 8px;
}
.mobile-brand .brand-logo em { color: #3b82f6; font-style: normal; }
.mobile-brand .brand-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(37,99,235,0.15);
  border: 1px solid rgba(37,99,235,0.3);
  border-radius: 20px;
  padding: 4px 12px;
  color: #93c5fd;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.8px;
  text-transform: uppercase;
}
.mobile-brand .brand-tag::before {
  content: '';
  width: 5px; height: 5px;
  background: #3b82f6;
  border-radius: 50%;
  display: inline-block;
}

/* ─── FORM WRAPPER ─────────────────────────────────────────── */
.form-wrap {
  width: 100%;
  max-width: 380px;
  position: relative;
  z-index: 1;
  animation: fadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.form-heading { margin-bottom: 28px; }
.form-heading h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.5px;
  margin-bottom: 5px;
  line-height: 1.2;
}
.form-heading p {
  color: rgba(255,255,255,0.35);
  font-size: 0.87rem;
}

/* ─── ALERT ────────────────────────────────────────────────── */
.alert-error {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: rgba(220,38,38,0.08);
  border: 1px solid rgba(220,38,38,0.25);
  border-radius: 10px;
  color: #fca5a5;
  font-size: 0.83rem;
  padding: 12px 14px;
  margin-bottom: 22px;
  line-height: 1.5;
}
.alert-error svg { flex-shrink: 0; margin-top: 1px; color: #f87171; }

/* ─── FIELDS ───────────────────────────────────────────────── */
.field { margin-bottom: 16px; }
.field label {
  display: block;
  color: rgba(255,255,255,0.5);
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  margin-bottom: 7px;
}
.input-wrap { position: relative; }
.input-wrap svg.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,0.2);
  pointer-events: none;
  transition: color 0.2s;
  width: 16px; height: 16px;
}
.field input {
  width: 100%;
  /* 16px minimum prevents iOS auto-zoom on focus */
  font-size: 16px;
  padding: 13px 44px 13px 40px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
  outline: none;
  -webkit-appearance: none; /* removes iOS inner shadow */
  appearance: none;
}
.field input::placeholder { color: rgba(255,255,255,0.18); }
.field input:focus {
  border-color: rgba(59,130,246,0.55);
  background: rgba(59,130,246,0.06);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.input-wrap:has(input:focus) svg.input-icon { color: #3b82f6; }
.field input.is-invalid {
  border-color: rgba(220,38,38,0.5);
  background: rgba(220,38,38,0.05);
}
.field-error { color: #fca5a5; font-size: 0.77rem; margin-top: 5px; }

/* Password toggle — larger tap target */
.pwd-toggle {
  position: absolute;
  right: 0; top: 0; bottom: 0;
  width: 44px;
  background: none;
  border: none;
  color: rgba(255,255,255,0.25);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
  border-radius: 0 10px 10px 0;
}
.pwd-toggle:hover { color: rgba(255,255,255,0.55); }

/* ─── BOTTOM ROW ───────────────────────────────────────────── */
.bottom-row {
  display: flex;
  align-items: center;
  margin-top: 4px;
}
.remember-wrap { display: flex; align-items: center; gap: 9px; }
.remember-wrap input[type="checkbox"] {
  /* Larger tap target on mobile */
  width: 17px; height: 17px;
  accent-color: #3b82f6;
  cursor: pointer;
  flex-shrink: 0;
}
.remember-wrap label {
  color: rgba(255,255,255,0.4);
  font-size: 0.84rem;
  cursor: pointer;
  line-height: 1.4;
}

/* ─── SUBMIT BUTTON ────────────────────────────────────────── */
.login-btn {
  width: 100%;
  /* 48px+ ensures comfortable tap target */
  padding: 14px;
  margin-top: 20px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
}
.login-btn:hover {
  background: #1d4ed8;
  box-shadow: 0 6px 24px rgba(37,99,235,0.4);
  transform: translateY(-1px);
}
.login-btn:active { transform: translateY(0); box-shadow: none; }
.login-btn svg { width: 16px; height: 16px; flex-shrink: 0; }

/* ─── FOOTER ───────────────────────────────────────────────── */
.form-footer {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid rgba(255,255,255,0.05);
  display: flex;
  align-items: center;
  gap: 10px;
}
.form-footer-dot {
  width: 6px; height: 6px;
  background: #22c55e;
  border-radius: 50%;
  flex-shrink: 0;
  box-shadow: 0 0 6px rgba(34,197,94,0.6);
  animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
.form-footer p {
  color: rgba(255,255,255,0.2);
  font-size: 0.75rem;
  line-height: 1.4;
}

/* ═══════════════════════════════════════════════════════════
   RESPONSIVE BREAKPOINTS
═══════════════════════════════════════════════════════════ */

/* ── Tablet (1024px and below) — narrow the left panel ── */
@media (max-width: 1024px) {
  body { grid-template-columns: 5fr 7fr; }
  .panel-left { padding: 36px 36px; }
  .hero-headline { font-size: 2.4rem; }
  .panel-right { padding: 36px 40px; }
}

/* ── Small tablet / large phone landscape (768px and below) ── */
@media (max-width: 768px) {
  body {
    grid-template-columns: 1fr;
    grid-template-rows: auto;
    overflow-y: auto;
    min-height: 100dvh;
  }

  /* Hide desktop left panel and divider */
  .panel-left  { display: none; }
  .panel-divider { display: none; }

  /* Right panel fills entire screen with gradient */
  .panel-right {
    min-height: 100dvh;
    padding: 40px 28px;
    justify-content: flex-start;
    background: linear-gradient(160deg, #0a1628 0%, #0f2447 60%, #0d3566 100%);
  }
  .panel-right::before {
    width: 340px; height: 340px;
  }

  /* Show mobile brand header */
  .mobile-brand { display: block; }

  .form-wrap { max-width: 440px; }
  .form-heading { margin-bottom: 24px; }
  .form-heading h1 { font-size: 1.55rem; }
}

/* ── Phone portrait (430px and below) ── */
@media (max-width: 430px) {
  .panel-right { padding: 32px 20px 40px; }

  .form-wrap { max-width: 100%; }

  .form-heading h1 { font-size: 1.4rem; }

  /* Slightly larger inputs for fat fingers */
  .field input { padding: 14px 44px 14px 40px; }
  .login-btn   { padding: 15px; font-size: 1rem; }
}
</style>
</head>
<body>

<!-- ─── LEFT PANEL (desktop only) ───────────────────────── -->
<div class="panel-left">
  <div class="dots"></div>

  <div class="brand">
    <div class="brand-logo">Med<em>Flow</em></div>
    <div class="brand-tag">Clinic Management Platform</div>
  </div>

  <div class="hero">
    <h2 class="hero-headline">
      Clinical care,<br><em>streamlined</em><br>for your team.
    </h2>
    <p class="hero-sub">
      Manage appointments, patient records, treatment plans, and your entire care team — all in one place.
    </p>
    <div class="stat-row">
      <div class="stat-item">
        <span class="stat-value">9+</span>
        <span class="stat-label">Workflow stages</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">6</span>
        <span class="stat-label">Role types</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">100%</span>
        <span class="stat-label">Branch scoped</span>
      </div>
    </div>
  </div>

  <div class="left-footer">MedFlow CRM &copy; {{ date('Y') }}</div>
</div>

<!-- Divider (desktop only) -->
<div class="panel-divider"></div>

<!-- ─── RIGHT PANEL ──────────────────────────────────────── -->
<div class="panel-right">
  <div class="form-wrap">

    <!-- Mobile brand (shown only on small screens) -->
    <div class="mobile-brand">
      <div class="brand-logo">Med<em>Flow</em></div>
      <div class="brand-tag">Clinic Management Platform</div>
    </div>

    <div class="form-heading">
      <h1>Welcome back</h1>
      <p>Sign in to your workspace</p>
    </div>

    @if ($errors->any())
    <div class="alert-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
      @csrf

      <!-- Email -->
      <div class="field">
        <label for="email">Email address</label>
        <div class="input-wrap">
          <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/>
          </svg>
          <input
            type="email" id="email" name="email"
            value="{{ old('email') }}"
            placeholder="you@medflow.local"
            autocomplete="email"
            inputmode="email"
            class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
            required autofocus>
        </div>
        @error('email')<div class="field-error">{{ $message }}</div>@enderror
      </div>

      <!-- Password -->
      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input
            type="password" id="password" name="password"
            placeholder="••••••••"
            autocomplete="current-password"
            class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
            required>
          <button type="button" class="pwd-toggle" onclick="togglePwd()" aria-label="Toggle password visibility">
            <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        @error('password')<div class="field-error">{{ $message }}</div>@enderror
      </div>

      <!-- Remember me -->
      <div class="bottom-row">
        <div class="remember-wrap">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Keep me signed in</label>
        </div>
      </div>

      <button type="submit" class="login-btn">
        Sign In
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
        </svg>
      </button>
    </form>

    <div class="form-footer">
      <div class="form-footer-dot"></div>
      <p>Secure access — all data is branch-scoped and role-protected</p>
    </div>

  </div>
</div>

<script>
function togglePwd() {
  const input = document.getElementById('password');
  const icon  = document.getElementById('eye-icon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = `
      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
      <line x1="1" y1="1" x2="23" y2="23"/>`;
  } else {
    input.type = 'password';
    icon.innerHTML = `
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
      <circle cx="12" cy="12" r="3"/>`;
  }
}
</script>
</body>
</html>
