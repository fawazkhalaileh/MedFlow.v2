# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full dev stack (Laravel server + queue worker + Pail logs + Vite HMR)
composer run dev
# or equivalently:
npm run dev

# Production build
npm run build

# Run PHPUnit tests
composer run test
php artisan test
php artisan test --filter=TestClassName   # single test class

# First-time setup (install deps, generate key, migrate, build)
composer run setup
```

## Architecture

**MedFlow CRM** — a Laravel 12 / Blade / Tailwind clinical management system for laser clinics. Server-rendered (SSR) with minimal JS. Authentication is session-based.

### Role-Based Workspaces

The app routes authenticated users to role-specific workspaces via `DashboardController` → `CheckRole` middleware:

| Role | Workspace URL | Purpose |
|---|---|---|
| `secretary` | `/front-desk` | Patient check-in, appointment booking |
| `technician` / `doctor` / `nurse` | `/my-queue` | Treatment queue |
| `branch_manager` | `/operations` | Branch operations dashboard |
| `doctor` / `nurse` | `/review-queue` | Clinical review |
| `finance` | `/finance` | Finance workspace |
| `system_admin` / `branch_manager` | `/admin/*` | Admin panel |

Roles live on the `User` model as `employee_type` (coarse-grained) and `role` (fine-grained). `CheckRole` middleware supports comma-separated multi-role gates: `middleware('role:secretary,technician')`. System admins bypass all role checks.

### Branch Scoping (Multi-Tenancy)

Data is scoped to `branch_id`. Branch managers see only their branch; system admins see all branches. Always filter queries by `branch_id` unless the context is explicitly system-wide.

### Key Models

- `User` — staff; `employee_type` + `role` fields drive RBAC
- `Patient`, `Appointment`, `Lead`, `FollowUp`, `Note` — clinical domain
- `Branch`, `Company` — multi-tenant structure
- `ClinicalFlag` — flags on patient records
- `Role`, `Permission` — RBAC tables
- `ActivityLog`, `ImportLog` — audit trail

### Routing

All routes are in `routes/web.php`. RESTful resource routes plus role-scoped prefix groups (`/admin`, `/front-desk`, `/my-queue`, etc.). No API routes — everything is web/Blade.

### Frontend

Blade templates in `resources/views/` organized by domain (`patients/`, `appointments/`, `admin/`, `employees/`, etc.). Main layout: `resources/views/layouts/app.blade.php`. CSS is Tailwind 4; JS is minimal Axios-only (no SPA framework). Vite bundles assets.

### Architecture Reference

`ARCHITECTURE_V3_RBAC.txt` is the authoritative spec for the RBAC system, permission matrices, branch scoping rules, and implementation roadmap. Read it before making changes to roles, permissions, or middleware.
