<?php

namespace Database\Factories;

use App\Models\Quota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quota>
 */
class QuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quotaTypes = ['Open Merit', 'Minority', 'Sports', 'Disabled', 'Overseas Pakistani'];
        
        return [
            'name' => fake()->unique()->randomElement($quotaTypes),
        ];
    }
}
