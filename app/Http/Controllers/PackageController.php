<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Services\PackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function __construct(private readonly PackageService $packageService)
    {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $this->currentCompany($user);
        $scopedBranchId = $user->scopedBranchId();
        $selectedBranchId = $scopedBranchId ?: ($request->filled('branch') ? (int) $request->branch : null);

        $this->packageService->syncExpiredPackagesForBranch($company->id, $selectedBranchId);

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $packages = Package::query()
            ->with(['branch', 'service', 'createdBy', 'patientPackages.patient'])
            ->where('company_id', $company->id)
            ->when($selectedBranchId, fn($query) => $query->where('branch_id', $selectedBranchId))
            ->latest()
            ->get();

        $patientPackages = PatientPackage::query()
            ->with(['branch', 'patient', 'package.service', 'purchasedBy', 'usages.appointment'])
            ->where('company_id', $company->id)
            ->when($selectedBranchId, fn($query) => $query->where('branch_id', $selectedBranchId))
            ->latest('purchased_at')
            ->get();

        $patients = Patient::query()
            ->where('company_id', $company->id)
            ->when($scopedBranchId, fn($query) => $query->where('branch_id', $scopedBranchId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $services = Service::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('packages.index', [
            'packages' => $packages,
            'patientPackages' => $patientPackages,
            'branches' => $branches,
            'patients' => $patients,
            'services' => $services,
            'selectedBranchId' => $selectedBranchId,
            'scopedBranchId' => $scopedBranchId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $company = $this->currentCompany($user);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'name' => ['required', 'string', 'max:150'],
            'sessions_purchased' => ['required', 'integer', 'min:1'],
            'original_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(Package::discountTypes())],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $branch = $this->resolveActionBranch($user, $company, $validated['branch_id'] ?? null);
        $service = Service::query()->where('company_id', $company->id)->findOrFail((int) $validated['service_id']);

        $package = $this->packageService->createPackage($user, $branch, $service, $validated);

        return redirect()
            ->route('packages.index', $this->packageRedirectParams($user, $branch))
            ->with('success', __('Package :name created.', ['name' => $package->name]));
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $company = $this->currentCompany($user);

        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $package = $this->resolvePackage($user, (int) $validated['package_id']);
        $branch = $this->resolveActionBranch($user, $company, $package->branch_id);
        $patient = $this->resolvePatientForBranch($company, $branch, (int) $validated['patient_id']);

        $purchase = $this->packageService->purchasePackage($user, $package, $patient, $validated);

        return redirect()
            ->route('packages.index', $this->packageRedirectParams($user, $branch))
            ->with('success', __('Package :package purchased for :patient.', [
                'package' => $purchase->package?->name,
                'patient' => $patient->full_name,
            ]));
    }

    public function edit(Package $package)
    {
        $user = Auth::user();
        $package = $this->resolvePackage($user, $package->id);

        return view('packages.edit', [
            'package' => $package->load(['branch', 'service']),
        ]);
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $user = Auth::user();
        $package = $this->resolvePackage($user, $package->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'original_price' => ['prohibited'],
            'discount_type' => ['prohibited'],
            'discount_value' => ['prohibited'],
            'final_price' => ['prohibited'],
        ]);

        $package = $this->packageService->updatePackage($package, $validated);

        return redirect()
            ->route('packages.index', $this->packageRedirectParams($user, $package->branch))
            ->with('success', __('Package :name updated.', ['name' => $package->name]));
    }

    public function freeze(Package $package): RedirectResponse
    {
        $user = Auth::user();
        $package = $this->resolvePackage($user, $package->id);
        $package = $this->packageService->freezePackage($package);

        return redirect()
            ->route('packages.index', $this->packageRedirectParams($user, $package->branch))
            ->with('success', __('Package :name frozen.', ['name' => $package->name]));
    }

    public function unfreeze(Package $package): RedirectResponse
    {
        $user = Auth::user();
        $package = $this->resolvePackage($user, $package->id);
        $package = $this->packageService->unfreezePackage($package);

        return redirect()
            ->route('packages.index', $this->packageRedirectParams($user, $package->branch))
            ->with('success', __('Package :name unfrozen.', ['name' => $package->name]));
    }

    private function currentCompany($user): Company
    {
        return Company::query()->findOrFail($user->company_id ?? Company::query()->value('id'));
    }

    private function resolveActionBranch($user, Company $company, ?int $requestedBranchId): Branch
    {
        $branchId = $user->scopedBranchId() ?? $requestedBranchId;

        abort_unless($branchId, 404);

        return Branch::query()
            ->where('company_id', $company->id)
            ->when($user->scopedBranchId(), fn($query, $scopedBranchId) => $query->where('id', $scopedBranchId))
            ->findOrFail($branchId);
    }

    private function resolvePackage($user, int $packageId): Package
    {
        return Package::query()
            ->where('company_id', $user->company_id)
            ->when($user->scopedBranchId(), fn($query, $scopedBranchId) => $query->where('branch_id', $scopedBranchId))
            ->findOrFail($packageId);
    }

    private function resolvePatientForBranch(Company $company, Branch $branch, int $patientId): Patient
    {
        return Patient::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->findOrFail($patientId);
    }

    private function packageRedirectParams($user, Branch $branch): array
    {
        return $user->scopedBranchId() ? [] : ['branch' => $branch->id];
    }
}
