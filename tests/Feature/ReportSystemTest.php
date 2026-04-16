<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Company;
use App\Models\Expense;
use App\Models\FollowUp;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use App\Models\TreatmentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_scoped_accounting_report_only_shows_same_branch_data(): void
    {
        [$company, $branchOne, $branchTwo, $financeUser, $service] = $this->makeReportUsers();
        [$patientOne, $planOne] = $this->makePatientPlanContext($company, $branchOne, $service, $financeUser, 'Same');
        [$patientTwo, $planTwo] = $this->makePatientPlanContext($company, $branchTwo, $service, $financeUser, 'Other');

        Transaction::create([
            'company_id' => $company->id,
            'branch_id' => $branchOne->id,
            'patient_id' => $patientOne->id,
            'treatment_plan_id' => $planOne->id,
            'amount' => '100.00',
            'amount_received' => '100.00',
            'change_returned' => '0.00',
            'transaction_type' => Transaction::TYPE_PAYMENT,
            'payment_method' => Transaction::METHOD_CARD,
            'received_at' => now(),
            'received_by' => $financeUser->id,
            'receipt_number' => 'RCPT-BR001-20260415-000001',
        ]);

        Transaction::create([
            'company_id' => $company->id,
            'branch_id' => $branchTwo->id,
            'patient_id' => $patientTwo->id,
            'treatment_plan_id' => $planTwo->id,
            'amount' => '400.00',
            'amount_received' => '400.00',
            'change_returned' => '0.00',
            'transaction_type' => Transaction::TYPE_PAYMENT,
            'payment_method' => Transaction::METHOD_CARD,
            'received_at' => now(),
            'received_by' => $financeUser->id,
            'receipt_number' => 'RCPT-BR002-20260415-000002',
        ]);

        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branchOne->id,
            'category' => 'Supplies',
            'title' => 'Cooling gel restock',
            'amount' => '25.00',
            'expense_date' => now()->toDateString(),
            'created_by' => $financeUser->id,
        ]);

        $response = $this->actingAs($financeUser)->get(route('reports.accounting'));

        $response->assertOk();
        $response->assertSee('Same Patient');
        $response->assertDontSee('Other Patient');
        $response->assertSee('JOD 100.00');
    }

    public function test_system_admin_can_export_inventory_report_as_csv(): void
    {
        [$company, $branchOne, , , $service] = $this->makeReportUsers();
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'system_admin',
            'role' => 'admin',
            'primary_branch_id' => $branchOne->id,
            'first_name' => 'System',
            'last_name' => 'Admin',
        ]);

        $item = InventoryItem::create([
            'company_id' => $company->id,
            'name' => 'Test Syringe',
            'sku' => 'SYR-001',
            'unit' => 'box',
        ]);

        $inventory = BranchInventory::create([
            'company_id' => $company->id,
            'branch_id' => $branchOne->id,
            'inventory_item_id' => $item->id,
            'low_stock_threshold' => 2,
        ]);

        InventoryBatch::create([
            'company_id' => $company->id,
            'branch_inventory_id' => $inventory->id,
            'batch_number' => 'B-01',
            'received_on' => now()->toDateString(),
            'quantity_received' => 4,
            'quantity_remaining' => 4,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.inventory.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Test Syringe', $response->streamedContent());
    }

    public function test_finance_patient_history_hides_clinical_session_details_but_shows_payments(): void
    {
        [$company, $branchOne, , $financeUser, $service] = $this->makeReportUsers();
        [$patient, $plan] = $this->makePatientPlanContext($company, $branchOne, $service, $financeUser, 'History');

        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branchOne->id,
            'patient_id' => $patient->id,
            'treatment_plan_id' => $plan->id,
            'service_id' => $service->id,
            'booked_by' => $financeUser->id,
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 30,
            'status' => Appointment::STATUS_COMPLETED,
            'completed_at' => now()->subHours(2),
        ]);

        TreatmentSession::create([
            'appointment_id' => $appointment->id,
            'treatment_plan_id' => $plan->id,
            'patient_id' => $patient->id,
            'branch_id' => $branchOne->id,
            'service_id' => $service->id,
            'technician_id' => $financeUser->id,
            'session_number' => 1,
            'started_at' => now()->subDay()->subMinutes(30),
            'ended_at' => now()->subDay(),
            'device_used' => 'Candela',
            'recommendations' => 'Avoid sun exposure',
            'shots_count' => 120,
        ]);

        Transaction::create([
            'company_id' => $company->id,
            'branch_id' => $branchOne->id,
            'patient_id' => $patient->id,
            'treatment_plan_id' => $plan->id,
            'appointment_id' => $appointment->id,
            'amount' => '90.00',
            'amount_received' => '90.00',
            'change_returned' => '0.00',
            'transaction_type' => Transaction::TYPE_PAYMENT,
            'payment_method' => Transaction::METHOD_CARD,
            'received_at' => now(),
            'received_by' => $financeUser->id,
            'receipt_number' => 'RCPT-BR001-20260415-000003',
        ]);

        $response = $this->actingAs($financeUser)->get(route('reports.patients.history', $patient));

        $response->assertOk();
        $response->assertSee('Payment / receipt');
        $response->assertDontSee('Candela');
        $response->assertDontSee('Avoid sun exposure');
    }

    public function test_patient_history_is_not_accessible_across_branches_for_branch_scoped_users(): void
    {
        [$company, $branchOne, $branchTwo, $financeUser, $service] = $this->makeReportUsers();
        [$otherPatient] = $this->makePatientPlanContext($company, $branchTwo, $service, $financeUser, 'Blocked');

        $response = $this->actingAs($financeUser)->get(route('reports.patients.history', $otherPatient));

        $response->assertNotFound();
    }

    private function makeReportUsers(): array
    {
        $company = Company::create([
            'name' => 'MedFlow Test Clinic',
            'slug' => 'medflow-test-clinic',
            'currency' => 'JOD',
        ]);

        $branchOne = Branch::create([
            'company_id' => $company->id,
            'name' => 'Branch One',
            'code' => 'BR-001',
            'status' => 'active',
        ]);

        $branchTwo = Branch::create([
            'company_id' => $company->id,
            'name' => 'Branch Two',
            'code' => 'BR-002',
            'status' => 'active',
        ]);

        $financeUser = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'finance',
            'role' => 'finance',
            'primary_branch_id' => $branchOne->id,
            'first_name' => 'Branch',
            'last_name' => 'Finance',
        ]);

        $service = Service::create([
            'company_id' => $company->id,
            'name' => 'Laser Basic',
            'duration_minutes' => 30,
            'price' => 100,
            'is_active' => true,
        ]);

        return [$company, $branchOne, $branchTwo, $financeUser, $service];
    }

    private function makePatientPlanContext(Company $company, Branch $branch, Service $service, User $user, string $namePrefix): array
    {
        $patient = Patient::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'first_name' => $namePrefix,
            'last_name' => 'Patient',
            'email' => strtolower($namePrefix) . '@test.com',
            'phone' => '0770000000',
            'status' => 'active',
            'registration_date' => now()->toDateString(),
        ]);

        $plan = TreatmentPlan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'name' => $namePrefix . ' Plan',
            'total_sessions' => 6,
            'completed_sessions' => 1,
            'status' => 'active',
            'total_price' => 600,
            'amount_paid' => 200,
            'created_by' => $user->id,
        ]);

        return [$patient, $plan];
    }
}
