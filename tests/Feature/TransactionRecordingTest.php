<?php

namespace Tests\Feature;

use App\Models\Appointment;
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

class TransactionRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_user_can_record_cash_payment_and_change_for_a_plan_when_register_is_open(): void
    {
        [$user, $plan, $appointment, $branch] = $this->makeFinanceContext();
        $registerSession = $this->openRegister($user, $branch, 100);

        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $plan->id,
            'appointment_id'    => $appointment->id,
            'transaction_type'  => Transaction::TYPE_PAYMENT,
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
            'cash_register_session_id' => $registerSession->id,
            'transaction_type'  => Transaction::TYPE_PAYMENT,
            'amount'            => '150.00',
            'amount_received'   => '200.00',
            'change_returned'   => '50.00',
            'payment_method'    => Transaction::METHOD_CASH,
            'received_by'       => $user->id,
        ]);

        $transaction = Transaction::query()->latest('id')->first();

        $this->assertNotNull($transaction->receipt_number);
        $this->assertMatchesRegularExpression(
            '/^RCPT-BR001-\d{8}-\d{6}$/',
            $transaction->receipt_number
        );

        $registerSession->refresh();
        $this->assertSame('250.00', $registerSession->expected_closing_balance);
        $this->assertSame('150.00', $registerSession->cash_sales_total);
        $this->assertSame('200.00', $registerSession->cash_received_total);
        $this->assertSame('50.00', $registerSession->change_returned_total);
        $this->assertSame('350.00', $plan->fresh()->amount_paid);
    }

    public function test_cash_payment_requires_an_open_register_session(): void
    {
        [$user, $plan, $appointment] = $this->makeFinanceContext();

        $response = $this->from(route('finance'))->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $plan->id,
            'appointment_id'    => $appointment->id,
            'transaction_type'  => Transaction::TYPE_PAYMENT,
            'amount'            => '100.00',
            'amount_received'   => '100.00',
            'payment_method'    => Transaction::METHOD_CASH,
        ]);

        $response->assertRedirect(route('finance'));
        $response->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_finance_user_can_record_non_cash_payment_without_register(): void
    {
        [$user, $plan, $appointment] = $this->makeFinanceContext();

        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $plan->id,
            'appointment_id'    => $appointment->id,
            'transaction_type'  => Transaction::TYPE_PAYMENT,
            'amount'            => '120.00',
            'amount_received'   => '120.00',
            'payment_method'    => Transaction::METHOD_CARD,
        ]);

        $response->assertRedirect(route('finance'));

        $this->assertDatabaseHas('transactions', [
            'treatment_plan_id' => $plan->id,
            'payment_method'    => Transaction::METHOD_CARD,
            'cash_register_session_id' => null,
        ]);

        $transaction = Transaction::query()->latest('id')->first();

        $this->assertMatchesRegularExpression(
            '/^RCPT-BR001-\d{8}-\d{6}$/',
            $transaction->receipt_number
        );
    }

    public function test_finance_user_cannot_record_payment_for_a_plan_in_another_branch(): void
    {
        [$user] = $this->makeFinanceContext();
        [, $otherPlan] = $this->makeFinanceContext(branchCode: 'BR-002', userEmail: 'finance-2@example.com', patientEmail: 'patient-2@example.com');

        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $otherPlan->id,
            'transaction_type'  => Transaction::TYPE_PAYMENT,
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
            'transaction_type'  => Transaction::TYPE_PAYMENT,
            'amount'            => '600.00',
            'amount_received'   => '600.00',
            'payment_method'    => Transaction::METHOD_TRANSFER,
        ]);

        $response->assertRedirect(route('finance'));
        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(300.0, $plan->fresh()->amount_remaining);
    }

    public function test_refund_transaction_type_is_rejected(): void
    {
        [$user, $plan, $appointment] = $this->makeFinanceContext();

        $response = $this->from(route('finance'))->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $plan->id,
            'appointment_id'    => $appointment->id,
            'transaction_type'  => 'refund',
            'amount'            => '50.00',
            'amount_received'   => '50.00',
            'payment_method'    => Transaction::METHOD_CARD,
        ]);

        $response->assertRedirect(route('finance'));
        $response->assertSessionHasErrors('transaction_type');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_finance_user_can_open_a_pdf_receipt_for_a_payment_in_the_same_branch(): void
    {
        [$user, $plan, $appointment, $branch] = $this->makeFinanceContext();
        $this->openRegister($user, $branch, 75);

        $this->actingAs($user)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $plan->id,
            'appointment_id' => $appointment->id,
            'transaction_type' => Transaction::TYPE_PAYMENT,
            'amount' => '90.00',
            'amount_received' => '100.00',
            'payment_method' => Transaction::METHOD_CASH,
        ]);

        $transaction = Transaction::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('finance.transactions.receipt', $transaction->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString($transaction->receiptFilename(), $response->headers->get('content-disposition'));
    }

    public function test_finance_user_cannot_open_a_receipt_for_another_branch_transaction(): void
    {
        [$user] = $this->makeFinanceContext();
        [$otherUser, $otherPlan, $otherAppointment, $otherBranch] = $this->makeFinanceContext(
            branchCode: 'BR-002',
            userEmail: 'finance-2@example.com',
            patientEmail: 'patient-2@example.com'
        );

        $this->openRegister($otherUser, $otherBranch, 50);

        $this->actingAs($otherUser)->post(route('finance.transactions.store'), [
            'treatment_plan_id' => $otherPlan->id,
            'appointment_id' => $otherAppointment->id,
            'transaction_type' => Transaction::TYPE_PAYMENT,
            'amount' => '80.00',
            'amount_received' => '80.00',
            'payment_method' => Transaction::METHOD_CARD,
        ]);

        $otherTransaction = Transaction::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('finance.transactions.receipt', $otherTransaction->id));

        $response->assertNotFound();
    }

    public function test_receipt_route_is_limited_to_payment_transactions(): void
    {
        [$user, $plan, $appointment] = $this->makeFinanceContext();

        $transaction = Transaction::create([
            'company_id' => $plan->company_id,
            'branch_id' => $plan->branch_id,
            'patient_id' => $plan->patient_id,
            'treatment_plan_id' => $plan->id,
            'appointment_id' => $appointment->id,
            'amount' => '50.00',
            'amount_received' => '50.00',
            'change_returned' => '0.00',
            'transaction_type' => 'adjustment',
            'payment_method' => Transaction::METHOD_CARD,
            'received_at' => now(),
            'received_by' => $user->id,
            'receipt_number' => Transaction::makeReceiptNumber('BR-001', now(), 1),
        ]);

        $transaction->update([
            'receipt_number' => Transaction::makeReceiptNumber('BR-001', $transaction->received_at, $transaction->id),
        ]);

        $response = $this->actingAs($user)->get(route('finance.transactions.receipt', $transaction->id));

        $response->assertNotFound();
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

        return [$user, $plan, $appointment, $branch];
    }

    private function openRegister(User $user, Branch $branch, float $openingBalance = 0): CashRegisterSession
    {
        return CashRegisterSession::create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'opening_balance' => $openingBalance,
            'expected_closing_balance' => $openingBalance,
            'status' => CashRegisterSession::STATUS_OPEN,
            'opened_at' => now()->subHour(),
            'opened_by' => $user->id,
        ]);
    }
}
