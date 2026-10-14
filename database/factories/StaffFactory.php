<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);
        
        return [
            'staff_id' => 'STAFF-' . fake()->unique()->numerify('#####'),
            'category' => fake()->randomElement(['teaching', 'non_teaching']),
            'bps' => fake()->numberBetween(1, 22),
            'first_name' => fake()->firstName($gender === 'Male' ? 'male' : 'female'),
            'last_name' => fake()->lastName(),
            'father_name' => fake()->name('male'),
            'cnic' => $this->generateCnic(),
            'date_of_birth' => fake()->date('Y-m-d', '-30 years'),
            'gender' => $gender,
            'marital_status' => fake()->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'religion' => fake()->randomElement(['Islam', 'Christianity', 'Hinduism', 'Other']),
            'nationality' => 'Pakistani',
            'contact_number' => $this->generatePakistaniMobile(),
            'email' => fake()->unique()->safeEmail(),
            'permanent_address' => fake()->address(),
            'current_address' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_number' => $this->generatePakistaniMobile(),
            'current_college_joining_date' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'designation' => fake()->randomElement(['Lecturer', 'Assistant Professor', 'Associate Professor', 'Professor', 'Lab Assistant']),
            'department' => fake()->randomElement(['Science', 'Arts', 'Commerce', 'Computer Science']),
            'employment_type' => fake()->randomElement(['Permanent', 'Contract', 'Visiting']),
            'salary' => fake()->numberBetween(30000, 150000),
            'status' => 'active',
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

    /**
     * Create a teacher staff member.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'designation' => fake()->randomElement(['Lecturer', 'Assistant Professor', 'Associate Professor', 'Professor']),
        ]);
    }

    /**
     * Create a permanent staff member.
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'Permanent',
        ]);
    }

    /**
     * Create an active staff member.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
