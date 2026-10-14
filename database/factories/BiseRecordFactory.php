<?php

namespace Database\Factories;

use App\Models\BiseRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BiseRecord>
 */
class BiseRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $biseBoards = [
            'Lahore',
            'Gujranwala',
            'Rawalpindi',
            'Multan',
            'Faisalabad',
            'Sargodha',
            'Bahawalpur',
            'Sahiwal',
            'DG Khan'
        ];
        
        return [
            'name' => fake()->unique()->randomElement($biseBoards) . ' Board',
        ];
    }
}
