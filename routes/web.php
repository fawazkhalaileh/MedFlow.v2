<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\FollowUpController;
use Illuminate\Support\Facades\Route;

// --- Public ---
Route::get('/', fn() => redirect()->route('dashboard'));

// --- Auth ---
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin Portal
    Route::resource('branches',  BranchController::class);
    Route::resource('employees', EmployeeController::class);

    // Clinical
    Route::resource('customers',    CustomerController::class)->only(['index', 'show']);
    Route::resource('appointments', AppointmentController::class)->only(['index']);

    // Operations
    Route::get('/leads',       [LeadController::class,     'index'])->name('leads.index');
    Route::get('/follow-ups',  [FollowUpController::class,  'index'])->name('followups.index');
});
