<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_user_can_record_payment_and_change_for_a_plan(): void
    {
        [$user, $plan, $appointment] = $this->makeFinanceContext();

        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $plan->id,
            'appointment_id'    => $appointment->id,
            'amount'            => '150.00',
            'amount_received'   => '200.00',
            'payment_method'    => Transaction::METHOD_CASH,
            'reference_number'  => 'CASH-200',
            'notes'             => 'Cash collected at front desk.',
        ]);

        $response->assertRedirect(route('finance'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'treatment_plan_id' => $plan->id,
            'appointment_id'    => $appointment->id,
            'branch_id'         => $plan->branch_id,
            'amount'            => '150.00',
            'amount_received'   => '200.00',
            'change_returned'   => '50.00',
            'payment_method'    => Transaction::METHOD_CASH,
            'received_by'       => $user->id,
        ]);

        $this->assertSame('350.00', $plan->fresh()->amount_paid);
    }

    public function test_finance_user_cannot_record_payment_for_a_plan_in_another_branch(): void
    {
        [$user] = $this->makeFinanceContext();
        [, $otherPlan] = $this->makeFinanceContext(branchCode: 'BR-002', userEmail: 'finance-2@example.com', patientEmail: 'patient-2@example.com');

        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $otherPlan->id,
            'amount'            => '50.00',
            'amount_received'   => '50.00',
            'payment_method'    => Transaction::METHOD_CARD,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_payment_amount_cannot_exceed_remaining_balance(): void
    {
        [$user, $plan] = $this->makeFinanceContext();

        $response = $this->from(route('finance'))->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $plan->id,
            'amount'            => '600.00',
            'amount_received'   => '600.00',
            'payment_method'    => Transaction::METHOD_TRANSFER,
        ]);

        $response->assertRedirect(route('finance'));
        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(300.0, $plan->fresh()->amount_remaining);
    }

    private function makeFinanceContext(
        string $branchCode = 'BR-001',
        string $userEmail = 'finance@example.com',
        string $patientEmail = 'patient@example.com'
    ): array {
        $company = Company::first() ?? Company::create([
            'name' => 'MedFlow Test Clinic',
            'slug' => 'medflow-test-clinic',
        ]);

        $branch = Branch::firstOrCreate(
            ['code' => $branchCode],
            [
                'company_id' => $company->id,
                'name'       => "Branch {$branchCode}",
            ]
        );

        $user = User::factory()->create([
            'company_id'        => $company->id,
            'email'             => $userEmail,
            'employee_type'     => 'finance',
            'role'              => 'finance',
            'primary_branch_id' => $branch->id,
            'first_name'        => 'Finance',
            'last_name'         => $branchCode,
        ]);

        $service = Service::firstOrCreate(
            ['company_id' => $company->id, 'name' => "Laser {$branchCode}"],
            ['duration_minutes' => 30, 'is_active' => true]
        );

        $patient = Patient::create([
            'company_id'        => $company->id,
            'branch_id'         => $branch->id,
            'first_name'        => 'Test',
            'last_name'         => $branchCode,
            'email'             => $patientEmail,
            'phone'             => '123456789',
            'status'            => 'active',
            'registration_date' => now()->toDateString(),
        ]);

        $plan = TreatmentPlan::create([
            'company_id'         => $company->id,
            'branch_id'          => $branch->id,
            'patient_id'         => $patient->id,
            'service_id'         => $service->id,
            'name'               => "Plan {$branchCode}",
            'total_sessions'     => 6,
            'completed_sessions' => 2,
            'status'             => 'active',
            'total_price'        => 500,
            'amount_paid'        => 200,
            'created_by'         => $user->id,
        ]);

        $appointment = Appointment::create([
            'company_id'        => $company->id,
            'branch_id'         => $branch->id,
            'patient_id'        => $patient->id,
            'treatment_plan_id' => $plan->id,
            'service_id'        => $service->id,
            'booked_by'         => $user->id,
            'scheduled_at'      => now()->subDay(),
            'duration_minutes'  => 30,
            'status'            => Appointment::STATUS_COMPLETED,
            'completed_at'      => now()->subHours(2),
        ]);

        return [$user, $plan, $appointment];
    }
}
