<?php

namespace Database\Factories;

use App\Models\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles>
 */
class RolesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_name' => fake()->unique()->randomElement(['Admin', 'Teacher', 'Staff', 'Principal', 'Vice Principal']),
            'role_description' => fake()->sentence(),
        ];
    }

    /**
     * Create an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_name' => 'Admin',
            'role_description' => 'System Administrator with full access',
        ]);
    }

    /**
     * Create a teacher role.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_name' => 'Teacher',
            'role_description' => 'Teaching staff with limited access',
        ]);
    }
}
