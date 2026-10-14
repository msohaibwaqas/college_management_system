<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Staff;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get staff with ID 1
        $staff = Staff::find(28);
        
        if (!$staff) {
            $this->command->error('Staff with ID 28 not found! Please create a staff member first.');
            return;
        }

        // Create or update test user
        $user = User::updateOrCreate(
            ['username' => 'tch-0028'],
            [
                'staff_id' => 28,
                'username' => 'tch-0028',
                'email' => $staff->email ?? 'tch-0028@college.edu',
                'password' => '123', // Will be hashed by User model mutator
                'status' => 'active',
                'is_super_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Get roles
        $adminRole = Roles::where('name', 'admin')->first();
        $teacherRole = Roles::where('name', 'teacher')->first();
        $principalRole = Roles::where('name', 'principal')->first();

        // Assign multiple roles to demonstrate role switching
        $rolesToAssign = [];
        
        if ($adminRole) {
            $rolesToAssign[] = $adminRole->id;
        }
        
        if ($teacherRole) {
            $rolesToAssign[] = $teacherRole->id;
        }
        
        if ($principalRole) {
            $rolesToAssign[] = $principalRole->id;
        }

        if (!empty($rolesToAssign)) {
            $user->roles()->sync($rolesToAssign);
        }

        $this->command->info('Test user created successfully!');
        $this->command->info('Username: admin');
        $this->command->info('Password: 123');
        $this->command->info('Roles: ' . $user->roles->pluck('name')->implode(', '));
    }
}
