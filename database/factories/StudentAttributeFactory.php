<?php

namespace Database\Factories;

use App\Models\StudentAttribute;
use App\Models\Student;
use App\Models\BloodGroup;
use App\Models\Quota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentAttribute>
 */
class StudentAttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'blood_group_id' => BloodGroup::factory(),
            'quota_id' => Quota::factory(),
            'religion' => fake()->randomElement(['Islam', 'Christianity', 'Hinduism', 'Other']),
            'nationality' => 'Pakistani',
            'domicile' => fake()->randomElement(['Punjab', 'Sindh', 'KPK', 'Balochistan', 'Islamabad']),
        ];
    }
}
