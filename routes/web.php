<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// PUBLIC
// -----------------------------------------------------------------------
Route::get('/', fn() => redirect()->route('login'));

// -----------------------------------------------------------------------
// GUEST ONLY
// -----------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// -----------------------------------------------------------------------
// ALL AUTHENTICATED USERS
// -----------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Smart redirect — role-aware landing
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // -----------------------------------------------------------------------
    // ADMIN AREA — system_admin only
    // (middleware bypasses for role=admin / employee_type=system_admin)
    // -----------------------------------------------------------------------
    Route::middleware('role:system_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/',             [AdminController::class, 'index'])->name('index');
        Route::resource('branches',  BranchController::class);
        Route::resource('employees', EmployeeController::class);
        Route::get('roles',         [AdminController::class, 'roles'])->name('roles');
        Route::get('settings',      [AdminController::class, 'settings'])->name('settings');
        Route::get('activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs');
    });

    // -----------------------------------------------------------------------
    // CLINICAL — secretary, technician, doctor, nurse, branch_manager
    // -----------------------------------------------------------------------
    Route::middleware('role:secretary,technician,doctor,nurse,branch_manager,finance')->group(function () {

        // Patients — full CRUD for secretary+, view-only enforced in controller
        Route::resource('patients', PatientController::class);

        // Patient JSON search (used by appointment booking autocomplete)
        Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');

        // Appointments — index + create/store for secretary+
        Route::get('/appointments',        [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/kanban', [AppointmentController::class, 'kanban'])->name('appointments.kanban');

        // Notes
        Route::post('/notes',          [NoteController::class, 'store'])->name('notes.store');
        Route::put('/notes/{note}',    [NoteController::class, 'update'])->name('notes.update');
        Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

        // Follow-ups
        Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('followups.index');

        // Leads
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    });

    // Secretary + branch_manager: can create / edit appointments
    Route::middleware('role:secretary,branch_manager')->group(function () {
        Route::get('/appointments/create',  [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments',        [AppointmentController::class, 'store'])->name('appointments.store');
        Route::patch('/appointments/{appointment}/status', [WorkspaceController::class, 'updateAppointmentStatus'])->name('appointments.status');
        Route::patch('/appointments/{appointment}/checkin', [AppointmentController::class, 'checkIn'])->name('appointments.checkin');
    });

    // -----------------------------------------------------------------------
    // ROLE WORKSPACES
    // -----------------------------------------------------------------------
    Route::get('/front-desk',   [WorkspaceController::class, 'frontDesk'])->name('front-desk')
        ->middleware('role:secretary,branch_manager');

    Route::get('/my-queue',     [WorkspaceController::class, 'myQueue'])->name('my-queue')
        ->middleware('role:technician,doctor,nurse');

    Route::get('/operations',   [WorkspaceController::class, 'operations'])->name('operations')
        ->middleware('role:branch_manager');

    Route::get('/review-queue', [WorkspaceController::class, 'reviewQueue'])->name('review-queue')
        ->middleware('role:doctor,nurse,branch_manager');

    Route::get('/finance',      [WorkspaceController::class, 'finance'])->name('finance')
        ->middleware('role:finance,branch_manager');

    // Technician + doctor can also update status from their queues
    Route::patch('/appointments/{appointment}/status', [WorkspaceController::class, 'updateAppointmentStatus'])
        ->name('appointments.status')
        ->middleware('role:technician,doctor,nurse,branch_manager,secretary')
        ->withoutMiddleware('role:secretary,branch_manager'); // allow both groups
});
