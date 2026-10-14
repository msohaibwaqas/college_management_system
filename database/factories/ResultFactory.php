<?php

namespace Database\Factories;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Admissions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Result>
 */
class ResultFactory extends Factory
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
            'subject_id' => Subject::factory(),
            'class_level' => fake()->randomElement(['11', '12']),
            'exam_type' => fake()->randomElement(['Oct_test', 'Dec_test', 'Send_up', 'Final']),
            'exam_name' => fake()->word(),
            'total_marks' => 100,
            'academic_year' => fake()->year(),
            'obtained_theory' => fake()->numberBetween(0, 75),
            'obtained_practical' => fake()->numberBetween(0, 25),
        ];
    }

    /**
     * Create a class test result.
     */
    public function classTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'exam_type' => 'class_test',
            'exam_name' => 'Class Test ' . fake()->numberBetween(1, 5),
            'total_marks' => 20,
            'obtained_theory' => fake()->numberBetween(10, 20),
            'obtained_practical' => 0,
        ]);
    }

    /**
     * Create an October test result.
     */
    public function octoberTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'exam_type' => 'october_test',
            'exam_name' => 'October Test',
            'total_marks' => 100,
            'obtained_theory' => fake()->numberBetween(40, 75),
            'obtained_practical' => fake()->numberBetween(0, 25),
        ]);
    }

    /**
     * Create a final exam result.
     */
    public function finalExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'exam_type' => 'final',
            'exam_name' => 'Final Examination',
            'total_marks' => 100,
            'obtained_theory' => fake()->numberBetween(33, 75),
            'obtained_practical' => fake()->numberBetween(0, 25),
        ]);
    }

    /**
     * Create an absent result.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'obtained_theory' => 0,
            'obtained_practical' => 0,
            'is_absent' => true,
        ]);
    }

    /**
     * Create a passing result.
     */
    public function passing(): static
    {
        return $this->state(function (array $attributes) {
            $totalMarks = $attributes['total_marks'] ?? 100;
            $passingMarks = ceil($totalMarks * 0.33);
            
            return [
                'obtained_theory' => fake()->numberBetween($passingMarks, $totalMarks),
                'obtained_practical' => 0,
            ];
        });
    }

    /**
     * Create a failing result.
     */
    public function failing(): static
    {
        return $this->state(function (array $attributes) {
            $totalMarks = $attributes['total_marks'] ?? 100;
            $passingMarks = ceil($totalMarks * 0.33);
            
            return [
                'obtained_theory' => fake()->numberBetween(0, $passingMarks - 1),
                'obtained_practical' => 0,
            ];
        });
    }
}
