<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['Male', 'Female']);
        
        return [
            'first_name' => fake()->firstName($gender === 'Male' ? 'male' : 'female'),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date('Y-m-d', '-16 years'), // Students are typically 16+
            'gender' => $gender,
            'cnic' => $this->generateCnic(),
            'picture' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    /**
     * Generate a realistic Pakistani CNIC number.
     */
    protected function generateCnic(): string
    {
        // Format: XXXXX-XXXXXXX-X (13 digits)
        return sprintf(
            '%05d-%07d-%01d',
            fake()->numberBetween(10000, 99999),
            fake()->numberBetween(1000000, 9999999),
            fake()->numberBetween(1, 9)
        );
    }

    /**
     * Create a male student.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'Male',
            'first_name' => fake()->firstName('male'),
        ]);
    }

    /**
     * Create a female student.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'Female',
            'first_name' => fake()->firstName('female'),
        ]);
    }

    /**
     * Create a student with a picture.
     */
    public function withPicture(): static
    {
        return $this->state(fn (array $attributes) => [
            'picture' => 'students/default.jpg',
        ]);
    }
}
