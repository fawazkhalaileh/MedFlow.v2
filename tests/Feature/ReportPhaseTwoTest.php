<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\EmployeeCommissionRule;
use App\Models\EmployeeCompensationProfile;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Models\TreatmentPlan;
use App\Models\TreatmentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_user_can_open_reports_hub_from_reports_route(): void
    {
        [$company, $branch, $financeUser] = $this->makeFinanceBranchContext();

        $response = $this->actingAs($financeUser)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Reports');
        $response->assertSee('Accounting Reports');
        $response->assertSee('Technician Performance');
        $response->assertSee('Commissions');
    }

    public function test_technician_performance_route_self_scopes_for_technician_users(): void
    {
        [$company, $branch, $financeUser] = $this->makeFinanceBranchContext();
        $service = $this->makeService($company);
        $technicianOne = $this->makeEmployee($company, $branch, 'technician', 'Tech', 'One');
        $technicianTwo = $this->makeEmployee($company, $branch, 'technician', 'Tech', 'Two');

        $this->makeCompletedSession($company, $branch, $service, $technicianOne, 'Self');
        $this->makeCompletedSession($company, $branch, $service, $technicianTwo, 'Other');

        $response = $this->actingAs($technicianOne)->get(route('reports.technician-performance', [
            'employee_id' => $technicianTwo->id,
        ]));

        $response->assertOk();
        $response->assertSee($technicianOne->full_name);
        $response->assertDontSee($technicianTwo->full_name);
    }

    public function test_commission_report_calculates_salary_plus_percentage_commission(): void
    {
        [$company, $branch, $financeUser] = $this->makeFinanceBranchContext();
        $service = $this->makeService($company);
        $technician = $this->makeEmployee($company, $branch, 'technician', 'Laser', 'Tech');
        $this->makeCompletedSession($company, $branch, $service, $technician, 'Commission');

        EmployeeCompensationProfile::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'employee_id' => $technician->id,
            'compensation_type' => EmployeeCompensationProfile::TYPE_SALARY_PLUS_COMMISSION,
            'fixed_salary' => 500,
            'is_active' => true,
            'created_by' => $financeUser->id,
        ]);

        EmployeeCommissionRule::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'employee_id' => $technician->id,
            'rule_scope' => EmployeeCommissionRule::SCOPE_EMPLOYEE_BRANCH,
            'source_type' => EmployeeCommissionRule::SOURCE_COMPLETED_SERVICE,
            'calculation_type' => EmployeeCommissionRule::CALC_PERCENTAGE,
            'rate' => 10,
            'priority' => 1,
            'is_active' => true,
            'created_by' => $financeUser->id,
        ]);

        $response = $this->actingAs($financeUser)->get(route('reports.commissions', [
            'employee_id' => $technician->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee($technician->full_name);
        $response->assertSee('JOD 500.00');
        $response->assertSee('JOD 10.00');
        $response->assertSee('JOD 510.00');
    }

    public function test_finance_user_can_generate_compensation_snapshots(): void
    {
        [$company, $branch, $financeUser] = $this->makeFinanceBranchContext();
        $service = $this->makeService($company);
        $technician = $this->makeEmployee($company, $branch, 'technician', 'Snapshot', 'Tech');
        $this->makeCompletedSession($company, $branch, $service, $technician, 'Snapshot');

        EmployeeCompensationProfile::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'employee_id' => $technician->id,
            'compensation_type' => EmployeeCompensationProfile::TYPE_SALARY_ONLY,
            'fixed_salary' => 700,
            'is_active' => true,
            'created_by' => $financeUser->id,
        ]);

        $response = $this->actingAs($financeUser)->post(route('reports.commissions.snapshots.store'), [
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('compensation_snapshots', [
            'company_id' => $company->id,
            'employee_id' => $technician->id,
            'fixed_salary' => '700.00',
            'total_due' => '700.00',
        ]);
    }

    private function makeFinanceBranchContext(): array
    {
        $company = Company::create([
            'name' => 'MedFlow Test Clinic',
            'slug' => 'medflow-test-clinic',
            'currency' => 'JOD',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'code' => 'BR-001',
            'status' => 'active',
        ]);

        $financeUser = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'finance',
            'role' => 'finance',
            'primary_branch_id' => $branch->id,
            'first_name' => 'Finance',
            'last_name' => 'User',
        ]);

        return [$company, $branch, $financeUser];
    }

    private function makeEmployee(Company $company, Branch $branch, string $type, string $firstName, string $lastName): User
    {
        return User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => $type,
            'role' => $type,
            'primary_branch_id' => $branch->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'employment_status' => 'active',
        ]);
    }

    private function makeService(Company $company): Service
    {
        return Service::create([
            'company_id' => $company->id,
            'name' => 'Laser Basic',
            'duration_minutes' => 30,
            'price' => 100,
            'is_active' => true,
        ]);
    }

    private function makeCompletedSession(Company $company, Branch $branch, Service $service, User $technician, string $prefix): void
    {
        $patient = Patient::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'first_name' => $prefix,
            'last_name' => 'Patient',
            'email' => strtolower($prefix) . '@test.com',
            'phone' => '0770000000',
            'status' => 'active',
            'registration_date' => now()->toDateString(),
        ]);

        $plan = TreatmentPlan::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
            'service_id' => $service->id,
            'name' => $prefix . ' Plan',
            'total_sessions' => 6,
            'completed_sessions' => 1,
            'status' => 'active',
            'total_price' => 600,
            'amount_paid' => 200,
            'created_by' => $technician->id,
        ]);

        TreatmentSession::create([
            'treatment_plan_id' => $plan->id,
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'technician_id' => $technician->id,
            'session_number' => 1,
            'started_at' => now()->subDay(),
            'ended_at' => now()->subDay()->addMinutes(30),
            'status' => 'completed',
        ]);
    }
}
