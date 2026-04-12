<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ClinicalFlagController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\AiController;
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

    // Smart redirect: DashboardController redirects to role workspace
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // -------------------------------------------------------------------
    // ADMIN AREA — system_admin / role=admin only
    // -------------------------------------------------------------------
    Route::middleware('role:system_admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/',             [AdminController::class,  'index'])->name('index');
            Route::get('roles',         [AdminController::class,  'roles'])->name('roles');
            Route::get('settings',      [AdminController::class,  'settings'])->name('settings');
            Route::get('activity-logs', [AdminController::class,  'activityLogs'])->name('activity-logs');
            Route::resource('branches',  BranchController::class);
            Route::resource('employees', EmployeeController::class);

            Route::prefix('import')->name('import.')->group(function () {
                Route::get('/',                [ImportController::class, 'index'])->name('index');
                Route::post('/upload',         [ImportController::class, 'upload'])->name('upload');
                Route::get('/preview',         [ImportController::class, 'preview'])->name('preview');
                Route::post('/validate',       [ImportController::class, 'validate_import'])->name('validate');
                Route::get('/confirm',         [ImportController::class, 'confirm'])->name('confirm');
                Route::post('/execute',        [ImportController::class, 'execute'])->name('execute');
                Route::get('/logs/{log}',      [ImportController::class, 'show'])->name('show');
                Route::get('/template/{type}', [ImportController::class, 'downloadTemplate'])->name('template');
            });
        });

    // -------------------------------------------------------------------
    // CLINICAL ROUTES — all clinical staff
    // -------------------------------------------------------------------
    $clinical = 'role:secretary,technician,doctor,nurse,branch_manager,finance';

    // Patient search must be defined BEFORE the resource to avoid route conflict
    Route::get('/patients/search', [PatientController::class, 'search'])
        ->name('patients.search')
        ->middleware($clinical);

    Route::resource('patients', PatientController::class)
        ->middleware($clinical);

    // Appointments index + kanban (read for all clinical)
    Route::get('/appointments',        [AppointmentController::class, 'index'])->name('appointments.index')->middleware($clinical);
    Route::get('/appointments/kanban', [AppointmentController::class, 'kanban'])->name('appointments.kanban')->middleware($clinical);

    // Appointment booking — secretary and branch_manager only
    Route::middleware('role:secretary,branch_manager')->group(function () {
        Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments',       [AppointmentController::class, 'store'])->name('appointments.store');
    });

    // Appointment status update — all clinical staff (technician moves cards, secretary checks in, etc.)
    Route::patch(
        '/appointments/{appointment}/status',
        [WorkspaceController::class, 'updateAppointmentStatus']
    )->name('appointments.status')->middleware($clinical);

    Route::patch(
        '/appointments/{appointment}/checkin',
        [AppointmentController::class, 'checkIn']
    )->name('appointments.checkin')->middleware('role:secretary,branch_manager');

    // Notes
    Route::post('/notes',          [NoteController::class, 'store'])->name('notes.store')->middleware($clinical);
    Route::put('/notes/{note}',    [NoteController::class, 'update'])->name('notes.update')->middleware($clinical);
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy')->middleware($clinical);

    // Follow-ups
    Route::middleware($clinical)->group(function () {
        Route::get('/follow-ups',                        [FollowUpController::class, 'index'])->name('followups.index');
        Route::post('/follow-ups',                       [FollowUpController::class, 'store'])->name('followups.store');
        Route::put('/follow-ups/{followUp}',             [FollowUpController::class, 'update'])->name('followups.update');
        Route::patch('/follow-ups/{followUp}/complete',  [FollowUpController::class, 'complete'])->name('followups.complete');
        Route::delete('/follow-ups/{followUp}',          [FollowUpController::class, 'destroy'])->name('followups.destroy');
    });

    // Leads
    Route::middleware($clinical)->group(function () {
        Route::get('/leads',                    [LeadController::class, 'index'])->name('leads.index');
        Route::post('/leads',                   [LeadController::class, 'store'])->name('leads.store');
        Route::put('/leads/{lead}',             [LeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{lead}',          [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::post('/leads/{lead}/convert',    [LeadController::class, 'convert'])->name('leads.convert');
    });

    // Clinical flags master list — system_admin + branch_manager can manage
    Route::middleware('role:system_admin,branch_manager')->prefix('clinical-flags')->name('clinical-flags.')->group(function () {
        Route::get('/',        [ClinicalFlagController::class, 'index'])->name('index');
        Route::post('/',       [ClinicalFlagController::class, 'store'])->name('store');
        Route::put('/{flag}',  [ClinicalFlagController::class, 'update'])->name('update');
        Route::delete('/{flag}', [ClinicalFlagController::class, 'destroy'])->name('destroy');
    });

    // Assign / remove flags from patients — all clinical staff
    Route::post('/patients/{patient}/flags',          [ClinicalFlagController::class, 'assignToPatient'])->name('patient-flags.assign')->middleware($clinical);
    Route::delete('/patients/{patient}/flags/{flag}', [ClinicalFlagController::class, 'removeFromPatient'])->name('patient-flags.remove')->middleware($clinical);

    // -------------------------------------------------------------------
    // AI AGENT
    // -------------------------------------------------------------------
    Route::middleware($clinical)->prefix('ai')->name('ai.')->group(function () {
        Route::get('/',                                 [AiController::class, 'page'])->name('page');
        Route::post('/chat',                            [AiController::class, 'chat'])->name('chat');
        Route::post('/patient/{patient}/summary',       [AiController::class, 'patientSummary'])->name('patient.summary');
        Route::post('/patient/{patient}/suggest-note',  [AiController::class, 'suggestNote'])->name('patient.suggest-note');
    });

    // -------------------------------------------------------------------
    // ROLE WORKSPACES
    // -------------------------------------------------------------------
    Route::get('/front-desk',   [WorkspaceController::class, 'frontDesk'])->name('front-desk')
        ->middleware('role:secretary,branch_manager');

    Route::get('/my-queue',     [WorkspaceController::class, 'myQueue'])->name('my-queue')
        ->middleware('role:technician,doctor,nurse,branch_manager');

    Route::get('/operations',   [WorkspaceController::class, 'operations'])->name('operations')
        ->middleware('role:branch_manager');

    Route::get('/review-queue', [WorkspaceController::class, 'reviewQueue'])->name('review-queue')
        ->middleware('role:doctor,nurse,branch_manager');

    Route::get('/finance',      [WorkspaceController::class, 'finance'])->name('finance')
        ->middleware('role:finance,branch_manager');
});
