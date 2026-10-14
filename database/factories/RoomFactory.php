<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_number' => fake()->unique()->bothify('Room-##'),
            'capacity' => fake()->randomElement([30, 40, 50, 60]),
            'building' => fake()->randomElement(['Main Building', 'Science Block', 'Arts Block', 'Admin Block']),
            'floor' => fake()->randomElement(['Ground Floor', 'First Floor', 'Second Floor']),
            'room_type' => fake()->randomElement(['Classroom', 'Lab', 'Exam Hall']),
        ];
    }

    /**
     * Create a classroom.
     */
    public function classroom(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_type' => 'Classroom',
            'capacity' => fake()->randomElement([40, 50, 60]),
        ]);
    }

    /**
     * Create an exam hall.
     */
    public function examHall(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_type' => 'Exam Hall',
            'capacity' => fake()->randomElement([50, 60, 80, 100]),
        ]);
    }
}
