<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        // Define all permissions: module => [actions]
        $permissionMap = [
            'dashboard'    => ['view'],
            'customers'    => ['view', 'create', 'edit', 'delete', 'export'],
            'medical_info' => ['view', 'edit'],
            'appointments' => ['view', 'create', 'edit', 'delete', 'approve'],
            'sessions'     => ['view', 'create', 'edit'],
            'treatment_plans' => ['view', 'create', 'edit', 'delete'],
            'notes'        => ['view', 'create', 'edit', 'delete'],
            'follow_ups'   => ['view', 'create', 'edit'],
            'leads'        => ['view', 'create', 'edit', 'delete'],
            'employees'    => ['view', 'create', 'edit', 'delete', 'manage'],
            'branches'     => ['view', 'create', 'edit', 'delete', 'manage'],
            'roles'        => ['view', 'create', 'edit', 'delete'],
            'services'     => ['view', 'create', 'edit', 'delete'],
            'reports'      => ['view', 'export'],
            'billing'      => ['view', 'edit'],
            'settings'     => ['view', 'edit', 'manage'],
            'activity_logs' => ['view'],
        ];

        // Create permissions
        $permissions = [];
        foreach ($permissionMap as $module => $actions) {
            foreach ($actions as $action) {
                $permission = Permission::firstOrCreate(
                    ['module' => $module, 'action' => $action],
                    ['display_name' => ucfirst($action) . ' ' . ucwords(str_replace('_', ' ', $module))]
                );
                $permissions[$module][$action] = $permission->id;
            }
        }

        // Define roles and their permissions
        $roles = [
            'super_admin' => [
                'display_name' => 'Super Admin',
                'color'        => '#DC2626',
                'is_system'    => true,
                'description'  => 'Full system access across all branches',
                'permissions'  => '*', // all permissions
            ],
            'branch_manager' => [
                'display_name' => 'Branch Manager',
                'color'        => '#7C3AED',
                'is_system'    => true,
                'description'  => 'Full access to assigned branch operations',
                'permissions'  => [
                    'dashboard'       => ['view'],
                    'customers'       => ['view', 'create', 'edit', 'export'],
                    'medical_info'    => ['view', 'edit'],
                    'appointments'    => ['view', 'create', 'edit', 'delete', 'approve'],
                    'sessions'        => ['view', 'create', 'edit'],
                    'treatment_plans' => ['view', 'create', 'edit'],
                    'notes'           => ['view', 'create', 'edit', 'delete'],
                    'follow_ups'      => ['view', 'create', 'edit'],
                    'leads'           => ['view', 'create', 'edit'],
                    'employees'       => ['view', 'create', 'edit'],
                    'services'        => ['view'],
                    'reports'         => ['view', 'export'],
                    'billing'         => ['view'],
                    'activity_logs'   => ['view'],
                ],
            ],
            'secretary' => [
                'display_name' => 'Secretary / Receptionist',
                'color'        => '#0891B2',
                'is_system'    => true,
                'description'  => 'Front desk: register customers, book appointments',
                'permissions'  => [
                    'dashboard'       => ['view'],
                    'customers'       => ['view', 'create', 'edit'],
                    'medical_info'    => ['view', 'edit'],
                    'appointments'    => ['view', 'create', 'edit'],
                    'treatment_plans' => ['view'],
                    'notes'           => ['view', 'create'],
                    'follow_ups'      => ['view', 'create'],
                    'leads'           => ['view', 'create', 'edit'],
                ],
            ],
            'technician' => [
                'display_name' => 'Technician / Specialist',
                'color'        => '#059669',
                'is_system'    => true,
                'description'  => 'Treatment delivery and session recording',
                'permissions'  => [
                    'dashboard'       => ['view'],
                    'customers'       => ['view'],
                    'medical_info'    => ['view'],
                    'appointments'    => ['view'],
                    'sessions'        => ['view', 'create', 'edit'],
                    'treatment_plans' => ['view'],
                    'notes'           => ['view', 'create', 'edit'],
                    'follow_ups'      => ['view', 'create'],
                ],
            ],
            'doctor' => [
                'display_name' => 'Doctor / Specialist',
                'color'        => '#2563EB',
                'is_system'    => true,
                'description'  => 'Clinical oversight and medical decisions',
                'permissions'  => [
                    'dashboard'       => ['view'],
                    'customers'       => ['view', 'edit'],
                    'medical_info'    => ['view', 'edit'],
                    'appointments'    => ['view', 'create', 'edit'],
                    'sessions'        => ['view', 'create', 'edit'],
                    'treatment_plans' => ['view', 'create', 'edit'],
                    'notes'           => ['view', 'create', 'edit'],
                    'follow_ups'      => ['view', 'create', 'edit'],
                ],
            ],
            'finance' => [
                'display_name' => 'Finance',
                'color'        => '#D97706',
                'is_system'    => true,
                'description'  => 'Billing and financial reporting only',
                'permissions'  => [
                    'dashboard' => ['view'],
                    'customers' => ['view'],
                    'billing'   => ['view', 'edit'],
                    'reports'   => ['view', 'export'],
                ],
            ],
        ];

        $allPermissionIds = Permission::pluck('id')->toArray();

        foreach ($roles as $name => $data) {
            $role = Role::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                [
                    'display_name' => $data['display_name'],
                    'color'        => $data['color'],
                    'is_system'    => $data['is_system'],
                    'description'  => $data['description'],
                ]
            );

            if ($data['permissions'] === '*') {
                $role->permissions()->sync($allPermissionIds);
            } else {
                $ids = [];
                foreach ($data['permissions'] as $module => $actions) {
                    foreach ($actions as $action) {
                        if (isset($permissions[$module][$action])) {
                            $ids[] = $permissions[$module][$action];
                        }
                    }
                }
                $role->permissions()->sync($ids);
            }
        }
    }
}
