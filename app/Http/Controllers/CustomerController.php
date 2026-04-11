<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::first();

        $query = Customer::with(['branch', 'assignedStaff'])
            ->where('company_id', $company->id);

        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name',     'like', "%$q%")
                    ->orWhere('last_name',      'like', "%$q%")
                    ->orWhere('phone',           'like', "%$q%")
                    ->orWhere('email',           'like', "%$q%")
                    ->orWhere('customer_code',   'like', "%$q%");
            });
        }

        $customers = $query->latest()->paginate(25)->withQueryString();
        $branches  = Branch::orderBy('name')->get();

        return view('customers.index', compact('customers', 'branches'));
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'branch', 'assignedStaff', 'medicalInfo',
            'treatmentPlans.service',
            'appointments' => fn($q) => $q->with(['service', 'assignedStaff'])->latest('scheduled_at')->limit(10),
            'notes'        => fn($q) => $q->latest()->limit(10),
            'followUps'    => fn($q) => $q->latest()->limit(5),
        ]);

        return view('customers.show', compact('customer'));
    }
}
