<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        $user    = Auth::user();
        $company = Company::first();

        $query = FollowUp::with(['patient', 'assignedTo', 'branch'])
            ->where('company_id', $company->id);

        // Non-admin scoped to their branch
        if ($branchId = $user->scopedBranchId()) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }
        if ($request->filled('branch') && $user->isSuperAdmin()) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $followups = $query->orderBy('due_date')->paginate(25)->withQueryString();
        $branches  = Branch::orderBy('name')->get();
        $staff     = User::whereNotNull('company_id')
            ->where('employment_status', 'active')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('followups.index', compact('followups', 'branches', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'  => 'required|exists:patients,id',
            'type'        => 'required|in:call,appointment,check_in,email',
            'due_date'    => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $user    = Auth::user();
        $patient = Patient::findOrFail($data['patient_id']);

        FollowUp::create([
            'company_id'  => Company::first()->id,
            'branch_id'   => $patient->branch_id,
            'patient_id'  => $data['patient_id'],
            'type'        => $data['type'],
            'due_date'    => $data['due_date'],
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'notes'       => $data['notes'] ?? null,
            'status'      => 'pending',
            'created_by'  => $user->id,
        ]);

        return back()->with('success', 'Follow-up created successfully.');
    }

    public function update(Request $request, FollowUp $followUp)
    {
        $data = $request->validate([
            'type'        => 'required|in:call,appointment,check_in,email',
            'due_date'    => 'required|date',
            'status'      => 'required|in:pending,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'notes'       => 'nullable|string|max:1000',
            'outcome'     => 'nullable|string|max:1000',
        ]);

        $followUp->update([
            'type'        => $data['type'],
            'due_date'    => $data['due_date'],
            'status'      => $data['status'],
            'assigned_to' => $data['assigned_to'],
            'notes'       => $data['notes'],
            'outcome'     => $data['outcome'],
            'completed_at'  => $data['status'] === 'completed' ? ($followUp->completed_at ?? now()) : null,
            'completed_by'  => $data['status'] === 'completed' ? Auth::id() : null,
        ]);

        return back()->with('success', 'Follow-up updated.');
    }

    public function complete(Request $request, FollowUp $followUp)
    {
        $data = $request->validate([
            'outcome' => 'nullable|string|max:1000',
        ]);

        $followUp->update([
            'status'       => 'completed',
            'outcome'      => $data['outcome'] ?? null,
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Follow-up marked as completed.');
    }

    public function destroy(FollowUp $followUp)
    {
        $followUp->delete();
        return back()->with('success', 'Follow-up deleted.');
    }
}
