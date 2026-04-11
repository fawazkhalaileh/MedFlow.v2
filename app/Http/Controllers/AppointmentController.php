<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::first();
        $date    = $request->input('date', today()->format('Y-m-d'));

        $query = Appointment::with(['customer', 'service', 'assignedStaff', 'branch'])
            ->whereHas('customer', fn($q) => $q->where('company_id', $company->id));

        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $date);
        }
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('scheduled_at')->paginate(25)->withQueryString();
        $branches     = Branch::orderBy('name')->get();

        $statuses = ['scheduled', 'confirmed', 'arrived', 'in_progress', 'completed', 'cancelled', 'no_show'];

        return view('appointments.index', compact('appointments', 'branches', 'statuses', 'date'));
    }
}
