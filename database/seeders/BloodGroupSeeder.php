<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BloodGroupSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'code' => 'A+ve', 'created_at' => '2025-11-19 03:10:14', 'updated_at' => '2025-11-19 03:10:14'],
            ['id' => 2, 'code' => 'A-ve', 'created_at' => '2025-11-19 03:10:26', 'updated_at' => '2025-11-19 03:10:26'],
            ['id' => 3, 'code' => 'B+ve', 'created_at' => '2025-11-19 03:10:40', 'updated_at' => '2025-11-19 03:10:40'],
            ['id' => 4, 'code' => 'B-ve', 'created_at' => '2025-11-19 03:10:45', 'updated_at' => '2025-11-19 03:10:45'],
            ['id' => 6, 'code' => 'O-ve', 'created_at' => '2025-11-19 03:10:55', 'updated_at' => '2025-11-19 03:10:55'],
            ['id' => 7, 'code' => 'AB+ve', 'created_at' => '2025-11-19 03:11:02', 'updated_at' => '2025-11-19 03:11:02'],
            ['id' => 8, 'code' => 'AB-ve', 'created_at' => '2025-11-19 03:11:10', 'updated_at' => '2025-11-19 03:11:10'],
            ['id' => 9, 'code' => 'O+ve', 'created_at' => '2025-11-19 03:12:00', 'updated_at' => '2025-11-19 03:12:00'],
        ];

        DB::table('blood_groups')->insert($data);
    }
}
