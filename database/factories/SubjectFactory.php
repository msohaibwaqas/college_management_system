<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            ['name' => 'Mathematics', 'type' => 'elective', 'marks' => 100],
            ['name' => 'Physics', 'type' => 'elective', 'marks' => 100],
            ['name' => 'Chemistry', 'type' => 'elective', 'marks' => 100],
            ['name' => 'Biology', 'type' => 'elective', 'marks' => 100],
            ['name' => 'Computer Science', 'type' => 'elective', 'marks' => 100],
            ['name' => 'English', 'type' => 'compulsory', 'marks' => 100],
            ['name' => 'Urdu', 'type' => 'compulsory', 'marks' => 100],
            ['name' => 'Islamic Studies', 'type' => 'compulsory', 'marks' => 50],
            ['name' => 'Pakistan Studies', 'type' => 'compulsory', 'marks' => 50],
        ];
        
        $subject = fake()->randomElement($subjects);
        
        return [
            'name' => $subject['name'],
            'code' => fake()->unique()->bothify('SUB-###'),
            'type' => ucfirst($subject['type']), // Capitalize for enum
            'class_level' => fake()->randomElement(['11', '12', 'both']),
            'total_11' => $subject['marks'],
            'theory_12' => 0,
            'practical_12' => 0,
        ];
    }

    /**
     * Create a compulsory subject.
     */
    public function compulsory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Compulsory',
        ]);
    }

    /**
     * Create an elective subject.
     */
    public function elective(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Elective',
        ]);
    }

    /**
     * Create a subject for Class 11.
     */
    public function class11(): static
    {
        return $this->state(fn (array $attributes) => [
            'class_level' => '11',
        ]);
    }

    /**
     * Create a subject for Class 12.
     */
    public function class12(): static
    {
        return $this->state(fn (array $attributes) => [
            'class_level' => '12',
        ]);
    }
}
