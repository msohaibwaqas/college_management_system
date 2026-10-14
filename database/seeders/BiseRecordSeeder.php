<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BiseRecordSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Board of Intermediate and Secondary Education Bahawalpur', 'created_at' => '2025-11-19 02:55:57', 'updated_at' => '2025-11-19 02:55:57'],
            ['id' => 2, 'name' => 'Board of Intermediate and Secondary Education Dera Ghazi Khan', 'created_at' => '2025-11-19 02:53:24', 'updated_at' => '2025-11-19 02:53:24'],
            ['id' => 3, 'name' => 'Board of Intermediate and Secondary Education Faisalabad', 'created_at' => '2025-11-19 02:54:07', 'updated_at' => '2025-11-19 02:54:07'],
            ['id' => 4, 'name' => 'Board of Intermediate and Secondary Education Gujranwala', 'created_at' => '2025-11-19 02:55:00', 'updated_at' => '2025-11-19 02:55:00'],
            ['id' => 5, 'name' => 'Board of Intermediate and Secondary Education Lahore', 'created_at' => '2025-11-19 02:55:28', 'updated_at' => '2025-11-19 02:55:28'],
            ['id' => 6, 'name' => 'Board of Intermediate and Secondary Education Multan', 'created_at' => '2025-11-19 02:56:48', 'updated_at' => '2025-11-19 02:56:48'],
            ['id' => 7, 'name' => 'Board of Intermediate and Secondary Education Rawalpindi', 'created_at' => '2025-11-19 02:57:12', 'updated_at' => '2025-11-19 02:57:12'],
            ['id' => 8, 'name' => 'Board of Intermediate and Secondary Education Sahiwal', 'created_at' => '2025-11-19 02:58:09', 'updated_at' => '2025-11-19 02:58:09'],
            ['id' => 9, 'name' => 'Board of Intermediate and Secondary Education Sargodha', 'created_at' => '2025-11-19 02:58:31', 'updated_at' => '2025-11-19 02:58:31'],
            ['id' => 11, 'name' => 'Federal Board of Intermediate and Secondary Education', 'created_at' => '2025-11-19 02:59:18', 'updated_at' => '2025-11-19 02:59:18'],
            ['id' => 12, 'name' => 'Board of Intermediate and Secondary Education Azad Jammu and Kashmir', 'created_at' => '2025-11-19 03:00:08', 'updated_at' => '2025-11-19 03:00:08'],
            ['id' => 13, 'name' => 'Balochistan Board of Intermediate and Secondary Education Quetta', 'created_at' => '2025-11-19 03:00:57', 'updated_at' => '2025-11-19 03:00:57'],
        ];

        DB::table('bise_records')->insert($data);
    }
}
