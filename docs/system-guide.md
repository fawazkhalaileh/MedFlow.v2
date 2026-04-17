# MedFlow CRM System Guide

Last updated: 2026-04-16

This document explains how MedFlow CRM currently works, what functions the system supports, which users exist, and what each role can do.

## 1. What MedFlow CRM Is

MedFlow CRM is a multi-branch clinic management system built with:

- Laravel 12
- PHP 8.2
- Blade UI
- strict role-based access control
- strict branch scoping for non-admin users
- soft delete for archive-style deletion
- audit logging for important business actions

The system is organized around operational, clinical, financial, package, inventory, reporting, and AI-assisted workflows.

## 2. How Users Reach the System

Users log in through the application login page.

After login, the system redirects users to a role-based home screen:

- `system_admin` goes to the admin dashboard
- `branch_manager` goes to the operations board
- `secretary` goes to the front desk workspace
- `technician` goes to the technician queue
- `doctor` and `nurse` go to the review queue
- `finance` goes to the finance workspace

The left sidebar changes based on role and shows only the main areas relevant to that role.

## 3. Branch Scoping and Security Model

MedFlow uses strict branch scoping:

- `system_admin` can see all branches
- all other users are scoped to their `primary_branch_id`
- branch-scoped users cannot access records from other branches
- reports follow the same branch rules
- compensation and commission calculations also follow the same scope

The main access helper is the user branch scope:

- super admin: no branch restriction
- all other users: only their primary branch

## 4. Main System Modules

### 4.1 Admin Module

Purpose:
- system-wide setup and governance

Functions:
- manage branches
- manage staff accounts
- view roles screen
- view system activity logs
- open settings
- run data import flows

### 4.2 Patient Module

Purpose:
- maintain patient records and profile history

Functions:
- register new patients
- edit patient profiles
- archive patients using soft delete
- assign staff
- store medical information
- add notes
- assign clinical flags
- track follow-ups
- upload patient attachments
- open full patient history timeline

Patient history currently supports:
- appointments
- appointment status changes
- treatment sessions
- device used
- treatment areas
- laser settings summary
- shots count
- duration
- technician notes
- doctor notes
- before and after observations
- recommendations
- package purchases
- package usage
- payments and receipts
- follow-ups
- attachments
- audit-friendly timestamps and authors

### 4.3 Appointment Module

Purpose:
- booking, scheduling, queueing, and status progression

Functions:
- create appointments
- view appointment list
- view kanban schedule
- check in patients
- update appointment status
- assign staff
- attach patient packages to appointments

Common statuses used across the app:
- booked
- scheduled
- confirmed
- arrived
- checked_in
- intake_complete
- assigned
- in_room
- in_treatment
- review_needed
- completed
- follow_up_needed
- no_show
- cancelled
- rescheduled

### 4.4 Front Desk Workspace

Purpose:
- reception workflow for today’s branch schedule

Functions:
- view today’s queue
- see appointments needing confirmation
- see personal pending follow-ups
- check patients in
- monitor room scheduling
- view unassigned appointments

### 4.5 Technician Workspace

Purpose:
- work queue for assigned clinical staff

Functions:
- see assigned appointments for today
- work through waiting, prep, in-session, and done groupings
- access patient profiles
- access follow-ups
- view self performance report through Reports

### 4.6 Doctor / Nurse Review Workspace

Purpose:
- supervise escalations and reviews

Functions:
- review appointments needing escalation
- monitor patients with pending consent
- review own consultations
- access patient profiles
- access follow-ups
- access reports allowed by role

### 4.7 Branch Operations Workspace

Purpose:
- branch-level operational management

Functions:
- monitor today’s branch pipeline
- review branch staff workload
- review alerts
- access front desk
- access finance
- access inventory
- access packages
- access reports

### 4.8 Finance Module

Purpose:
- collect payments and manage operational finance

Functions:
- record payments
- enforce no-refund payment-only policy in current implementation
- open and close cash register sessions
- calculate expected closing balance
- calculate cash sales and change returned
- view outstanding treatment plans
- open receipt PDFs
- access accounting reports
- access inventory reports
- access commission and compensation reports

### 4.9 Inventory Module

Purpose:
- branch inventory control

Functions:
- create inventory items
- add stock into batches
- record usage
- record waste
- track patient-linked usage
- track FEFO deduction
- create branch transfers
- approve, send, receive, or cancel transfers
- view low stock alerts
- view expiry alerts
- view recent movements

### 4.10 Package Module

Purpose:
- define service packages and sell them to patients

Functions:
- create package masters
- set original and final pricing at creation
- lock pricing after creation
- purchase packages for patients
- freeze and unfreeze packages
- attach patient packages to appointments
- automatically deduct usage on completed appointments
- track patient package remaining sessions

### 4.11 AI Module

Purpose:
- assist staff with summaries and documentation

Functions:
- AI chat page
- generate patient summary
- suggest notes for a patient

### 4.12 Reporting Module

Purpose:
- provide operational, financial, inventory, patient, performance, and compensation reporting

How to open reports:
- use the left sidebar and click `Reports`
- the reports hub is at `/reports`

Current report areas:
- Accounting Reports
- Patient Reports
- Inventory Reports
- Patient History export
- Technician Performance
- Commissions & Compensation

## 5. Reporting Capabilities

### 5.1 Accounting Reports

Functions:
- revenue by day, week, month, or custom period
- expense reports by date range
- payment summaries by method
- package sales summary
- outstanding balances
- branch profit-style summary
- CSV export
- PDF export

### 5.2 Patient Reports

Functions:
- patient visit frequency
- cancellation and no-show metrics
- package consumption status
- overdue follow-up tracking
- patients with no future booking
- top patients by visits
- top patients by spend
- active vs inactive counts
- first visit vs returning counts
- CSV export
- PDF export

### 5.3 Inventory Reports

Functions:
- current stock by item
- stock movement history
- stock usage by period
- stock deducted by session, service, or manual bucket
- expiry alerts
- low stock alerts
- branch inventory summary
- transfer history
- CSV export
- PDF export

### 5.4 Technician Performance Reports

Phase 2 added:
- sessions completed by technician
- services performed
- utilization by period
- revenue attributable to technician work
- package sales attributable to employee work
- package usage attributable to employee work
- branch filtering
- employee filtering
- CSV export
- PDF export

### 5.5 Commission and Compensation Reports

Phase 2 added:
- commissions by employee
- commissions by period
- fixed salary and commission combined totals
- work performed summary for each employee
- salary-only, commission-only, or salary-plus-commission support
- compensation profile entry
- commission rule entry
- auditable calculation snapshots
- CSV export
- PDF export

## 6. Compensation and Commission Logic

Phase 2 introduced a compensation engine designed to be auditable and reproducible.

### 6.1 Main Concepts

#### Employee Compensation Profile

Defines the base compensation setup for an employee:

- salary only
- commission only
- salary plus commission
- fixed salary amount
- optional branch-specific version
- optional effective date range

#### Employee Commission Rule

Defines how commission is calculated.

Supported rule ideas in the current implementation:
- percentage of completed service value
- percentage of package sale value
- percentage of package consumed value
- per-session commission
- fixed amount rule
- branch-specific override
- employee-specific override
- employee plus branch override

#### Work Attribution

This is the key audit object for Phase 2.

It answers:
- what employee worked on what item
- what was the source type
- what service was involved
- what patient was involved
- what value is attributable
- when it occurred

Sources currently attributed:
- completed treatment sessions
- patient package sales
- package consumption

#### Compensation Snapshot

This stores a generated period result:
- period start
- period end
- fixed salary
- commission total
- total due
- breakdown JSON
- generated timestamp
- generated by user

### 6.2 Attribution Sources Used

The system keeps performance attribution separate from receipt attribution.

Performance attribution currently uses:
- `treatment_sessions.technician_id`
- `patient_packages.purchased_by`
- `package_usages.used_by`
- related branch, patient, service, and period data

Not used as the primary source for compensation:
- `transactions.received_by`

This is intentional. Receiving money and doing the work are different responsibilities.

### 6.3 Salary and Commission Modes

The system supports:

- salary only
- commission only
- salary plus commission

Total due is:

`fixed salary + calculated commission`

### 6.4 Auditability

Calculations are auditable because:

- work attribution records are stored
- commission rules are stored
- compensation profiles are stored
- snapshots can be generated and saved
- activity log records snapshot generation

## 7. Roles and What Each Role Can Do

Below is the practical role summary based on current routes and workspaces.

### 7.1 System Admin

Scope:
- all branches
- all modules

Main capabilities:
- access admin dashboard
- manage branches
- manage employees
- access activity logs
- access import tools
- access patient, appointment, lead, follow-up, and AI areas
- access inventory
- access packages
- access all reports
- access all compensation and commission reporting

### 7.2 Branch Manager

Scope:
- own branch only

Main capabilities:
- operations board
- front desk
- appointment booking
- appointment check-in
- appointment status updates
- patient access
- follow-ups
- leads
- review queue
- finance workspace
- inventory workspace
- package management
- clinical flag management
- all branch-level reports
- technician performance reports
- commission and compensation reports for branch scope
- create compensation profiles and commission rules within allowed scope
- generate compensation snapshots within allowed scope

### 7.3 Secretary

Scope:
- own branch only

Main capabilities:
- front desk workspace
- patient search
- patient create, edit, show, and archive within scope
- appointment booking
- appointment check-in
- appointment status updates
- notes
- follow-ups
- leads
- patient reports
- patient history reports
- reports hub access

Notable limits:
- no finance workspace
- no inventory workspace
- no package management
- no commission management

### 7.4 Technician

Scope:
- own branch only

Main capabilities:
- my queue workspace
- appointment kanban access
- appointment status updates
- patient profile access within branch scope
- notes
- follow-ups
- patient reports
- patient history report access
- technician performance report

Special Phase 2 behavior:
- technicians are self-scoped in performance reporting
- if self-view is active, they only see their own performance data

Notable limits:
- no finance workspace
- no accounting reports
- no commission administration

### 7.5 Doctor

Scope:
- own branch only

Main capabilities:
- review queue
- my queue / consultations
- patient access
- appointment status updates
- notes
- follow-ups
- patient reports
- patient history reports
- technician performance reports where allowed by route
- reports hub access

Notable limits:
- no finance workspace
- no compensation administration

### 7.6 Nurse

Scope:
- own branch only

Main capabilities:
- same general access pattern as doctor for current routing
- review queue
- patient access
- appointment status updates
- notes
- follow-ups
- patient reports
- patient history reports
- technician performance reports where allowed by route

### 7.7 Finance

Scope:
- own branch only

Main capabilities:
- finance workspace
- payment recording
- cash register open and close
- receipt PDF generation
- patient and appointment visibility within allowed scope
- inventory workspace
- accounting reports
- inventory reports
- patient reports
- patient history reports
- technician performance reports
- commission and compensation reports
- create compensation profiles
- create commission rules
- generate compensation snapshots

Notable limits:
- cannot manage package master records unless also branch manager or admin
- cannot manage clinical flag masters

## 8. Current Major Workflows

### 8.1 Register a Patient

1. Secretary or clinical-access role opens patient registration.
2. Patient demographic and contact details are stored.
3. Medical information can be saved.
4. Assigned staff and branch are linked.
5. Patient profile becomes available.

### 8.2 Book an Appointment

1. Secretary or branch manager opens appointment create.
2. Select patient, service, branch, time, and staff.
3. Optional patient package may be attached.
4. Appointment appears in schedule and kanban.

### 8.3 Move an Appointment Through Clinic Workflow

1. Front desk checks in patient.
2. Staff update status through queue or kanban.
3. Completed appointments can trigger package usage deduction.
4. Status changes are audit logged and visible in patient history.

### 8.4 Record a Payment

1. Finance opens finance workspace.
2. Select plan and optional completed appointment.
3. Enter amount, amount received, and payment method.
4. Cash payments require an open register.
5. Transaction is stored.
6. Receipt number is created.
7. Treatment plan paid amount is updated.
8. Receipt PDF can be opened.

### 8.5 Record Inventory Usage

1. Finance or manager opens inventory.
2. Select branch inventory.
3. Enter used quantity and optionally wasted quantity.
4. Optionally attach patient.
5. FEFO deduction is performed.
6. Movement records are saved.
7. Low-stock and expiry logic update naturally from batches.

### 8.6 Sell and Use a Package

1. Manager or admin creates package master.
2. Package is sold to a patient.
3. Patient package is attached to appointment if relevant.
4. On completion, usage is deducted.
5. Package sale and package use can become work attribution sources for commission logic.

### 8.7 Generate Compensation Results

1. Finance or manager opens `Reports > Commissions & Compensation`.
2. Select branch, employee, and period.
3. System syncs work attribution for sessions, package sales, and package use.
4. Compensation profiles and commission rules are matched.
5. Commission is calculated per attribution row.
6. Total due is calculated as salary plus commission.
7. Optional snapshots can be generated and stored.

## 9. Reports Navigation Summary

Open reports from the left sidebar:

- `Reports` opens the hub
- from the hub, open:
  - Accounting Reports
  - Patient Reports
  - Inventory Reports
  - Technician Performance
  - Commissions & Compensation

Direct routes currently include:

- `/reports`
- `/reports/accounting`
- `/reports/patients`
- `/reports/inventory`
- `/reports/technician-performance`
- `/reports/commissions`

Patient history export/report route:

- `/reports/patients/{patient}/history`

## 10. Key Constraints and Design Principles

- branch isolation is strict
- admins can see all branches
- non-admin users are branch scoped
- accounting accuracy must not be weakened
- soft delete is preferred instead of destructive deletion
- important actions are audit logged
- reporting and exports must respect RBAC
- performance attribution is separate from receipt attribution
- compensation calculations must be reproducible

## 11. Current Limitations and Future Notes

The system is strong in branch-scoped workflows and reporting, but some areas are clearly designed for future expansion:

- multi-staff split attribution can be added later using explicit work attribution records
- technician compensation can be made more complex without redesigning the reporting layer
- payroll settlement workflows can be added on top of compensation snapshots
- more detailed role-specific policy classes can be added later if needed
- some UI text and localization still require ongoing refinement outside this document

## 12. Recommended Use of This Document

Use this guide as:

- onboarding material for new staff
- handoff documentation for developers
- a scope reference for future phases
- a permissions summary when testing roles

If the system changes, update this file first so the project always has a current operational reference.
