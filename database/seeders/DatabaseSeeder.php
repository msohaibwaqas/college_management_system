<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BiseRecordSeeder::class,
            BloodGroupSeeder::class,
            QuotaSeeder::class,
            SubjectSeeder::class,
            RolesSeeder::class,
            DesignationSeeder::class,
            StaffSeeder::class,
        ]);
    }
}
