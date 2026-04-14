<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegisterSession;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_user_can_open_a_cash_register_session(): void
    {
        [$user, $branch] = $this->makeFinanceUser();

        $response = $this->actingAs($user)->post(route('finance.register.open'), [
            'opening_balance' => '125.00',
            'notes' => 'Morning float',
        ]);

        $response->assertRedirect(route('finance'));

        $this->assertDatabaseHas('cash_register_sessions', [
            'branch_id' => $branch->id,
            'opening_balance' => '125.00',
            'status' => CashRegisterSession::STATUS_OPEN,
            'opened_by' => $user->id,
        ]);
    }

    public function test_branch_cannot_have_two_open_register_sessions(): void
    {
        [$user, $branch] = $this->makeFinanceUser();

        CashRegisterSession::create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'opening_balance' => 100,
            'expected_closing_balance' => 100,
            'status' => CashRegisterSession::STATUS_OPEN,
            'opened_at' => now()->subHour(),
            'opened_by' => $user->id,
        ]);

        $response = $this->from(route('finance'))->actingAs($user)->post(route('finance.register.open'), [
            'opening_balance' => '75.00',
        ]);

        $response->assertRedirect(route('finance'));
        $response->assertSessionHasErrors('opening_balance');
        $this->assertDatabaseCount('cash_register_sessions', 1);
    }

    public function test_finance_user_can_close_register_and_capture_variance_and_totals(): void
    {
        [$user, $branch, $plan] = $this->makeFinanceUser();

        $session = CashRegisterSession::create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'opening_balance' => 100,
            'expected_closing_balance' => 100,
            'status' => CashRegisterSession::STATUS_OPEN,
            'opened_at' => now()->subHours(2),
            'opened_by' => $user->id,
        ]);

        Transaction::create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'patient_id' => $plan->patient_id,
            'treatment_plan_id' => $plan->id,
            'appointment_id' => null,
            'cash_register_session_id' => $session->id,
            'amount' => 150,
            'amount_received' => 200,
            'change_returned' => 50,
            'transaction_type' => Transaction::TYPE_PAYMENT,
            'payment_method' => Transaction::METHOD_CASH,
            'received_at' => now()->subHour(),
            'received_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('finance.register.close', $session->id), [
            'closing_balance' => '248.00',
            'closing_notes' => 'Counted before shift handoff',
        ]);

        $response->assertRedirect(route('finance'));

        $this->assertDatabaseHas('cash_register_sessions', [
            'id' => $session->id,
            'status' => CashRegisterSession::STATUS_CLOSED,
            'cash_sales_total' => '150.00',
            'cash_received_total' => '200.00',
            'change_returned_total' => '50.00',
            'expected_closing_balance' => '250.00',
            'closing_balance' => '248.00',
            'variance' => '-2.00',
            'closed_by' => $user->id,
        ]);
    }

    private function makeFinanceUser(): array
    {
        $company = Company::create([
            'name' => 'MedFlow Test Clinic',
            'slug' => 'medflow-test-clinic',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'code' => 'BR-001',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'finance',
            'role' => 'finance',
            'primary_branch_id' => $branch->id,
            'first_name' => 'Finance',
            'last_name' => 'User',
        ]);

        $service = Service::create([
            'company_id' => $company->id,
            'name' => 'Laser Session',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'phone' => '123456789',
            'status' => 'active',
            'registration_date' => now()->toDateString(),
        ]);

        $plan = TreatmentPlan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'name' => 'Cash Register Plan',
            'total_sessions' => 4,
            'completed_sessions' => 1,
            'status' => 'active',
            'total_price' => 400,
            'amount_paid' => 100,
            'created_by' => $user->id,
        ]);

        return [$user, $branch, $plan];
    }
}
