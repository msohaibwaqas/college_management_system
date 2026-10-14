<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuotaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'name' => 'disable', 'created_at' => '2025-11-19 03:18:12', 'updated_at' => '2025-11-19 03:18:12'],
            ['id' => 2, 'name' => 'blind', 'created_at' => '2025-11-19 03:18:35', 'updated_at' => '2025-11-19 03:18:35'],
            ['id' => 3, 'name' => 'minority', 'created_at' => '2025-11-19 03:18:41', 'updated_at' => '2025-11-19 03:18:41'],
            ['id' => 4, 'name' => 'sports', 'created_at' => '2025-11-19 03:19:06', 'updated_at' => '2025-11-19 03:19:06'],
            ['id' => 5, 'name' => 'hafiz e quran', 'created_at' => '2025-11-19 03:19:23', 'updated_at' => '2025-11-19 03:19:23'],
        ];

        DB::table('quotas')->insert($data);
    }
}
