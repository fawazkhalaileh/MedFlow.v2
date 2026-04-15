<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Package;
use App\Models\PackageUsage;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageService
{
    public function createPackage(
        User $user,
        Branch $branch,
        Service $service,
        array $attributes
    ): Package {
        $pricing = $this->calculatePricing(
            (float) $attributes['original_price'],
            $attributes['discount_type'] ?? null,
            $attributes['discount_value'] ?? null
        );

        return DB::transaction(function () use ($user, $branch, $service, $attributes, $pricing) {
            $package = Package::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'service_id' => $service->id,
                'name' => $attributes['name'],
                'sessions_purchased' => (int) $attributes['sessions_purchased'],
                'original_price' => $pricing['original_price'],
                'discount_type' => $pricing['discount_type'],
                'discount_value' => $pricing['discount_value'],
                'final_price' => $pricing['final_price'],
                'expiry_date' => $attributes['expiry_date'] ?? null,
                'status' => Package::STATUS_ACTIVE,
                'created_by' => $user->id,
                'notes' => $attributes['notes'] ?? null,
            ]);

            ActivityLog::record(
                'package_created',
                $package,
                "Created package {$package->name}.",
                [],
                [
                    'branch_id' => $branch->id,
                    'service_id' => $service->id,
                    'sessions_purchased' => $package->sessions_purchased,
                    'final_price' => $package->final_price,
                ]
            );

            return $package->load(['service', 'branch', 'createdBy']);
        });
    }

    public function purchasePackage(
        User $user,
        Package $package,
        Patient $patient,
        array $attributes = []
    ): PatientPackage {
        return DB::transaction(function () use ($user, $package, $patient, $attributes) {
            $this->syncPackageStatus($package);

            if ($package->status !== Package::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'package_id' => 'Only active packages can be purchased.',
                ]);
            }

            $purchase = PatientPackage::create([
                'company_id' => $package->company_id,
                'branch_id' => $package->branch_id,
                'package_id' => $package->id,
                'patient_id' => $patient->id,
                'sessions_purchased' => $package->sessions_purchased,
                'sessions_used' => 0,
                'final_price' => $package->final_price,
                'expiry_date' => $attributes['expiry_date'] ?? $package->expiry_date,
                'status' => PatientPackage::STATUS_ACTIVE,
                'purchased_at' => now(),
                'purchased_by' => $user->id,
                'notes' => $attributes['notes'] ?? null,
            ]);

            ActivityLog::record(
                'patient_package_purchased',
                $purchase,
                "Purchased package {$package->name} for {$patient->full_name}.",
                [],
                [
                    'package_id' => $package->id,
                    'patient_id' => $patient->id,
                    'final_price' => $purchase->final_price,
                ]
            );

            return $purchase->load(['package.service', 'patient', 'branch', 'purchasedBy']);
        });
    }

    public function updatePackage(Package $package, array $attributes): Package
    {
        return DB::transaction(function () use ($package, $attributes) {
            $package->update([
                'name' => $attributes['name'],
                'expiry_date' => $attributes['expiry_date'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $this->syncPackageStatus($package);

            ActivityLog::record(
                'package_updated',
                $package,
                "Updated package {$package->name}.",
                [],
                [
                    'package_id' => $package->id,
                    'expiry_date' => $package->expiry_date?->toDateString(),
                ]
            );

            return $package->fresh(['service', 'branch', 'patientPackages.patient']);
        });
    }

    public function freezePackage(Package $package): Package
    {
        return DB::transaction(function () use ($package) {
            $this->syncPackageStatus($package);

            if ($package->status !== Package::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'status' => 'Only active packages can be frozen.',
                ]);
            }

            $package->update([
                'status' => Package::STATUS_FROZEN,
                'frozen_at' => now(),
            ]);

            PatientPackage::query()
                ->where('package_id', $package->id)
                ->where('status', PatientPackage::STATUS_ACTIVE)
                ->update(['status' => PatientPackage::STATUS_FROZEN]);

            ActivityLog::record('package_frozen', $package, "Froze package {$package->name}.");

            return $package->fresh(['service', 'branch']);
        });
    }

    public function unfreezePackage(Package $package): Package
    {
        return DB::transaction(function () use ($package) {
            $this->syncPackageStatus($package);

            if ($package->status !== Package::STATUS_FROZEN) {
                throw ValidationException::withMessages([
                    'status' => 'Only frozen packages can be unfrozen.',
                ]);
            }

            if ($package->expiry_date && $package->expiry_date->isPast()) {
                $package->update(['status' => Package::STATUS_EXPIRED]);

                throw ValidationException::withMessages([
                    'status' => 'Expired packages cannot be unfrozen.',
                ]);
            }

            $package->update([
                'status' => Package::STATUS_ACTIVE,
                'unfrozen_at' => now(),
            ]);

            PatientPackage::query()
                ->where('package_id', $package->id)
                ->where('status', PatientPackage::STATUS_FROZEN)
                ->where(function ($query) {
                    $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today());
                })
                ->update(['status' => PatientPackage::STATUS_ACTIVE]);

            ActivityLog::record('package_unfrozen', $package, "Unfroze package {$package->name}.");

            return $package->fresh(['service', 'branch']);
        });
    }

    public function recordAppointmentUsage(User $user, Appointment $appointment, ?string $notes = null): ?PackageUsage
    {
        return DB::transaction(function () use ($user, $appointment, $notes) {
            if (!$appointment->patient_package_id) {
                return null;
            }

            $appointment->loadMissing(['patientPackage.package', 'packageUsage']);

            if ($appointment->packageUsage) {
                return $appointment->packageUsage;
            }

            if ($appointment->status !== Appointment::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'appointment' => 'Package usage can only be deducted for completed appointments.',
                ]);
            }

            $patientPackage = $appointment->patientPackage;

            if (!$patientPackage) {
                throw ValidationException::withMessages([
                    'patient_package_id' => 'Attached patient package could not be found.',
                ]);
            }

            $patientPackage->refresh();
            $patientPackage->loadMissing('package');

            $this->syncPatientPackageStatus($patientPackage);

            if ($patientPackage->status === PatientPackage::STATUS_FROZEN) {
                throw ValidationException::withMessages([
                    'patient_package_id' => 'Frozen patient packages cannot be used.',
                ]);
            }

            if ($patientPackage->status === PatientPackage::STATUS_EXPIRED) {
                throw ValidationException::withMessages([
                    'patient_package_id' => 'Expired patient packages cannot be used.',
                ]);
            }

            if ($patientPackage->status === PatientPackage::STATUS_EXHAUSTED) {
                throw ValidationException::withMessages([
                    'patient_package_id' => 'Exhausted patient packages cannot be used.',
                ]);
            }

            if ($appointment->branch_id !== $patientPackage->branch_id
                || $appointment->patient_id !== $patientPackage->patient_id
                || $appointment->service_id !== $patientPackage->package->service_id
            ) {
                throw ValidationException::withMessages([
                    'patient_package_id' => 'Appointment must match the patient package branch, patient, and service.',
                ]);
            }

            $usage = PackageUsage::create([
                'company_id' => $patientPackage->company_id,
                'branch_id' => $patientPackage->branch_id,
                'patient_package_id' => $patientPackage->id,
                'patient_id' => $patientPackage->patient_id,
                'service_id' => $patientPackage->package->service_id,
                'appointment_id' => $appointment->id,
                'sessions_consumed' => 1,
                'used_at' => now(),
                'used_by' => $user->id,
                'notes' => $notes,
            ]);

            $newUsed = (int) $patientPackage->sessions_used + 1;

            $patientPackage->update([
                'sessions_used' => $newUsed,
                'status' => $newUsed >= (int) $patientPackage->sessions_purchased
                    ? PatientPackage::STATUS_EXHAUSTED
                    : PatientPackage::STATUS_ACTIVE,
            ]);

            ActivityLog::record(
                'patient_package_session_used',
                $patientPackage,
                "Recorded 1 session used for patient package #{$patientPackage->id}.",
                [],
                [
                    'patient_package_id' => $patientPackage->id,
                    'appointment_id' => $appointment->id,
                    'sessions_used' => $patientPackage->fresh()->sessions_used,
                ]
            );

            return $usage->load(['patientPackage.package', 'appointment', 'usedBy']);
        });
    }

    public function syncExpiredPackagesForBranch(?int $companyId, ?int $branchId = null): void
    {
        Package::query()
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->whereIn('status', [Package::STATUS_ACTIVE, Package::STATUS_FROZEN])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->update(['status' => Package::STATUS_EXPIRED]);

        PatientPackage::query()
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->whereIn('status', [PatientPackage::STATUS_ACTIVE, PatientPackage::STATUS_FROZEN])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->update(['status' => PatientPackage::STATUS_EXPIRED]);
    }

    private function calculatePricing(float $originalPrice, ?string $discountType, mixed $discountValue): array
    {
        $discountType = $discountType ?: null;
        $discountValue = $discountType ? (float) ($discountValue ?? 0) : null;

        if ($discountType === Package::DISCOUNT_PERCENTAGE && ($discountValue < 0 || $discountValue > 100)) {
            throw ValidationException::withMessages([
                'discount_value' => 'Percentage discounts must be between 0 and 100.',
            ]);
        }

        if ($discountType === Package::DISCOUNT_FIXED && $discountValue > $originalPrice) {
            throw ValidationException::withMessages([
                'discount_value' => 'Fixed discount cannot exceed the original price.',
            ]);
        }

        $finalPrice = match ($discountType) {
            Package::DISCOUNT_PERCENTAGE => max(0, $originalPrice - (($originalPrice * $discountValue) / 100)),
            Package::DISCOUNT_FIXED => max(0, $originalPrice - $discountValue),
            default => $originalPrice,
        };

        return [
            'original_price' => number_format($originalPrice, 2, '.', ''),
            'discount_type' => $discountType,
            'discount_value' => $discountType ? number_format($discountValue, 2, '.', '') : null,
            'final_price' => number_format($finalPrice, 2, '.', ''),
        ];
    }

    private function syncPackageStatus(Package $package): void
    {
        if ($package->expiry_date && $package->expiry_date->isPast()) {
            $package->update(['status' => Package::STATUS_EXPIRED]);
        }
    }

    private function syncPatientPackageStatus(PatientPackage $patientPackage): void
    {
        $patientPackage->loadMissing('package');

        if ($patientPackage->package?->status === Package::STATUS_FROZEN) {
            $patientPackage->update(['status' => PatientPackage::STATUS_FROZEN]);
            return;
        }

        if ($patientPackage->package?->status === Package::STATUS_EXPIRED) {
            $patientPackage->update(['status' => PatientPackage::STATUS_EXPIRED]);
            return;
        }

        if ($patientPackage->sessions_used >= $patientPackage->sessions_purchased) {
            $patientPackage->update(['status' => PatientPackage::STATUS_EXHAUSTED]);
            return;
        }

        if ($patientPackage->expiry_date && $patientPackage->expiry_date->isPast()) {
            $patientPackage->update(['status' => PatientPackage::STATUS_EXPIRED]);
            return;
        }

        if ($patientPackage->status !== PatientPackage::STATUS_ACTIVE) {
            $patientPackage->update(['status' => PatientPackage::STATUS_ACTIVE]);
        }
    }
}
