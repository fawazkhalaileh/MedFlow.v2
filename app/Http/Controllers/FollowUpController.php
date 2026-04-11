<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FollowUp;
use App\Models\Branch;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::first();

        $query = FollowUp::with(['patient', 'assignedTo', 'branch'])
            ->where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $followups = $query->orderBy('due_date')->paginate(25)->withQueryString();
        $branches  = Branch::orderBy('name')->get();

        return view('followups.index', compact('followups', 'branches'));
    }
}
