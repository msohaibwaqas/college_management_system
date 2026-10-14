<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Roles;
use Illuminate\Support\Facades\DB;

class NewClerkRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds new clerk roles: hssc_clerk and bs_clerk
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'hssc_clerk',
                'description' => 'Clerk for HSSC (Intermediate) section - manages intermediate student records and results',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'bs_clerk',
                'description' => 'Clerk for BS/ADP Higher Education section - manages BS and ADP student records, results, and datesheets',
                'guard_name' => 'web',
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $roleData) {
            // Use updateOrCreate to avoid duplicates
            Roles::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        $this->command->info('New clerk roles (hssc_clerk, bs_clerk) created successfully!');
    }
}
