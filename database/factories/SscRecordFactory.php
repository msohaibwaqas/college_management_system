<?php

namespace Database\Factories;

use App\Models\SscRecord;
use App\Models\Student;
use App\Models\BiseRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SscRecord>
 */
class SscRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalMarks = 1100; // Typical SSC total marks
        $obtainedMarks = fake()->numberBetween(600, 1050);
        $percentage = round(($obtainedMarks / $totalMarks) * 100, 2);
        
        return [
            'student_id' => Student::factory(),
            'bise_id' => BiseRecord::factory(),
            'roll_no' => fake()->unique()->numerify('######'),
            'reg_no' => fake()->unique()->numerify('###-###-###'),
            'school_name' => fake()->company() . ' High School',
            'passing_year' => fake()->year('-2 years'),
            'passing_session' => fake()->randomElement(['1st Annual', '2nd Annual']),
            'ssc_group' => fake()->randomElement(['Science', 'Arts']),
            'total_mark' => $totalMarks,
            'obtained_mark' => $obtainedMarks,
        ];
    }

    /**
     * Calculate grade based on percentage.
     */
    protected function calculateGrade(float $percentage): string
    {
        if ($percentage >= 80) return 'A+';
        if ($percentage >= 70) return 'A';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 40) return 'D';
        return 'E';
    }

    /**
     * Create a high-achieving SSC record.
     */
    public function distinction(): static
    {
        return $this->state(function (array $attributes) {
            $totalMarks = 1100;
            $obtainedMarks = fake()->numberBetween(900, 1050);
            $percentage = round(($obtainedMarks / $totalMarks) * 100, 2);
            
            return [
                'obtained_mark' => $obtainedMarks,
            ];
        });
    }
}
