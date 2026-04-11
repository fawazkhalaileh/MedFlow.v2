<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['primaryBranch', 'branches'])
            ->whereNotNull('company_id');

        if ($request->filled('branch')) {
            $query->where('primary_branch_id', $request->branch);
        }
        if ($request->filled('type')) {
            $query->where('employee_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%$q%")
                    ->orWhere('last_name',  'like', "%$q%")
                    ->orWhere('email',      'like', "%$q%")
                    ->orWhere('employee_id','like', "%$q%");
            });
        }

        $employees = $query->latest()->paginate(20)->withQueryString();
        $branches  = Branch::orderBy('name')->get();
        $types     = ['branch_manager', 'secretary', 'technician', 'doctor', 'nurse', 'finance', 'system_admin'];

        return view('employees.index', compact('employees', 'branches', 'types'));
    }

    public function create()
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $roles    = Role::whereNotNull('company_id')->orderBy('name')->get();
        $types    = ['branch_manager', 'secretary', 'technician', 'doctor', 'nurse', 'finance', 'system_admin'];

        return view('employees.create', compact('branches', 'roles', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'nullable|string|max:80',
            'email'            => 'required|email|unique:users,email',
            'password'         => ['required', Password::min(8)],
            'phone'            => 'nullable|string|max:30',
            'gender'           => 'nullable|in:male,female,other',
            'date_of_birth'    => 'nullable|date',
            'employee_type'    => 'required|string',
            'employment_status'=> 'required|in:active,inactive,on_leave,terminated',
            'hire_date'        => 'nullable|date',
            'primary_branch_id'=> 'nullable|exists:branches,id',
            'employee_notes'   => 'nullable|string',
        ]);

        $company = Company::first();

        // Auto-generate employee_id
        $count = User::whereNotNull('company_id')->count();
        $data['employee_id'] = 'EMP-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $data['company_id']  = $company->id;
        $data['password']    = Hash::make($data['password']);
        $data['name']        = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        $employee = User::create($data);

        // Assign to primary branch with role if provided
        if ($request->filled('primary_branch_id') && $request->filled('role_id')) {
            $employee->branches()->attach($request->primary_branch_id, [
                'role_id'    => $request->role_id,
                'is_primary' => true,
            ]);
        }

        return redirect()->route('employees.index')
            ->with('success', "Employee '{$employee->first_name} {$employee->last_name}' created successfully.");
    }

    public function edit(User $employee)
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $roles    = Role::whereNotNull('company_id')->orderBy('name')->get();
        $types    = ['branch_manager', 'secretary', 'technician', 'doctor', 'nurse', 'finance', 'system_admin'];

        return view('employees.edit', compact('employee', 'branches', 'roles', 'types'));
    }

    public function update(Request $request, User $employee)
    {
        $data = $request->validate([
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'nullable|string|max:80',
            'email'            => 'required|email|unique:users,email,' . $employee->id,
            'phone'            => 'nullable|string|max:30',
            'gender'           => 'nullable|in:male,female,other',
            'date_of_birth'    => 'nullable|date',
            'employee_type'    => 'required|string',
            'employment_status'=> 'required|in:active,inactive,on_leave,terminated',
            'hire_date'        => 'nullable|date',
            'primary_branch_id'=> 'nullable|exists:branches,id',
            'employee_notes'   => 'nullable|string',
        ]);

        $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        if ($request->filled('password')) {
            $request->validate(['password' => [Password::min(8)]]);
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        return redirect()->route('employees.index')
            ->with('success', "Employee '{$employee->first_name}' updated.");
    }

    public function destroy(User $employee)
    {
        $name = $employee->first_name . ' ' . $employee->last_name;
        $employee->update(['employment_status' => 'terminated']);
        return redirect()->route('employees.index')
            ->with('success', "Employee '{$name}' has been terminated.");
    }
}
