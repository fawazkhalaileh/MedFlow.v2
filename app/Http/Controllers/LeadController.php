<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::first();

        $query = Lead::with(['branch', 'assignedTo'])
            ->where('company_id', $company->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch')) {
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
        $branches = Branch::orderBy('name')->get();

        return view('leads.index', compact('leads', 'branches'));
    }
}
