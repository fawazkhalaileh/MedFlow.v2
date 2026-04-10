<?php

use App\Http\Controllers\Auth\LoginController;
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
    Route::get('/dashboard', fn() => view('dashboard.index'))->name('dashboard');

    // --- Quick data API endpoints for testing (return JSON) ---

    Route::prefix('api')->name('api.')->group(function () {

        // Company overview
        Route::get('/overview', function () {
            $company = \App\Models\Company::with('activeBranches')->first();
            return response()->json([
                'company'   => $company,
                'branches'  => $company->activeBranches()->withCount(['customers', 'appointments'])->get(),
                'staff'     => \App\Models\User::where('company_id', $company->id)->count(),
                'customers' => \App\Models\Customer::where('company_id', $company->id)->count(),
            ]);
        })->name('overview');

        // Customers
        Route::get('/customers', function () {
            return response()->json(
                \App\Models\Customer::with(['branch', 'assignedStaff', 'medicalInfo', 'treatmentPlans'])
                    ->latest()->paginate(20)
            );
        })->name('customers.index');

        Route::get('/customers/{customer}', function (\App\Models\Customer $customer) {
            $customer->load([
                'branch', 'assignedStaff', 'medicalInfo',
                'treatmentPlans.service',
                'appointments' => fn($q) => $q->latest('scheduled_at')->limit(10),
                'sessions' => fn($q) => $q->latest()->limit(10),
                'notes.createdBy',
                'followUps',
            ]);
            return response()->json($customer);
        })->name('customers.show');

        // Appointments
        Route::get('/appointments', function () {
            $date = request('date', today()->format('Y-m-d'));
            return response()->json(
                \App\Models\Appointment::with(['customer', 'service', 'assignedStaff', 'room'])
                    ->whereDate('scheduled_at', $date)
                    ->orderBy('scheduled_at')
                    ->get()
            );
        })->name('appointments.index');

        Route::get('/appointments/upcoming', function () {
            return response()->json(
                \App\Models\Appointment::with(['customer', 'service', 'assignedStaff'])
                    ->where('scheduled_at', '>=', now())
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->orderBy('scheduled_at')
                    ->limit(20)
                    ->get()
            );
        })->name('appointments.upcoming');

        // Treatment plans
        Route::get('/treatment-plans', function () {
            return response()->json(
                \App\Models\TreatmentPlan::with(['customer', 'service', 'branch'])
                    ->where('status', 'active')
                    ->latest()
                    ->paginate(20)
            );
        })->name('plans.index');

        // Dashboard stats
        Route::get('/stats', function () {
            $companyId = \App\Models\Company::first()->id;

            $today = today();

            return response()->json([
                'today' => [
                    'appointments_total'    => \App\Models\Appointment::whereDate('scheduled_at', $today)->count(),
                    'appointments_completed' => \App\Models\Appointment::whereDate('scheduled_at', $today)->where('status', 'completed')->count(),
                    'appointments_no_show'  => \App\Models\Appointment::whereDate('scheduled_at', $today)->where('status', 'no_show')->count(),
                    'sessions_completed'    => \App\Models\TreatmentSession::whereDate('created_at', $today)->count(),
                ],
                'overall' => [
                    'total_customers'       => \App\Models\Customer::where('company_id', $companyId)->count(),
                    'active_customers'      => \App\Models\Customer::where('company_id', $companyId)->where('status', 'active')->count(),
                    'active_plans'          => \App\Models\TreatmentPlan::where('company_id', $companyId)->where('status', 'active')->count(),
                    'follow_ups_pending'    => \App\Models\FollowUp::where('company_id', $companyId)->where('status', 'pending')->count(),
                    'open_leads'            => \App\Models\Lead::where('company_id', $companyId)->whereIn('status', ['new', 'contacted'])->count(),
                ],
                'staff' => \App\Models\User::where('company_id', $companyId)
                    ->where('employment_status', 'active')
                    ->select('id', 'first_name', 'last_name', 'employee_type', 'role')
                    ->get(),
            ]);
        })->name('stats');

        // Leads
        Route::get('/leads', function () {
            return response()->json(
                \App\Models\Lead::with(['assignedTo', 'branch'])->latest()->get()
            );
        })->name('leads.index');

        // Follow-ups
        Route::get('/follow-ups', function () {
            return response()->json(
                \App\Models\FollowUp::with(['customer', 'assignedTo'])
                    ->where('status', 'pending')
                    ->orderBy('due_date')
                    ->get()
            );
        })->name('followups.index');

        // Roles & permissions
        Route::get('/roles', function () {
            return response()->json(
                \App\Models\Role::with('permissions')->where('company_id', \App\Models\Company::first()->id)->get()
            );
        })->name('roles.index');

        // Services
        Route::get('/services', function () {
            return response()->json(
                \App\Models\Service::with('category')->where('is_active', true)->get()
            );
        })->name('services.index');
    });
});
