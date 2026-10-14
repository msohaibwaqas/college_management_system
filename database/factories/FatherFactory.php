<?php

namespace Database\Factories;

use App\Models\Father;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Father>
 */
class FatherFactory extends Factory
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
            'name' => fake()->name('male'),
            'cnic' => $this->generateCnic(),
            'occupation' => fake()->randomElement([
                'Government Employee',
                'Private Employee',
                'Business',
                'Doctor',
                'Engineer',
                'Teacher',
                'Farmer',
                'Other'
            ]),
            'annual_income' => fake()->numberBetween(20000, 500000),
            'contact' => $this->generatePakistaniMobile(),
        ];
    }

    /**
     * Generate a realistic Pakistani CNIC number.
     */
    protected function generateCnic(): string
    {
        return sprintf(
            '%05d-%07d-%01d',
            fake()->numberBetween(10000, 99999),
            fake()->numberBetween(1000000, 9999999),
            fake()->numberBetween(1, 9)
        );
    }

    /**
     * Generate a Pakistani mobile number.
     */
    protected function generatePakistaniMobile(): string
    {
        $prefixes = ['0300', '0301', '0321', '0322', '0323', '0333'];
        return fake()->randomElement($prefixes) . '-' . fake()->numerify('#######');
    }
}
