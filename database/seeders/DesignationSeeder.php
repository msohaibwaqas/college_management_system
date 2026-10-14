<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $designations = [
            ['name' => 'Principal', 'bps' => 20],
            ['name' => 'Vice Principal', 'bps' => 19],
            ['name' => 'Professor', 'bps' => 19],
            ['name' => 'Associate Professor', 'bps' => 18],
            ['name' => 'Assistant Professor', 'bps' => 17],
            ['name' => 'Lecturer', 'bps' => 17],
            ['name' => 'Senior Lecturer', 'bps' => 18],
            ['name' => 'Subject Specialist', 'bps' => 17],
            ['name' => 'Physical Education Teacher', 'bps' => 16],
            ['name' => 'Librarian', 'bps' => 16],
            ['name' => 'Lab Attendant', 'bps' => 11],
            ['name' => 'Clerk', 'bps' => 11],
            ['name' => 'Senior Clerk', 'bps' => 14],
            ['name' => 'Head Clerk', 'bps' => 16],
            ['name' => 'Accountant', 'bps' => 16],
            ['name' => 'Naib Qasid', 'bps' => 1],
            ['name' => 'Chowkidar', 'bps' => 1],
            ['name' => 'Mali', 'bps' => 1],
            ['name' => 'Sweeper', 'bps' => 1],
        ];

        foreach ($designations as $designation) {
            \App\Models\Designation::create($designation);
        }

        $this->command->info('Designations seeded successfully!');
    }
}
