<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\Role;
use App\Models\TreatmentPlan;
use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $company = Company::first();

        $stats = [
            'branches'       => Branch::where('company_id', $company->id)->count(),
            'active_branches'=> Branch::where('company_id', $company->id)->where('status', 'active')->count(),
            'staff'          => User::where('company_id', $company->id)->where('employment_status', 'active')->count(),
            'patients'       => Patient::where('company_id', $company->id)->count(),
            'appointments_today' => Appointment::whereDate('scheduled_at', today())->count(),
            'active_plans'   => TreatmentPlan::where('company_id', $company->id)->where('status', 'active')->count(),
            'open_leads'     => Lead::where('company_id', $company->id)->whereIn('status', ['new', 'contacted'])->count(),
            'pending_followups' => FollowUp::where('company_id', $company->id)->where('status', 'pending')->count(),
        ];

        $branches = Branch::where('company_id', $company->id)
            ->withCount(['patients', 'appointments', 'staff'])
            ->orderBy('name')
            ->get();

        $staff = User::where('company_id', $company->id)
            ->where('employment_status', 'active')
            ->orderBy('first_name')
            ->limit(10)
            ->get();

        $recentActivity = ActivityLog::with('user')
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.index', compact('stats', 'branches', 'staff', 'recentActivity'));
    }

    public function roles()
    {
        $company = Company::first();
        $roles   = Role::where('company_id', $company->id)
            ->with('permissions')
            ->get();

        return view('admin.roles', compact('roles'));
    }

    public function settings()
    {
        $company = Company::first();
        return view('admin.settings', compact('company'));
    }

    public function activityLogs()
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(50);

        return view('admin.activity-logs', compact('logs'));
    }
}
