<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ClinicalFlag;
use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\PatientMedicalInfo;
use App\Models\User;
use App\Services\PatientHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    public function __construct(private readonly PatientHistoryService $patientHistoryService)
    {
    }

    /**
     * JSON search endpoint — used by appointment booking autocomplete.
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $q    = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $query = Patient::where(function ($sub) use ($q) {
            $sub->where('first_name',    'like', "%{$q}%")
                ->orWhere('last_name',   'like', "%{$q}%")
                ->orWhere('phone',       'like', "%{$q}%")
                ->orWhere('patient_code','like', "%{$q}%")
                ->orWhere('email',       'like', "%{$q}%");
        });

        if ($branchId = $user->scopedBranchId()) {
            $query->where('branch_id', $branchId);
        }

        $patients = $query->orderBy('first_name')
            ->limit(8)
            ->get(['id', 'first_name', 'last_name', 'phone', 'patient_code', 'status']);

        $patientAppointments = collect();
        if ($user->isRole('secretary', 'branch_manager') || $user->isSuperAdmin()) {
            $patientAppointments = \App\Models\Appointment::query()
                ->with(['service', 'assignedStaff'])
                ->whereIn('patient_id', $patients->pluck('id'))
                ->when($user->scopedBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                ->whereDate('scheduled_at', '>=', today()->subDays(1))
                ->orderBy('scheduled_at')
                ->get()
                ->groupBy('patient_id');
        }

        return response()->json($patients->map(fn($p) => [
            'id'           => $p->id,
            'full_name'    => $p->full_name,
            'phone'        => $p->phone,
            'patient_code' => $p->patient_code,
            'status'       => $p->status,
            'appointments' => collect($patientAppointments->get($p->id, []))
                ->take(4)
                ->map(fn ($appointment) => [
                    'id' => $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at?->format('d M, g:i A'),
                    'service' => $appointment->service?->name,
                    'staff' => $appointment->assignedStaff?->first_name,
                    'status' => $appointment->status,
                    'visit_type' => $appointment->visit_type,
                ])
                ->values(),
        ]));
    }

    public function index(Request $request)
    {
        $user    = Auth::user();
        $company = Company::first();

        $query = Patient::with(['branch', 'assignedStaff'])
            ->where('company_id', $company->id);

        // Non-admin roles are scoped to their primary branch
        if ($branchId = $user->scopedBranchId()) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('branch') && $user->isSuperAdmin()) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name',    'like', "%$q%")
                    ->orWhere('last_name',    'like', "%$q%")
                    ->orWhere('phone',        'like', "%$q%")
                    ->orWhere('email',        'like', "%$q%")
                    ->orWhere('patient_code', 'like', "%$q%");
            });
        }

        $patients = $query->latest()->paginate(25)->withQueryString();
        $branches = Branch::orderBy('name')->get();

        return view('patients.index', compact('patients', 'branches'));
    }

    public function create()
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $staff    = User::whereNotNull('company_id')
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['technician', 'doctor', 'nurse'])
            ->select('id', 'first_name', 'last_name', 'employee_type')
            ->get();

        return view('patients.create', compact('branches', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'                    => 'required|string|max:80',
            'last_name'                     => 'required|string|max:80',
            'email'                         => 'nullable|email|max:120',
            'phone'                         => 'required|string|max:30',
            'phone_alt'                     => 'nullable|string|max:30',
            'date_of_birth'                 => 'nullable|date',
            'gender'                        => 'nullable|in:male,female,other',
            'nationality'                   => 'nullable|string|max:60',
            'id_number'                     => 'nullable|string|max:40',
            'address'                       => 'nullable|string|max:255',
            'city'                          => 'nullable|string|max:60',
            'emergency_contact_name'        => 'nullable|string|max:80',
            'emergency_contact_phone'       => 'nullable|string|max:30',
            'emergency_contact_relation'    => 'nullable|string|max:40',
            'branch_id'                     => 'required|exists:branches,id',
            'assigned_staff_id'             => 'nullable|exists:users,id',
            'source'                        => 'nullable|string',
            'referral_source'               => 'nullable|string|max:120',
            'status'                        => 'required|in:active,inactive,vip,blacklisted',
            'consent_given'                 => 'nullable|boolean',
            'internal_notes'                => 'nullable|string',
        ]);

        $data['company_id']        = Company::first()->id;
        $data['registration_date'] = today()->format('Y-m-d');
        $data['consent_given']     = $request->boolean('consent_given');
        $data['consent_given_at']  = $data['consent_given'] ? now() : null;

        $patient = Patient::create($data);

        // Save medical info if any provided
        $this->saveMedicalInfo($request, $patient);

        return redirect()->route('patients.show', $patient)
            ->with('success', "Patient {$patient->full_name} registered successfully.");
    }

    public function show(Patient $patient)
    {
        $this->patientHistoryService->assertCanView(Auth::user(), $patient);

        $patient->load([
            'branch', 'assignedStaff', 'medicalInfo',
            'clinicalFlags',
            'treatmentPlans.service',
            'appointments' => fn($q) => $q->with(['service', 'assignedStaff'])->latest('scheduled_at')->limit(10),
            'notes'        => fn($q) => $q->with('createdBy')->latest()->limit(30),
            'followUps'    => fn($q) => $q->where('status', 'pending')->orderBy('due_date')->limit(15),
            'attachments'  => fn($q) => $q->with('uploadedBy')->latest()->limit(12),
        ]);

        $allFlags = ClinicalFlag::where('is_active', true)->orderBy('sort_order')->get();
        $historyTimeline = $this->patientHistoryService->timeline(Auth::user(), $patient)->take(40);

        return view('patients.show', compact('patient', 'allFlags', 'historyTimeline'));
    }

    public function edit(Patient $patient)
    {
        $patient->load('medicalInfo');
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $staff    = User::whereNotNull('company_id')
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['technician', 'doctor', 'nurse'])
            ->select('id', 'first_name', 'last_name', 'employee_type')
            ->get();

        return view('patients.edit', compact('patient', 'branches', 'staff'));
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'first_name'                    => 'required|string|max:80',
            'last_name'                     => 'required|string|max:80',
            'email'                         => 'nullable|email|max:120',
            'phone'                         => 'required|string|max:30',
            'phone_alt'                     => 'nullable|string|max:30',
            'date_of_birth'                 => 'nullable|date',
            'gender'                        => 'nullable|in:male,female,other',
            'nationality'                   => 'nullable|string|max:60',
            'id_number'                     => 'nullable|string|max:40',
            'address'                       => 'nullable|string|max:255',
            'city'                          => 'nullable|string|max:60',
            'emergency_contact_name'        => 'nullable|string|max:80',
            'emergency_contact_phone'       => 'nullable|string|max:30',
            'emergency_contact_relation'    => 'nullable|string|max:40',
            'branch_id'                     => 'required|exists:branches,id',
            'assigned_staff_id'             => 'nullable|exists:users,id',
            'source'                        => 'nullable|string',
            'referral_source'               => 'nullable|string|max:120',
            'status'                        => 'required|in:active,inactive,vip,blacklisted',
            'consent_given'                 => 'nullable|boolean',
            'internal_notes'                => 'nullable|string',
        ]);

        $wasConsented = $patient->consent_given;
        $data['consent_given'] = $request->boolean('consent_given');
        if (!$wasConsented && $data['consent_given']) {
            $data['consent_given_at'] = now();
        }

        $patient->update($data);

        $this->saveMedicalInfo($request, $patient);

        return redirect()->route('patients.show', $patient)
            ->with('success', "Patient {$patient->full_name} updated successfully.");
    }

    public function destroy(Patient $patient)
    {
        $name = $patient->full_name;
        $patient->delete();
        return redirect()->route('patients.index')
            ->with('success', "Patient '{$name}' has been archived.");
    }

    public function storeAttachment(Request $request, Patient $patient)
    {
        $this->patientHistoryService->assertCanView(Auth::user(), $patient);

        $validated = $request->validate([
            'attachment' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
            'title' => ['nullable', 'string', 'max:160'],
            'attachment_type' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $file = $validated['attachment'];
        $path = $file->store('patient-attachments/' . $patient->id, 'public');

        PatientAttachment::create([
            'company_id' => $patient->company_id,
            'branch_id' => $patient->branch_id,
            'patient_id' => $patient->id,
            'attachment_type' => $validated['attachment_type']
                ?? (str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'document'),
            'title' => $validated['title'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_private' => $request->boolean('is_private'),
            'notes' => $validated['notes'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        return redirect()->route('patients.show', $patient)->with('success', 'Attachment uploaded successfully.');
    }

    private function saveMedicalInfo(Request $request, Patient $patient): void
    {
        $medFields = [
            'height_cm', 'weight_kg', 'skin_type', 'skin_tone',
            'medical_history', 'current_medications', 'allergies',
            'contraindications', 'other_conditions',
            'insurance_provider', 'insurance_number',
            'insurance_expiry', 'insurance_plan',
        ];

        $medBools = ['is_pregnant', 'has_pacemaker', 'has_metal_implants'];

        $hasAnyMedData = $request->hasAny($medFields) ||
            $request->hasAny($medBools);

        if (!$hasAnyMedData) {
            return;
        }

        $medData = $request->only($medFields);
        foreach ($medBools as $bool) {
            $medData[$bool] = $request->boolean($bool);
        }
        $medData['updated_by'] = auth()->id();

        $patient->medicalInfo()->updateOrCreate(
            ['patient_id' => $patient->id],
            $medData
        );
    }
}
