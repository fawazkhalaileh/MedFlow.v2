<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['manager', 'rooms'])
            ->withCount(['customers', 'appointments', 'staff'])
            ->latest()
            ->get();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        $managers = User::whereNotNull('company_id')
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['branch_manager', 'doctor', 'system_admin'])
            ->select('id', 'first_name', 'last_name', 'name', 'employee_type')
            ->get();

        return view('branches.create', compact('managers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:120',
            'code'       => 'required|string|max:20|unique:branches,code',
            'email'      => 'nullable|email|max:120',
            'phone'      => 'nullable|string|max:30',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:80',
            'country'    => 'nullable|string|max:80',
            'manager_id' => 'nullable|exists:users,id',
            'status'     => 'required|in:active,inactive,coming_soon',
            'notes'      => 'nullable|string',
        ]);

        $data['company_id'] = Company::first()->id;

        Branch::create($data);

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$data['name']}' created successfully.");
    }

    public function edit(Branch $branch)
    {
        $managers = User::whereNotNull('company_id')
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['branch_manager', 'doctor', 'system_admin'])
            ->select('id', 'first_name', 'last_name', 'name', 'employee_type')
            ->get();

        $branch->load(['rooms', 'staff.roles']);

        return view('branches.edit', compact('branch', 'managers'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:120',
            'code'       => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'email'      => 'nullable|email|max:120',
            'phone'      => 'nullable|string|max:30',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:80',
            'country'    => 'nullable|string|max:80',
            'manager_id' => 'nullable|exists:users,id',
            'status'     => 'required|in:active,inactive,coming_soon',
            'notes'      => 'nullable|string',
        ]);

        $branch->update($data);

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$branch->name}' updated successfully.");
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return redirect()->route('branches.index')
            ->with('success', "Branch '{$branch->name}' deleted.");
    }
}
