<?php

namespace Database\Factories;

use App\Models\Admissions;
use App\Models\Student;
use App\Models\BiseRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admissions>
 */
class AdmissionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currentYear = now()->year;
        $admissionYear = fake()->randomElement([$currentYear - 2, $currentYear - 1, $currentYear]);
        
        return [
            'student_id' => Student::factory(),
            'admission_year' => $admissionYear,
            'academic_year' => $admissionYear, // Assuming same for simplicity, or +2
            'current_class' => fake()->randomElement(['11', '12']),
            'college_roll_no' => fake()->unique()->numerify('####'),
            'hssc_reg_no' => fake()->unique()->numerify('HSSC-#####'),
            'bise_roll_11' => null,
            'bise_roll_12' => null,
            'status' => fake()->randomElement(['Applied', 'Admitted', 'Active', 'Graduated', 'Dropped', 'Suspended']),
        ];
    }

    /**
     * Create an active admission.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create a Class 11 admission.
     */
    public function class11(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_class' => '11',
        ]);
    }

    /**
     * Create a Class 12 admission.
     */
    public function class12(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_class' => '12',
        ]);
    }

    /**
     * Create an admission with BISE roll number for Class 11.
     */
    public function withBiseRoll11(): static
    {
        return $this->state(fn (array $attributes) => [
            'bise_roll_11' => fake()->unique()->numerify('######'),
        ]);
    }

    /**
     * Create an admission with BISE roll number for Class 12.
     */
    public function withBiseRoll12(): static
    {
        return $this->state(fn (array $attributes) => [
            'bise_roll_12' => fake()->unique()->numerify('######'),
        ]);
    }
}
