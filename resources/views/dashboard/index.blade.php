<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - MedFlow CRM</title>
<style>
body { font-family: system-ui, sans-serif; background: #f8f9fb; color: #0f172a; margin: 0; padding: 40px; }
.card { background: #fff; border-radius: 12px; padding: 32px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
h1 { font-size: 1.5rem; margin-bottom: 8px; }
p  { color: #475569; margin-bottom: 20px; }
form button { background: #dc2626; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
</style>
</head>
<body>
<div class="card">
  <h1>Welcome, {{ Auth::user()->name }}</h1>
  <p>Role: <strong>{{ Auth::user()->role }}</strong></p>
  <p>MedFlow CRM Dashboard - full UI coming in the next phase.</p>
  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Sign Out</button>
  </form>
</div>
</body>
</html>
