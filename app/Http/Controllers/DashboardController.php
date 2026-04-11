<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Role-based redirect: each role has a purpose-built workspace
        if (!$user->isSuperAdmin()) {
            return redirect($this->roleHome($user));
        }

        // Super Admin sees the full company-wide dashboard
        return $this->adminDashboard();
    }

    private function roleHome($user): string
    {
        return match($user->employee_type) {
            'branch_manager' => route('operations'),
            'secretary'      => route('front-desk'),
            'technician'     => route('my-queue'),
            'doctor', 'nurse'=> route('review-queue'),
            'finance'        => route('finance'),
            default          => route('dashboard'),
        };
    }

    private function adminDashboard()
    {
        $company   = Company::first();
        $companyId = $company?->id;
        $today     = today();

        $kpi = [
            'total_patients'     => Patient::where('company_id', $companyId)->count(),
            'active_patients'    => Patient::where('company_id', $companyId)->where('status', 'active')->count(),
            'today_appointments' => Appointment::whereDate('scheduled_at', $today)->count(),
            'today_completed'    => Appointment::whereDate('scheduled_at', $today)->where('status', 'completed')->count(),
            'active_plans'       => TreatmentPlan::where('company_id', $companyId)->where('status', 'active')->count(),
            'pending_followups'  => FollowUp::where('company_id', $companyId)->where('status', 'pending')->count(),
            'open_leads'         => Lead::where('company_id', $companyId)->whereIn('status', ['new', 'contacted'])->count(),
            'total_staff'        => User::where('company_id', $companyId)->where('employment_status', 'active')->count(),
        ];

        $todayAppointments = Appointment::with(['patient', 'service', 'assignedStaff'])
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        $recentPatients = Patient::with('branch')
            ->where('company_id', $companyId)
            ->latest()
            ->limit(5)
            ->get();

        $pendingFollowUps = FollowUp::with(['patient', 'assignedTo'])
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->withCount(['patients', 'appointments', 'staff'])
            ->get();

        return view('dashboard.index', compact(
            'kpi', 'todayAppointments', 'recentPatients', 'pendingFollowUps', 'branches'
        ));
    }
}
