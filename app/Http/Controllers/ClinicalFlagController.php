<?php

namespace App\Http\Controllers;

use App\Models\ClinicalFlag;
use App\Models\Company;
use App\Models\Patient;
use Illuminate\Http\Request;

class ClinicalFlagController extends Controller
{
    /**
     * List all flags for management.
     */
    public function index()
    {
        $flags     = ClinicalFlag::withCount('patients')
                        ->orderBy('category')
                        ->orderBy('sort_order')
                        ->get()
                        ->groupBy('category');

        $companyId = Company::first()?->id;

        return view('clinical-flags.index', compact('flags', 'companyId'));
    }

    /**
     * Create a new flag in the master list.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:80',
            'category'           => 'required|in:general,medical,allergy,lifestyle,alert',
            'color'              => 'required|string|max:20',
            'icon'               => 'nullable|string|max:10',
            'requires_detail'    => 'nullable|boolean',
            'detail_placeholder' => 'nullable|string|max:120',
            'sort_order'         => 'nullable|integer',
        ]);

        $data['company_id']      = Company::first()?->id;
        $data['requires_detail'] = $request->boolean('requires_detail');
        $data['is_active']       = true;
        $data['sort_order']      = $data['sort_order'] ?? 0;

        ClinicalFlag::create($data);

        return redirect()->route('clinical-flags.index')
            ->with('success', 'Clinical flag created successfully.');
    }

    /**
     * Update an existing flag.
     */
    public function update(Request $request, ClinicalFlag $flag)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:80',
            'category'           => 'required|in:general,medical,allergy,lifestyle,alert',
            'color'              => 'required|string|max:20',
            'icon'               => 'nullable|string|max:10',
            'requires_detail'    => 'nullable|boolean',
            'detail_placeholder' => 'nullable|string|max:120',
            'is_active'          => 'nullable|boolean',
            'sort_order'         => 'nullable|integer',
        ]);

        $data['requires_detail'] = $request->boolean('requires_detail');
        $data['is_active']       = $request->boolean('is_active', true);
        $data['sort_order']      = $data['sort_order'] ?? $flag->sort_order;

        $flag->update($data);

        return redirect()->route('clinical-flags.index')
            ->with('success', 'Clinical flag updated successfully.');
    }

    /**
     * Delete a flag from the master list.
     */
    public function destroy(ClinicalFlag $flag)
    {
        $flag->delete();

        return redirect()->route('clinical-flags.index')
            ->with('success', 'Clinical flag deleted.');
    }

    /**
     * Assign a flag to a patient.
     */
    public function assignToPatient(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'flag_id' => 'required|exists:clinical_flags,id',
            'detail'  => 'nullable|string|max:200',
        ]);

        // Sync only the new flag without detaching others
        try {
            $patient->clinicalFlags()->attach($data['flag_id'], [
                'detail'   => $data['detail'] ?? null,
                'added_by' => auth()->id(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique constraint: flag already assigned
            return redirect()->back()->with('error', 'This flag is already assigned to the patient.');
        }

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Clinical flag assigned.');
    }

    /**
     * Remove a flag from a patient.
     */
    public function removeFromPatient(Request $request, Patient $patient, ClinicalFlag $flag)
    {
        $patient->clinicalFlags()->detach($flag->id);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Clinical flag removed.');
    }
}
