<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Roles;
use Illuminate\Support\Facades\DB;

class AdditionalRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds roles: hssc_admin, bs_admin, hssc_accountant, bs_accountant
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'hssc_admin',
                'description' => 'Administrator for HSSC (Intermediate) section - full management access for intermediate',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'bs_admin',
                'description' => 'Administrator for BS/ADP Higher Education section - full management access for HE',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'hssc_accountant',
                'description' => 'Accountant for HSSC (Intermediate) section - financial management for intermediate',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'bs_accountant',
                'description' => 'Accountant for BS/ADP Higher Education section - financial management for HE',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $roleData) {
            Roles::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        $this->command->info('Additional roles (hssc_admin, bs_admin, hssc_accountant, bs_accountant) created successfully!');
    }
}
