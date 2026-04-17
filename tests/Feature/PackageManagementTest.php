<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_package_master_in_own_branch(): void
    {
        [$company, $branch, , $manager, $patient, $service, $technician] = $this->makePackageFixtures();

        $response = $this->actingAs($manager)->post(route('packages.store'), [
            'service_id' => $service->id,
            'name' => 'Laser Glow Package',
            'sessions_purchased' => 6,
            'original_price' => '300.00',
            'discount_type' => 'percentage',
            'discount_value' => '10',
            'expiry_date' => '2026-12-31',
            'notes' => 'Spring offer',
        ]);

        $response->assertRedirect(route('packages.index'));

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'name' => 'Laser Glow Package',
            'sessions_purchased' => 6,
            'status' => Package::STATUS_ACTIVE,
            'final_price' => '270.00',
        ]);
    }

    public function test_secretary_cannot_manage_packages(): void
    {
        [, $branch, , , $patient, $service] = $this->makePackageFixtures();

        $secretary = User::factory()->create([
            'company_id' => $branch->company_id,
            'employee_type' => 'secretary',
            'role' => 'secretary',
            'primary_branch_id' => $branch->id,
            'first_name' => 'Front',
            'last_name' => 'Desk',
        ]);

        $this->actingAs($secretary)->get(route('packages.index'))->assertForbidden();

        $this->actingAs($secretary)->post(route('packages.store'), [
            'service_id' => $service->id,
            'name' => 'Blocked Package',
            'sessions_purchased' => 4,
            'original_price' => '200.00',
        ])->assertForbidden();

        $package = $this->createPackage($branch, $service, $secretary);

        $this->actingAs($secretary)->post(route('packages.purchases.store'), [
            'package_id' => $package->id,
            'patient_id' => $patient->id,
        ])->assertForbidden();
    }

    public function test_branch_manager_cannot_manage_another_branch_package(): void
    {
        [$company, $branchOne, $branchTwo, $managerOne, $patientOne, $service, $technician] = $this->makePackageFixtures();
        $patientTwo = $this->makePatient($company, $branchTwo, 'Other', 'Branch');

        $package = $this->createPackage($branchTwo, $service, $managerOne, [
            'name' => 'Other Branch Package',
        ]);

        $this->actingAs($managerOne)->get(route('packages.edit', $package))->assertNotFound();
        $this->actingAs($managerOne)->post(route('packages.freeze', $package))->assertNotFound();
        $this->actingAs($managerOne)->post(route('packages.purchases.store'), [
            'package_id' => $package->id,
            'patient_id' => $patientOne->id,
        ])->assertNotFound();

        $purchase = $this->createPatientPackage($branchTwo, $package, $patientTwo, $managerOne);

        $this->actingAs($managerOne)->post(route('appointments.store'), [
            'patient_id' => $patientTwo->id,
            'service_id' => $service->id,
            'branch_id' => $branchTwo->id,
            'patient_package_id' => $purchase->id,
            'scheduled_date' => today()->toDateString(),
            'scheduled_time' => '10:00',
            'visit_type' => Appointment::VISIT_TYPE_TECHNICIAN,
            'assigned_staff_id' => $technician->id,
        ])->assertNotFound();
    }

    public function test_price_cannot_be_changed_after_creation(): void
    {
        [, $branch, , $manager, , $service] = $this->makePackageFixtures();

        $package = $this->createPackage($branch, $service, $manager, [
            'original_price' => '400.00',
            'final_price' => '360.00',
            'discount_type' => Package::DISCOUNT_PERCENTAGE,
            'discount_value' => '10.00',
        ]);

        $response = $this->actingAs($manager)->put(route('packages.update', $package), [
            'name' => 'Edited Name',
            'expiry_date' => '2026-11-30',
            'notes' => 'Updated notes',
            'original_price' => '100.00',
        ]);

        $response->assertSessionHasErrors('original_price');

        $package = $package->fresh();

        $this->assertSame('400.00', $package->original_price);
        $this->assertSame('360.00', $package->final_price);
        $this->assertSame(Package::DISCOUNT_PERCENTAGE, $package->discount_type);
        $this->assertSame('10.00', $package->discount_value);
    }

    public function test_discount_and_final_price_are_calculated_correctly(): void
    {
        [, $branch, , $manager, $patient, $service] = $this->makePackageFixtures();

        $this->actingAs($manager)->post(route('packages.store'), [
            'service_id' => $service->id,
            'name' => 'Fixed Discount Package',
            'sessions_purchased' => 8,
            'original_price' => '500.00',
            'discount_type' => 'fixed',
            'discount_value' => '75.00',
        ])->assertRedirect(route('packages.index'));

        $package = Package::query()->where('name', 'Fixed Discount Package')->firstOrFail();

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'original_price' => '500.00',
            'discount_type' => 'fixed',
            'discount_value' => '75.00',
            'final_price' => '425.00',
        ]);

        $this->actingAs($manager)->post(route('packages.purchases.store'), [
            'package_id' => $package->id,
            'patient_id' => $patient->id,
        ])->assertRedirect(route('packages.index'));

        $this->assertDatabaseHas('patient_packages', [
            'package_id' => $package->id,
            'patient_id' => $patient->id,
            'final_price' => '425.00',
        ]);
    }

    public function test_frozen_package_cannot_be_used_on_completed_appointment(): void
    {
        [$company, $branch, , $manager, $patient, $service, $technician] = $this->makePackageFixtures();

        $package = $this->createPackage($branch, $service, $manager);
        $purchase = $this->createPatientPackage($branch, $package, $patient, $manager);

        $this->actingAs($manager)->post(route('packages.freeze', $package))
            ->assertRedirect(route('packages.index'));

        $appointment = $this->createAppointment($company, $branch, $patient, $service, $manager, $purchase);

        $response = $this->actingAs($manager)->patch(route('appointments.status', $appointment), [
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $response->assertSessionHasErrors('patient_package_id');
        $this->assertSame(0, $purchase->fresh()->sessions_used);
    }

    public function test_appointment_booking_can_attach_active_patient_package_without_deducting_on_booking(): void
    {
        [$company, $branch, , $manager, $patient, $service, $technician] = $this->makePackageFixtures();

        $package = $this->createPackage($branch, $service, $manager);
        $purchase = $this->createPatientPackage($branch, $package, $patient, $manager);

        $response = $this->actingAs($manager)->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'branch_id' => $branch->id,
            'patient_package_id' => $purchase->id,
            'scheduled_date' => today()->addDay()->toDateString(),
            'scheduled_time' => '11:00',
            'visit_type' => Appointment::VISIT_TYPE_TECHNICIAN,
            'assigned_staff_id' => $technician->id,
        ]);

        $response->assertRedirect(route('appointments.index'));

        $appointment = Appointment::query()->latest('id')->firstOrFail();

        $this->assertSame($purchase->id, $appointment->patient_package_id);
        $this->assertSame(0, $purchase->fresh()->sessions_used);
        $this->assertDatabaseCount('package_usages', 0);
    }

    public function test_usage_deducts_when_completed_appointment_is_attached_to_patient_package(): void
    {
        [$company, $branch, , $manager, $patient, $service] = $this->makePackageFixtures();

        $package = $this->createPackage($branch, $service, $manager, ['sessions_purchased' => 6]);
        $purchase = $this->createPatientPackage($branch, $package, $patient, $manager);
        $appointment = $this->createAppointment($company, $branch, $patient, $service, $manager, $purchase);

        $this->actingAs($manager)->patch(route('appointments.status', $appointment), [
            'status' => Appointment::STATUS_COMPLETED,
        ])->assertRedirect();

        $purchase = $purchase->fresh();

        $this->assertSame(1, $purchase->sessions_used);
        $this->assertSame(PatientPackage::STATUS_ACTIVE, $purchase->status);
        $this->assertDatabaseHas('package_usages', [
            'patient_package_id' => $purchase->id,
            'appointment_id' => $appointment->id,
            'sessions_consumed' => 1,
        ]);
    }

    public function test_exhausted_patient_package_status_is_set_correctly(): void
    {
        [$company, $branch, , $manager, $patient, $service] = $this->makePackageFixtures();

        $package = $this->createPackage($branch, $service, $manager, ['sessions_purchased' => 1]);
        $purchase = $this->createPatientPackage($branch, $package, $patient, $manager);
        $appointment = $this->createAppointment($company, $branch, $patient, $service, $manager, $purchase);

        $this->actingAs($manager)->patch(route('appointments.status', $appointment), [
            'status' => Appointment::STATUS_COMPLETED,
        ])->assertRedirect();

        $purchase = $purchase->fresh();

        $this->assertSame(1, $purchase->sessions_used);
        $this->assertSame(PatientPackage::STATUS_EXHAUSTED, $purchase->status);
    }

    private function makePackageFixtures(): array
    {
        $company = Company::create([
            'name' => 'MedFlow Test Clinic',
            'slug' => 'medflow-test-clinic',
            'currency' => 'JOD',
        ]);

        $branchOne = Branch::create([
            'company_id' => $company->id,
            'name' => 'Marina Branch',
            'code' => 'BR-001',
            'status' => 'active',
        ]);

        $branchTwo = Branch::create([
            'company_id' => $company->id,
            'name' => 'Jabal Branch',
            'code' => 'BR-002',
            'status' => 'active',
        ]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'branch_manager',
            'role' => 'branch_manager',
            'primary_branch_id' => $branchOne->id,
            'first_name' => 'Branch',
            'last_name' => 'Manager',
        ]);

        $technician = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'technician',
            'role' => 'technician',
            'primary_branch_id' => $branchOne->id,
            'first_name' => 'Laser',
            'last_name' => 'Tech',
        ]);

        $patient = $this->makePatient($company, $branchOne, 'Sara', 'Ali');

        $service = Service::create([
            'company_id' => $company->id,
            'name' => 'Laser Full Body',
            'duration_minutes' => 45,
            'price' => '50.00',
            'is_active' => true,
        ]);

        return [$company, $branchOne, $branchTwo, $manager, $patient, $service, $technician];
    }

    private function makePatient(Company $company, Branch $branch, string $firstName, string $lastName): Patient
    {
        return Patient::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName).'.'.strtolower($lastName).'@test.com',
            'phone' => '0770000000',
            'date_of_birth' => '1995-01-01',
            'gender' => 'female',
            'source' => 'instagram',
            'status' => 'active',
            'registration_date' => now()->toDateString(),
            'consent_given' => true,
            'consent_given_at' => now(),
        ]);
    }

    private function createPackage(Branch $branch, Service $service, User $manager, array $overrides = []): Package
    {
        return Package::create(array_merge([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'name' => 'Core Package',
            'sessions_purchased' => 5,
            'original_price' => '250.00',
            'discount_type' => null,
            'discount_value' => null,
            'final_price' => '250.00',
            'status' => Package::STATUS_ACTIVE,
            'created_by' => $manager->id,
        ], $overrides));
    }

    private function createPatientPackage(Branch $branch, Package $package, Patient $patient, User $manager, array $overrides = []): PatientPackage
    {
        return PatientPackage::create(array_merge([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'patient_id' => $patient->id,
            'sessions_purchased' => $package->sessions_purchased,
            'sessions_used' => 0,
            'final_price' => $package->final_price,
            'status' => PatientPackage::STATUS_ACTIVE,
            'purchased_at' => now(),
            'purchased_by' => $manager->id,
        ], $overrides));
    }

    private function createAppointment(Company $company, Branch $branch, Patient $patient, Service $service, User $manager, PatientPackage $purchase): Appointment
    {
        return Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
            'patient_package_id' => $purchase->id,
            'service_id' => $service->id,
            'assigned_staff_id' => $manager->id,
            'booked_by' => $manager->id,
            'appointment_type' => 'treatment',
            'visit_type' => Appointment::VISIT_TYPE_TECHNICIAN,
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 30,
            'status' => Appointment::STATUS_SCHEDULED,
        ]);
    }
}
