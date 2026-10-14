<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Roles;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrator with full system access',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'principal',
                'description' => 'College Principal with management access',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'teacher',
                'description' => 'Teaching staff with student and result management',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'clerk',
                'description' => 'Administrative clerk with limited access',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'accountant',
                'description' => 'Financial management access',
                'guard_name' => 'web',
                'is_system_role' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            Roles::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('Roles seeded successfully!');
    }
}
