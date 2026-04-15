<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'MedFlow CRM')</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Instrument+Serif:ital@0;1&family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@stack('page_style')
<style>
:root {
  --bg-primary: #f8f9fb;
  --bg-secondary: #ffffff;
  --bg-tertiary: #f1f3f7;
  --bg-sidebar: #0a1628;
  --bg-sidebar-hover: #142240;
  --bg-sidebar-active: #1a2d52;
  --text-primary: #0f172a;
  --text-secondary: #475569;
  --text-tertiary: #94a3b8;
  --text-sidebar: #94a3b8;
  --text-sidebar-active: #ffffff;
  --accent: #2563eb;
  --accent-light: #dbeafe;
  --accent-dark: #1d4ed8;
  --success: #059669;
  --success-light: #d1fae5;
  --warning: #d97706;
  --warning-light: #fef3c7;
  --danger: #dc2626;
  --danger-light: #fee2e2;
  --info: #0891b2;
  --info-light: #cffafe;
  --border: #e2e8f0;
  --border-light: #f1f5f9;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
  --shadow-lg: 0 8px 30px rgba(0,0,0,0.08);
  --shadow-xl: 0 20px 60px rgba(0,0,0,0.1);
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --radius-xl: 20px;
  --sidebar-width: 260px;
  --topbar-height: 64px;
  --font-body: 'DM Sans', -apple-system, sans-serif;
  --font-display: 'Instrument Serif', Georgia, serif;
  --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; scroll-behavior: smooth; }
body { font-family: var(--font-body); background: var(--bg-primary); color: var(--text-primary); line-height: 1.6; overflow: hidden; height: 100vh; }

/* LAYOUT */
.app-layout { display: flex; height: 100vh; }

/* SIDEBAR */
.sidebar { width: var(--sidebar-width); background: var(--bg-sidebar); display: flex; flex-direction: column; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.06); z-index: 100; overflow: hidden; }
.sidebar-logo { padding: 24px 24px 20px; font-family: var(--font-display); font-size: 1.6rem; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.06); letter-spacing: -0.3px; flex-shrink: 0; }
.sidebar-logo span { color: var(--accent); }
.sidebar-user { padding: 14px 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
.sidebar-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #7c3aed); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 0.78rem; flex-shrink: 0; }
.sidebar-user-info { flex: 1; min-width: 0; }
.sidebar-user-name { color: #fff; font-size: 0.83rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-user-role { color: var(--text-sidebar); font-size: 0.72rem; text-transform: capitalize; }
.sidebar-nav { flex: 1; padding: 10px; overflow-y: auto; display: flex; flex-direction: column; }
.sidebar-nav::-webkit-scrollbar { width: 4px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
.sidebar-section { margin-bottom: 6px; }
.sidebar-section-title { padding: 8px 12px 4px; font-size: 0.62rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: rgba(148,163,184,0.5); }
.sidebar-item { display: flex; align-items: center; gap: 9px; padding: 9px 12px; border-radius: var(--radius-sm); color: var(--text-sidebar); cursor: pointer; transition: var(--transition); font-size: 0.86rem; font-weight: 400; position: relative; text-decoration: none; }
.sidebar-item:hover { background: var(--bg-sidebar-hover); color: #cbd5e1; }
.sidebar-item.active { background: var(--bg-sidebar-active); color: var(--text-sidebar-active); font-weight: 500; }
.sidebar-item.active::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 18px; background: var(--accent); border-radius: 0 3px 3px 0; }
.sidebar-item svg { width: 16px; height: 16px; flex-shrink: 0; opacity: 0.7; }
.sidebar-item.active svg { opacity: 1; }
.sidebar-badge { margin-left: auto; background: var(--accent); color: #fff; font-size: 0.68rem; padding: 1px 7px; border-radius: 10px; font-weight: 600; }
.sidebar-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 8px 0; }
.sidebar-bottom { margin-top: auto; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }

/* MAIN */
.main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

/* TOPBAR */
.topbar { height: var(--topbar-height); background: var(--bg-secondary); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 28px; gap: 16px; flex-shrink: 0; }
.topbar-breadcrumb { font-size: 0.8rem; color: var(--text-tertiary); white-space: nowrap; }
.topbar-breadcrumb strong { color: var(--text-primary); font-weight: 600; }
.topbar-search { flex: 1; max-width: 380px; margin-left: auto; position: relative; }
.topbar-search input { width: 100%; padding: 8px 14px 8px 36px; background: var(--bg-tertiary); border: 1px solid transparent; border-radius: var(--radius-md); font-size: 0.84rem; font-family: var(--font-body); color: var(--text-primary); transition: var(--transition); outline: none; }
.topbar-search input:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.topbar-search-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary); }
.topbar-search-icon svg { width: 15px; height: 15px; }
.topbar-actions { display: flex; align-items: center; gap: 8px; }
.topbar-icon-btn { width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); transition: var(--transition); position: relative; }
.topbar-icon-btn:hover { border-color: var(--accent); color: var(--accent); }
.topbar-icon-btn svg { width: 17px; height: 17px; }
.topbar-user { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: var(--radius-md); border: 1px solid var(--border); cursor: pointer; transition: var(--transition); }
.topbar-user:hover { border-color: var(--accent); }
.topbar-user-name { font-size: 0.82rem; font-weight: 500; }
.topbar-user-role { font-size: 0.72rem; color: var(--text-tertiary); }

/* PAGE */
.page-container { flex: 1; overflow-y: auto; padding: 28px; scroll-behavior: smooth; }
.page-container::-webkit-scrollbar { width: 5px; }
.page-container::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 28px; gap: 16px; }
.page-title { font-family: var(--font-display); font-size: 1.9rem; font-weight: 400; letter-spacing: -0.3px; line-height: 1.2; }
.page-subtitle { color: var(--text-secondary); font-size: 0.88rem; margin-top: 3px; }
.header-actions { display: flex; gap: 10px; flex-shrink: 0; align-items: center; }

/* BUTTONS */
.btn { padding: 9px 18px; border-radius: var(--radius-sm); font-family: var(--font-body); font-size: 0.84rem; font-weight: 500; cursor: pointer; border: none; transition: var(--transition); display: inline-flex; align-items: center; gap: 7px; text-decoration: none; white-space: nowrap; }
.btn svg { width: 15px; height: 15px; flex-shrink: 0; }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); box-shadow: var(--shadow-md); }
.btn-secondary { background: #fff; color: var(--text-primary); border: 1px solid var(--border); }
.btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
.btn-success { background: var(--success); color: #fff; }
.btn-success:hover { background: #047857; }
.btn-danger { background: var(--danger); color: #fff; }
.btn-danger:hover { background: #b91c1c; }
.btn-ghost { background: transparent; color: var(--text-secondary); }
.btn-ghost:hover { color: var(--accent); background: var(--accent-light); }
.btn-sm { padding: 5px 12px; font-size: 0.78rem; }
.btn-icon { width: 34px; height: 34px; padding: 0; justify-content: center; border-radius: var(--radius-sm); flex-shrink: 0; }

/* CARDS */
.card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 22px; transition: var(--transition); }
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px; }
.card-title { font-weight: 600; font-size: 0.92rem; }
.card-subtitle { color: var(--text-secondary); font-size: 0.78rem; margin-top: 2px; }

/* KPI */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px 22px; transition: var(--transition); position: relative; overflow: hidden; }
.kpi-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.kpi-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.kpi-card:nth-child(1)::after { background: var(--accent); }
.kpi-card:nth-child(2)::after { background: var(--success); }
.kpi-card:nth-child(3)::after { background: var(--warning); }
.kpi-card:nth-child(4)::after { background: var(--info); }
.kpi-icon { width: 40px; height: 40px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.kpi-card:nth-child(1) .kpi-icon { background: var(--accent-light); color: var(--accent); }
.kpi-card:nth-child(2) .kpi-icon { background: var(--success-light); color: var(--success); }
.kpi-card:nth-child(3) .kpi-icon { background: var(--warning-light); color: var(--warning); }
.kpi-card:nth-child(4) .kpi-icon { background: var(--info-light); color: var(--info); }
.kpi-icon svg { width: 19px; height: 19px; }
.kpi-label { font-size: 0.78rem; color: var(--text-secondary); margin-bottom: 3px; font-weight: 500; }
.kpi-value { font-size: 1.7rem; font-weight: 700; line-height: 1.2; }
.kpi-change { display: inline-flex; align-items: center; gap: 3px; font-size: 0.74rem; font-weight: 500; margin-top: 5px; padding: 2px 7px; border-radius: 20px; }
.kpi-change.up { color: var(--success); background: var(--success-light); }
.kpi-change.neutral { color: var(--text-secondary); background: var(--bg-tertiary); }

/* GRIDS */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
.grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; }

/* TABLES */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { padding: 11px 16px; text-align: left; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-tertiary); border-bottom: 1px solid var(--border); background: var(--bg-tertiary); white-space: nowrap; }
th:first-child { border-radius: var(--radius-sm) 0 0 0; }
th:last-child { border-radius: 0 var(--radius-sm) 0 0; }
td { padding: 13px 16px; border-bottom: 1px solid var(--border-light); font-size: 0.86rem; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(37,99,235,0.015); }

/* BADGES */
.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 0.73rem; font-weight: 500; white-space: nowrap; }
.badge-blue { background: var(--accent-light); color: var(--accent); }
.badge-green { background: var(--success-light); color: var(--success); }
.badge-yellow { background: var(--warning-light); color: var(--warning); }
.badge-red { background: var(--danger-light); color: var(--danger); }
.badge-gray { background: var(--bg-tertiary); color: var(--text-secondary); }
.badge-cyan { background: var(--info-light); color: var(--info); }
.badge-purple { background: #f3e8ff; color: #7c3aed; }

/* AVATARS */
.avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.76rem; color: #fff; flex-shrink: 0; }
.avatar-sm { width: 28px; height: 28px; font-size: 0.68rem; }
.avatar-lg { width: 46px; height: 46px; font-size: 0.95rem; }

/* FORMS */
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 5px; color: var(--text-primary); }
.form-label .required { color: var(--danger); margin-left: 2px; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 9px 13px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-family: var(--font-body); font-size: 0.86rem; color: var(--text-primary); background: #fff; transition: var(--transition); outline: none; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.form-input.error, .form-select.error { border-color: var(--danger); }
.form-error { font-size: 0.76rem; color: var(--danger); margin-top: 4px; }
.form-hint { font-size: 0.76rem; color: var(--text-tertiary); margin-top: 4px; }
.form-textarea { resize: vertical; min-height: 90px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.form-section { margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
.form-section:last-child { border-bottom: none; margin-bottom: 0; }
.form-section-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 14px; color: var(--text-primary); }

/* FILTER BAR */
.filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
.filter-search-wrap { position: relative; flex: 1; min-width: 200px; }
.filter-search-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-tertiary); }
.filter-search { width: 100%; padding: 9px 13px 9px 34px; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.86rem; background: #fff; outline: none; font-family: var(--font-body); transition: var(--transition); }
.filter-search:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.filter-select { padding: 9px 13px; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.84rem; background: #fff; font-family: var(--font-body); outline: none; color: var(--text-primary); cursor: pointer; }

/* ALERTS */
.alert { padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.86rem; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 10px; }
.alert-success { background: var(--success-light); color: #065f46; border: 1px solid #a7f3d0; }
.alert-danger  { background: var(--danger-light);  color: #991b1b; border: 1px solid #fca5a5; }

/* EMPTY STATE */
.empty-state { text-align: center; padding: 52px 20px; color: var(--text-secondary); }
.empty-state svg { width: 44px; height: 44px; color: var(--text-tertiary); margin-bottom: 14px; }
.empty-state h3 { font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
.empty-state p { font-size: 0.84rem; }

/* ACTIVITY */
.activity-item { display: flex; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--border-light); }
.activity-item:last-child { border-bottom: none; }
.activity-dot { width: 7px; height: 7px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.activity-text { font-size: 0.83rem; color: var(--text-secondary); flex: 1; }
.activity-text strong { color: var(--text-primary); font-weight: 500; }
.activity-time { font-size: 0.74rem; color: var(--text-tertiary); margin-top: 1px; }

/* TABS */
.tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--border); margin-bottom: 18px; }
.tab { padding: 9px 16px; font-size: 0.84rem; color: var(--text-secondary); cursor: pointer; border-bottom: 2px solid transparent; transition: var(--transition); font-weight: 500; text-decoration: none; }
.tab:hover { color: var(--text-primary); }
.tab.active { color: var(--accent); border-bottom-color: var(--accent); }

/* DROPDOWN MENU */
.dropdown { position: relative; display: inline-block; }
.dropdown-menu { position: absolute; right: 0; top: calc(100% + 6px); background: #fff; border: 1px solid var(--border); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); min-width: 160px; z-index: 200; overflow: hidden; display: none; }
.dropdown-menu.open { display: block; }
.dropdown-item { display: flex; align-items: center; gap: 8px; padding: 9px 14px; font-size: 0.84rem; color: var(--text-primary); text-decoration: none; cursor: pointer; transition: var(--transition); }
.dropdown-item:hover { background: var(--bg-tertiary); color: var(--accent); }
.dropdown-item.danger { color: var(--danger); }
.dropdown-item.danger:hover { background: var(--danger-light); }
.dropdown-divider { height: 1px; background: var(--border); }

/* STATUS DOT */
.status-dot { display: inline-flex; align-items: center; gap: 5px; font-size: 0.82rem; }
.status-dot::before { content: ''; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.status-dot.active::before { background: var(--success); }
.status-dot.inactive::before { background: var(--text-tertiary); }
.status-dot.pending::before { background: var(--warning); }

/* ANIMATIONS */
@keyframes fadeSlideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-in { animation: fadeSlideUp 0.3s ease-out both; }

/* ═══════════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════════ */

/* ── Tablet (1100px) ─────────────────────────────────────── */
@media (max-width: 1100px) {
  .kpi-grid    { grid-template-columns: repeat(2, 1fr); }
  .grid-2-1    { grid-template-columns: 1fr; }
  .grid-3      { grid-template-columns: 1fr 1fr; }
}

/* ── Sidebar overlay (mobile) ────────────────────────────── */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.55);
  z-index: 299;
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
}
.sidebar-overlay.open { display: block; }

/* ── Mobile hamburger button ─────────────────────────────── */
.topbar-hamburger {
  display: none;
  width: 38px; height: 38px;
  align-items: center; justify-content: center;
  background: none;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  color: var(--text-secondary);
  flex-shrink: 0;
  transition: var(--transition);
}
.topbar-hamburger:hover { border-color: var(--accent); color: var(--accent); }
.topbar-hamburger svg  { width: 18px; height: 18px; }

/* ── Small tablet / large phone (768px) ──────────────────── */
@media (max-width: 768px) {

  /* Sidebar slides in from left as drawer */
  .sidebar {
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 300;
    transform: translateX(-100%);
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-xl);
  }
  .sidebar.open { transform: translateX(0); }

  /* Main content takes full width */
  .main-content { width: 100%; }
  body { overflow: hidden; }

  /* Show hamburger, hide topbar search */
  .topbar-hamburger { display: flex; }
  .topbar-search    { display: none; }

  /* Topbar padding */
  .topbar { padding: 0 16px; gap: 10px; }

  /* Page padding */
  .page-container { padding: 18px 16px; }

  /* Stack page header */
  .page-header { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 20px; }
  .header-actions { flex-wrap: wrap; width: 100%; }
  .header-actions .btn { flex: 1; min-width: 140px; justify-content: center; }

  /* Single-column grids */
  .grid-2   { grid-template-columns: 1fr; }
  .grid-3   { grid-template-columns: 1fr; }
  .grid-2-1 { grid-template-columns: 1fr; }
  .kpi-grid { grid-template-columns: 1fr 1fr; }

  /* Form rows collapse */
  .form-row   { grid-template-columns: 1fr; }
  .form-row-3 { grid-template-columns: 1fr; }

  /* Topbar user — hide text labels */
  .topbar-user-name,
  .topbar-user-role { display: none; }
  .topbar-user { padding: 5px; border: none; }

  /* Cards */
  .card { padding: 16px; }

  /* Page title smaller */
  .page-title { font-size: 1.5rem; }
}

/* ── Phone portrait (480px) ──────────────────────────────── */
@media (max-width: 480px) {
  .kpi-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
  .kpi-value { font-size: 1.35rem; }
  .kpi-card  { padding: 14px 16px; }
  .page-container { padding: 14px 12px; }
  .topbar { padding: 0 12px; height: 56px; }

  /* Buttons — full tap target */
  .btn { padding: 10px 14px; }
  .btn-sm { padding: 7px 12px; }

  /* Tables — allow horizontal scroll on mobile */
  .table-wrap { -webkit-overflow-scrolling: touch; }

  /* Filter bar wraps neatly */
  .filter-bar { gap: 8px; }
  .filter-select { font-size: 0.82rem; padding: 8px 10px; }
}

/* RTL SUPPORT */
[dir="rtl"] body { font-family: 'Cairo', var(--font-body); }
[dir="rtl"] .sidebar { border-right: none; border-left: 1px solid rgba(255,255,255,0.06); }
[dir="rtl"] .sidebar-item.active::before { left: auto; right: 0; border-radius: 3px 0 0 3px; }
[dir="rtl"] .sidebar-badge { margin-left: 0; margin-right: auto; }
[dir="rtl"] th { text-align: right; }
[dir="rtl"] .topbar-search { margin-left: 0; margin-right: auto; }
[dir="rtl"] .topbar-search input { padding: 8px 36px 8px 14px; }
[dir="rtl"] .topbar-search-icon { left: auto; right: 11px; }
[dir="rtl"] .page-header { flex-direction: row-reverse; }
[dir="rtl"] .filter-bar { flex-direction: row-reverse; }
[dir="rtl"] .form-row, [dir="rtl"] .form-row-3 { direction: rtl; }
</style>
</head>
<body>
{{-- Mobile sidebar overlay --}}
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-layout">

  {{-- SIDEBAR --}}
  <aside class="sidebar">
    <div class="sidebar-logo">Med<span>Flow</span></div>

    <div class="sidebar-user">
      <div class="sidebar-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}</div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">{{ Auth::user()->first_name ?? Auth::user()->name }}</div>
        <div class="sidebar-user-role">{{ ucfirst(str_replace('_',' ', Auth::user()->employee_type ?? Auth::user()->role ?? 'user')) }}</div>
      </div>
    </div>

    <nav class="sidebar-nav">
    @php $navGroup = Auth::user()->navGroup(); $apptToday = \App\Models\Appointment::whereDate('scheduled_at', today())->count(); @endphp

    {{-- ========================================================
         SUPER ADMIN NAV
    ======================================================== --}}
    @if($navGroup === 'admin')

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Overview') }}</div>
        <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          {{ __('Dashboard') }}
        </a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Clinical') }}</div>
        <a href="{{ route('patients.index') }}" class="sidebar-item {{ request()->routeIs('patients.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          {{ __('Patients') }}
          <span class="sidebar-badge">{{ \App\Models\Patient::count() }}</span>
        </a>
        <a href="{{ route('appointments.index') }}" class="sidebar-item {{ request()->routeIs('appointments.index') || request()->routeIs('appointments.kanban') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ __('Appointments') }}
          <span class="sidebar-badge">{{ $apptToday }}</span>
        </a>
        <a href="{{ route('leads.index') }}" class="sidebar-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          {{ __('Leads') }}
        </a>
        <a href="{{ route('followups.index') }}" class="sidebar-item {{ request()->routeIs('followups.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.45 2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
          {{ __('Follow-ups') }}
        </a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Admin Panel') }}</div>
        <a href="{{ route('admin.index') }}" class="sidebar-item {{ request()->routeIs('admin.index') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          {{ __('Admin Panel') }}
        </a>
        <a href="{{ route('admin.branches.index') }}" class="sidebar-item {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          {{ __('Branches') }}
          <span class="sidebar-badge">{{ \App\Models\Branch::count() }}</span>
        </a>
        <a href="{{ route('admin.employees.index') }}" class="sidebar-item {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
          {{ __('Staff Accounts') }}
          <span class="sidebar-badge">{{ \App\Models\User::whereNotNull('company_id')->where('employment_status','active')->count() }}</span>
        </a>
        <a href="{{ route('admin.roles') }}" class="sidebar-item {{ request()->routeIs('admin.roles') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          {{ __('Roles') }}
        </a>
        <a href="{{ route('admin.activity-logs') }}" class="sidebar-item {{ request()->routeIs('admin.activity-logs') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          {{ __('Activity Logs') }}
        </a>
        <a href="{{ route('inventory.index') }}" class="sidebar-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/><polyline points="12 22.08 12 17"/></svg>
          {{ __('Inventory') }}
        </a>
        <a href="{{ route('packages.index') }}" class="sidebar-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 4v16"/><path d="M3 10h18"/><path d="M14 14h4"/></svg>
          {{ __('Packages') }}
        </a>
        <a href="{{ route('admin.import.index') }}" class="sidebar-item {{ request()->routeIs('admin.import.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          {{ __('Data Import') }}
        </a>
        <a href="{{ route('admin.settings') }}" class="sidebar-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          {{ __('Settings') }}
        </a>
      </div>

    {{-- ========================================================
         SECRETARY NAV — focused on front desk operations
    ======================================================== --}}
    @elseif($navGroup === 'secretary')

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Front Desk') }}</div>
        <a href="{{ route('front-desk') }}" class="sidebar-item {{ request()->routeIs('front-desk') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          {{ __('Front Desk') }}
          @if($apptToday > 0)<span class="sidebar-badge">{{ $apptToday }}</span>@endif
        </a>
        <a href="{{ route('appointments.create') }}" class="sidebar-item {{ request()->routeIs('appointments.create') ? 'active' : '' }}" style="color:#93c5fd;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          {{ __('New Appointment') }}
        </a>
        <a href="{{ route('appointments.kanban') }}" class="sidebar-item {{ request()->routeIs('appointments.kanban') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
          {{ __('Kanban Board') }}
        </a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Patients') }}</div>
        <a href="{{ route('patients.create') }}" class="sidebar-item {{ request()->routeIs('patients.create') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
          {{ __('Register Patient') }}
        </a>
        <a href="{{ route('patients.index') }}" class="sidebar-item {{ request()->routeIs('patients.index') || request()->routeIs('patients.show') || request()->routeIs('patients.edit') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          {{ __('All Patients') }}
        </a>
        <a href="{{ route('appointments.index') }}" class="sidebar-item {{ request()->routeIs('appointments.index') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ __('Appointment Schedule') }}
        </a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('My Tasks') }}</div>
        <a href="{{ route('followups.index') }}" class="sidebar-item {{ request()->routeIs('followups.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.45 2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
          {{ __('Follow-ups') }}
        </a>
        <a href="{{ route('leads.index') }}" class="sidebar-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          {{ __('Leads') }}
        </a>
      </div>

    {{-- ========================================================
         BRANCH MANAGER NAV
    ======================================================== --}}
    @elseif($navGroup === 'manager')

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('My Branch') }}</div>
        <a href="{{ route('operations') }}" class="sidebar-item {{ request()->routeIs('operations') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          {{ __('Operations Board') }}
        </a>
        <a href="{{ route('front-desk') }}" class="sidebar-item {{ request()->routeIs('front-desk') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>
          {{ __('Front Desk') }}
        </a>
        <a href="{{ route('appointments.kanban') }}" class="sidebar-item {{ request()->routeIs('appointments.kanban') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
          {{ __('Kanban') }}
        </a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Clinical') }}</div>
        <a href="{{ route('patients.index') }}" class="sidebar-item {{ request()->routeIs('patients.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          {{ __('Patients') }}
        </a>
        <a href="{{ route('appointments.index') }}" class="sidebar-item {{ request()->routeIs('appointments.index') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ __('Appointments') }}
          <span class="sidebar-badge">{{ $apptToday }}</span>
        </a>
        <a href="{{ route('appointments.create') }}" class="sidebar-item {{ request()->routeIs('appointments.create') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          {{ __('New Appointment') }}
        </a>
        <a href="{{ route('review-queue') }}" class="sidebar-item {{ request()->routeIs('review-queue') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg>
          {{ __('Review Queue') }}
        </a>
        <a href="{{ route('finance') }}" class="sidebar-item {{ request()->routeIs('finance') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          {{ __('Finance') }}
        </a>
        <a href="{{ route('inventory.index') }}" class="sidebar-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/><polyline points="12 22.08 12 17"/></svg>
            {{ __('Inventory') }}
          </a>
          <a href="{{ route('packages.index') }}" class="sidebar-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 4v16"/><path d="M3 10h18"/><path d="M14 14h4"/></svg>
            {{ __('Packages') }}
          </a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Operations') }}</div>
        <a href="{{ route('leads.index') }}" class="sidebar-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          {{ __('Leads') }}
        </a>
        <a href="{{ route('followups.index') }}" class="sidebar-item {{ request()->routeIs('followups.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6.31-6.31 19.79 19.79 0 0 1-3.07-8.63 2 2 0 0 1 1.98-2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
          {{ __('Follow-ups') }}
        </a>
      </div>

    {{-- ========================================================
         TECHNICIAN NAV
    ======================================================== --}}
    @elseif($navGroup === 'technician')

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('My Work') }}</div>
        <a href="{{ route('my-queue') }}" class="sidebar-item {{ request()->routeIs('my-queue') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          {{ __('My Queue') }}
          <span class="sidebar-badge">{{ $apptToday }}</span>
        </a>
        <a href="{{ route('appointments.kanban') }}" class="sidebar-item {{ request()->routeIs('appointments.kanban') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
          {{ __('Kanban View') }}
        </a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Patients') }}</div>
        <a href="{{ route('patients.index') }}" class="sidebar-item {{ request()->routeIs('patients.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          {{ __('Patient Profiles') }}
        </a>
        <a href="{{ route('followups.index') }}" class="sidebar-item {{ request()->routeIs('followups.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6.31-6.31 19.79 19.79 0 0 1-3.07-8.63 2 2 0 0 1 1.98-2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
          {{ __('Follow-ups') }}
        </a>
      </div>

    {{-- ========================================================
         DOCTOR / NURSE NAV
    ======================================================== --}}
    @elseif($navGroup === 'doctor')

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Clinical') }}</div>
        <a href="{{ route('review-queue') }}" class="sidebar-item {{ request()->routeIs('review-queue') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          {{ __('Review Queue') }}
        </a>
        <a href="{{ route('my-queue') }}" class="sidebar-item {{ request()->routeIs('my-queue') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/></svg>
          {{ __('My Consultations') }}
          <span class="sidebar-badge">{{ $apptToday }}</span>
        </a>
        <a href="{{ route('appointments.kanban') }}" class="sidebar-item {{ request()->routeIs('appointments.kanban') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
          {{ __('Kanban') }}
        </a>
        <a href="{{ route('patients.index') }}" class="sidebar-item {{ request()->routeIs('patients.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          {{ __('Patients') }}
        </a>
        <a href="{{ route('followups.index') }}" class="sidebar-item {{ request()->routeIs('followups.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6.31-6.31 19.79 19.79 0 0 1-3.07-8.63 2 2 0 0 1 1.98-2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
          {{ __('Follow-ups') }}
        </a>
      </div>

    {{-- ========================================================
         FINANCE NAV
    ======================================================== --}}
    @elseif($navGroup === 'finance')

      <div class="sidebar-section">
        <div class="sidebar-section-title">{{ __('Finance') }}</div>
        <a href="{{ route('finance') }}" class="sidebar-item {{ request()->routeIs('finance') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          {{ __('Finance Dashboard') }}
        </a>
        <a href="{{ route('inventory.index') }}" class="sidebar-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/><polyline points="12 22.08 12 17"/></svg>
          {{ __('Inventory') }}
        </a>
        <a href="{{ route('patients.index') }}" class="sidebar-item {{ request()->routeIs('patients.*') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          {{ __('Patients') }}
        </a>
        <a href="{{ route('appointments.index') }}" class="sidebar-item {{ request()->routeIs('appointments.index') ? 'active' : '' }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ __('Appointments') }}
        </a>
      </div>

    @endif

    {{-- AI — always shown in sidebar --}}
    <div class="sidebar-divider"></div>
    <a href="{{ route('ai.page') }}" class="sidebar-item {{ request()->routeIs('ai.*') ? 'active' : '' }}"
      style="{{ request()->routeIs('ai.*') ? '' : 'background:linear-gradient(135deg,rgba(37,99,235,.08),rgba(124,58,237,.08));' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
      <span style="background:linear-gradient(90deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:600;">{{ __('MedFlow AI') }}</span>
      <span class="sidebar-badge" style="background:linear-gradient(135deg,#2563eb,#7c3aed);margin-left:auto;font-size:.62rem;">✨ NEW</span>
    </a>

    {{-- SIGN OUT — always shown --}}
      <div class="sidebar-bottom">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="sidebar-item" style="width:100%;background:none;border:none;text-align:left;cursor:pointer;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            {{ __('Sign Out') }}
          </button>
        </form>
      </div>
    </nav>
  </aside>

  {{-- MAIN CONTENT --}}
  <div class="main-content">
    {{-- TOPBAR --}}
    <header class="topbar">
      {{-- Hamburger — mobile only --}}
      <button class="topbar-hamburger" id="sidebar-toggle" aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topbar-breadcrumb">MedFlow / <strong>@yield('breadcrumb', 'Dashboard')</strong></div>
      <div class="topbar-search">
        <span class="topbar-search-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input type="text" placeholder="{{ __('Search...') }}">
      </div>
      <div class="topbar-actions">
        <button class="topbar-icon-btn" title="{{ __('Notifications') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </button>
        <form method="POST" action="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" style="display:inline;">
          @csrf
          <button type="submit" class="topbar-icon-btn" title="{{ __('Language') }}" style="font-size:0.72rem;font-weight:600;width:auto;padding:0 10px;">
            {{ app()->getLocale() === 'ar' ? 'EN' : 'ع' }}
          </button>
        </form>
        <a href="{{ route('ai.page') }}" class="topbar-icon-btn" title="{{ __('MedFlow AI Assistant') }}" style="border-color:rgba(124,58,237,.25);background:linear-gradient(135deg,rgba(37,99,235,.08),rgba(124,58,237,.08));color:#7c3aed;text-decoration:none;position:relative;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
          <span style="position:absolute;top:6px;right:6px;width:7px;height:7px;background:#7c3aed;border-radius:50%;animation:aiPulse 2.5s infinite;"></span>
        </a>
        <div class="topbar-user">
          <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#2563eb,#7c3aed);">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 2)) }}</div>
          <div>
            <div class="topbar-user-name">{{ Auth::user()->first_name ?? Auth::user()->name }}</div>
            <div class="topbar-user-role">{{ Auth::user()->role ?? '' }}</div>
          </div>
        </div>
      </div>
    </header>

    {{-- PAGE CONTENT --}}
    <div class="page-container">

      {{-- Flash messages --}}
      @if (session('success'))
        <div class="alert alert-success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;margin-top:1px;"><polyline points="20 6 9 17 4 12"/></svg>
          {{ session('success') }}
        </div>
      @endif
      @if (session('error'))
        <div class="alert alert-danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ session('error') }}
        </div>
      @endif

      @yield('content')
    </div>
  </div>

</div>

<style>
@keyframes aiPulse {
  0%,100% { opacity:1; }
  50%      { opacity:.3; }
}
</style>

<script>
// ── Dropdown toggle ────────────────────────────────────────
document.querySelectorAll('[data-toggle="dropdown"]').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const menu = btn.nextElementSibling;
    document.querySelectorAll('.dropdown-menu.open').forEach(m => { if (m !== menu) m.classList.remove('open'); });
    menu.classList.toggle('open');
  });
});
document.addEventListener('click', () => document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open')));

// ── Mobile sidebar drawer ──────────────────────────────────
(function () {
  const toggle   = document.getElementById('sidebar-toggle');
  const sidebar  = document.querySelector('.sidebar');
  const overlay  = document.getElementById('sidebar-overlay');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (toggle)  toggle.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);

  // Close sidebar when a nav link is tapped on mobile
  sidebar && sidebar.querySelectorAll('a.sidebar-item').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
  });
})();
</script>
@stack('scripts')
</body>
</html>
