<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [
            ['slot_number' => 1, 'name' => 'Lecture 1', 'start_time' => '09:00:00', 'end_time' => '09:40:00'],
            ['slot_number' => 2, 'name' => 'Lecture 2', 'start_time' => '09:40:00', 'end_time' => '10:20:00'],
            ['slot_number' => 3, 'name' => 'Lecture 3', 'start_time' => '10:20:00', 'end_time' => '11:00:00'],
            ['slot_number' => 4, 'name' => 'Lecture 4', 'start_time' => '11:00:00', 'end_time' => '11:40:00'],
            ['slot_number' => 5, 'name' => 'Lecture 5', 'start_time' => '11:40:00', 'end_time' => '12:20:00'],
            ['slot_number' => 6, 'name' => 'Lecture 6', 'start_time' => '12:20:00', 'end_time' => '13:00:00'],
            ['slot_number' => 7, 'name' => 'Lecture 7', 'start_time' => '13:00:00', 'end_time' => '13:40:00'],
        ];

        foreach ($slots as $slot) {
            \App\Models\TimeSlot::updateOrCreate(
                ['slot_number' => $slot['slot_number']],
                $slot
            );
        }
    }
}
