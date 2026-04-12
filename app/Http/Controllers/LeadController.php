<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $user    = Auth::user();
        $company = Company::first();

        $query = Lead::with(['branch', 'assignedTo'])
            ->where('company_id', $company->id);

        if ($branchId = $user->scopedBranchId()) {
            $query->where('branch_id', $branchId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch') && $user->isSuperAdmin()) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%$q%")
                    ->orWhere('last_name',  'like', "%$q%")
                    ->orWhere('phone',      'like', "%$q%")
                    ->orWhere('email',      'like', "%$q%");
            });
        }

        $leads    = $query->latest()->paginate(25)->withQueryString();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $staff    = User::whereNotNull('company_id')
            ->where('employment_status', 'active')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('leads.index', compact('leads', 'branches', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'nullable|string|max:80',
            'phone'            => 'required|string|max:30',
            'email'            => 'nullable|email|max:120',
            'service_interest' => 'nullable|string|max:120',
            'source'           => 'required|in:phone,walk_in,social,online,referral',
            'branch_id'        => 'required|exists:branches,id',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        Lead::create([
            ...$data,
            'company_id' => Company::first()->id,
            'status'     => 'new',
            'created_by' => $user->id,
        ]);

        return back()->with('success', 'Lead added successfully.');
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'nullable|string|max:80',
            'phone'            => 'required|string|max:30',
            'email'            => 'nullable|email|max:120',
            'service_interest' => 'nullable|string|max:120',
            'source'           => 'required|in:phone,walk_in,social,online,referral',
            'status'           => 'required|in:new,contacted,appointment_booked,converted,lost',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $lead->update($data);

        return back()->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return back()->with('success', 'Lead deleted.');
    }

    /**
     * Convert a lead into a registered patient.
     */
    public function convert(Lead $lead)
    {
        if ($lead->status === 'converted') {
            return back()->with('error', 'This lead has already been converted.');
        }

        $company = Company::first();
        $user    = Auth::user();

        // Generate patient code
        $lastCode = Patient::where('company_id', $company->id)
            ->orderByDesc('id')
            ->value('patient_code');
        $nextNum  = $lastCode ? ((int) preg_replace('/\D/', '', $lastCode)) + 1 : 1;
        $code     = 'MF-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        $patient = Patient::create([
            'company_id'        => $company->id,
            'branch_id'         => $lead->branch_id,
            'patient_code'      => $code,
            'first_name'        => $lead->first_name,
            'last_name'         => $lead->last_name ?? '',
            'phone'             => $lead->phone,
            'email'             => $lead->email,
            'source'            => $lead->source,
            'status'            => 'active',
            'registration_date' => today()->format('Y-m-d'),
            'consent_given'     => false,
            'internal_notes'    => $lead->notes ? "Converted from lead. Notes: {$lead->notes}" : null,
        ]);

        $lead->update([
            'status'                 => 'converted',
            'converted_to_patient_id'=> $patient->id,
            'converted_at'           => now(),
        ]);

        return redirect()->route('patients.show', $patient)
            ->with('success', "Lead converted — patient profile created for {$patient->full_name}.");
    }
}
