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
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin()) {
            return redirect($this->roleHome($user));
        }

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
        $thisMonth = $today->copy()->startOfMonth();
        $lastMonth = $today->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $today->copy()->subMonth()->endOfMonth();

        // ── Operational KPIs ──────────────────────────────────
        $kpi = [
            'total_patients'     => Patient::where('company_id', $companyId)->count(),
            'active_patients'    => Patient::where('company_id', $companyId)->where('status', 'active')->count(),
            'today_appointments' => Appointment::whereDate('scheduled_at', $today)->count(),
            'today_completed'    => Appointment::whereDate('scheduled_at', $today)->whereIn('status', Appointment::completedStatuses())->count(),
            'active_plans'       => TreatmentPlan::where('company_id', $companyId)->where('status', 'active')->count(),
            'pending_followups'  => FollowUp::where('company_id', $companyId)->where('status', 'pending')->count(),
            'open_leads'         => Lead::where('company_id', $companyId)->whereIn('status', ['new', 'contacted'])->count(),
            'total_staff'        => User::where('company_id', $companyId)->where('employment_status', 'active')->count(),
        ];

        // ── Revenue KPIs ──────────────────────────────────────
        $revenue = $this->buildRevenueKpis($companyId, $thisMonth, $lastMonth, $lastMonthEnd, $today);

        // ── Monthly revenue trend (last 6 months) ─────────────
        $revenueTrend = $this->buildRevenueTrend($companyId, 6);

        // ── Appointments per day (last 30 days) ───────────────
        $apptTrend = $this->buildAppointmentTrend($companyId, 30);

        // ── Top services by revenue ───────────────────────────
        $topServices = $this->buildTopServices($companyId, $thisMonth);

        // ── Revenue by branch this month ──────────────────────
        $branchRevenue = $this->buildBranchRevenue($companyId, $thisMonth);

        // ── New patients per month (last 6) ───────────────────
        $patientTrend = $this->buildPatientTrend($companyId, 6);

        // ── Today's appointments ──────────────────────────────
        $todayAppointments = Appointment::with(['patient', 'service', 'assignedStaff'])
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        // ── Recent patients ───────────────────────────────────
        $recentPatients = Patient::with('branch')
            ->where('company_id', $companyId)
            ->latest()
            ->limit(5)
            ->get();

        // ── Pending follow-ups ────────────────────────────────
        $pendingFollowUps = FollowUp::with(['patient', 'assignedTo'])
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // ── Branches ──────────────────────────────────────────
        $branches = Branch::where('company_id', $companyId)
            ->withCount(['patients', 'appointments', 'staff'])
            ->get();

        // ── Outstanding balances ──────────────────────────────
        $outstanding = TreatmentPlan::with(['patient', 'service'])
            ->where('company_id', $companyId)
            ->whereRaw('total_price > amount_paid')
            ->orderByRaw('(total_price - amount_paid) DESC')
            ->limit(8)
            ->get();

        return view('dashboard.index', compact(
            'kpi', 'revenue', 'revenueTrend', 'apptTrend',
            'topServices', 'branchRevenue', 'patientTrend',
            'todayAppointments', 'recentPatients', 'pendingFollowUps',
            'branches', 'outstanding'
        ));
    }

    // ─── Revenue helpers ──────────────────────────────────────────────────────

    private function buildRevenueKpis($companyId, $thisMonth, $lastMonth, $lastMonthEnd, $today): array
    {
        $base = TreatmentPlan::where('company_id', $companyId);

        $collectedThisMonth = (clone $base)
            ->whereBetween('updated_at', [$thisMonth, $today->copy()->endOfDay()])
            ->where('amount_paid', '>', 0)
            ->sum('amount_paid');

        $collectedLastMonth = (clone $base)
            ->whereBetween('updated_at', [$lastMonth, $lastMonthEnd->copy()->endOfDay()])
            ->where('amount_paid', '>', 0)
            ->sum('amount_paid');

        $bookedThisMonth = (clone $base)
            ->whereBetween('created_at', [$thisMonth, $today->copy()->endOfDay()])
            ->sum('total_price');

        $totalOutstanding = (clone $base)
            ->where('status', 'active')
            ->selectRaw('SUM(total_price - amount_paid) as diff')
            ->value('diff') ?? 0;

        $totalCollectedAllTime = (clone $base)->sum('amount_paid');
        $totalBookedAllTime    = (clone $base)->sum('total_price');

        $momChange = $collectedLastMonth > 0
            ? round((($collectedThisMonth - $collectedLastMonth) / $collectedLastMonth) * 100, 1)
            : null;

        return [
            'collected_this_month' => (float) $collectedThisMonth,
            'collected_last_month' => (float) $collectedLastMonth,
            'booked_this_month'    => (float) $bookedThisMonth,
            'outstanding'          => (float) max(0, $totalOutstanding),
            'total_all_time'       => (float) $totalCollectedAllTime,
            'total_booked'         => (float) $totalBookedAllTime,
            'mom_change'           => $momChange,
            'collection_rate'      => $totalBookedAllTime > 0
                ? round(($totalCollectedAllTime / $totalBookedAllTime) * 100, 1)
                : 0,
        ];
    }

    private function buildRevenueTrend($companyId, int $months): array
    {
        $labels  = [];
        $booked  = [];
        $collected = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = today()->subMonths($i)->startOfMonth();
            $end   = today()->subMonths($i)->endOfMonth();

            $labels[]    = $start->format('M Y');
            $booked[]    = (float) TreatmentPlan::where('company_id', $companyId)
                ->whereBetween('created_at', [$start, $end])
                ->sum('total_price');
            $collected[] = (float) TreatmentPlan::where('company_id', $companyId)
                ->whereBetween('updated_at', [$start, $end])
                ->where('amount_paid', '>', 0)
                ->sum('amount_paid');
        }

        return compact('labels', 'booked', 'collected');
    }

    private function buildAppointmentTrend($companyId, int $days): array
    {
        $labels    = [];
        $total     = [];
        $completed = [];
        $cancelled = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $day  = Appointment::whereDate('scheduled_at', $date);

            $labels[]    = $date->format('d M');
            $total[]     = (clone $day)->count();
            $completed[] = (clone $day)->whereIn('status', Appointment::completedStatuses())->count();
            $cancelled[] = (clone $day)->whereIn('status', ['cancelled', 'no_show'])->count();
        }

        return compact('labels', 'total', 'completed', 'cancelled');
    }

    private function buildTopServices($companyId, $since): array
    {
        return TreatmentPlan::with('service')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->select('service_id', DB::raw('COUNT(*) as plan_count'), DB::raw('SUM(total_price) as revenue'), DB::raw('SUM(amount_paid) as collected'))
            ->groupBy('service_id')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get()
            ->map(fn($p) => [
                'name'      => $p->service?->name ?? 'Unknown',
                'count'     => $p->plan_count,
                'revenue'   => (float) $p->revenue,
                'collected' => (float) $p->collected,
            ])
            ->toArray();
    }

    private function buildBranchRevenue($companyId, $since): array
    {
        return TreatmentPlan::with('branch')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->select('branch_id', DB::raw('SUM(total_price) as revenue'), DB::raw('SUM(amount_paid) as collected'), DB::raw('COUNT(*) as plans'))
            ->groupBy('branch_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($p) => [
                'name'      => $p->branch?->name ?? 'Unknown',
                'revenue'   => (float) $p->revenue,
                'collected' => (float) $p->collected,
                'plans'     => $p->plans,
            ])
            ->toArray();
    }

    private function buildPatientTrend($companyId, int $months): array
    {
        $labels = [];
        $counts = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = today()->subMonths($i)->startOfMonth();
            $end   = today()->subMonths($i)->endOfMonth();
            $labels[] = $start->format('M Y');
            $counts[]  = Patient::where('company_id', $companyId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return compact('labels', 'counts');
    }
}
