<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_required_attributes()
    {
        $subject = Subject::factory()->create([
            'name' => 'Mathematics',
            'type' => 'Compulsory',
            'class_level' => '11',
            'total_11' => 100,
        ]);

        $this->assertEquals('Mathematics', $subject->name);
        $this->assertEquals('Compulsory', $subject->type);
        $this->assertEquals('11', $subject->class_level);
        $this->assertEquals(100, $subject->total_11);
    }

    /** @test */
    public function it_can_be_compulsory_type()
    {
        $subject = Subject::factory()->compulsory()->create();

        $this->assertEquals('Compulsory', $subject->type);
    }

    /** @test */
    public function it_can_be_elective_type()
    {
        $subject = Subject::factory()->elective()->create();

        $this->assertEquals('Elective', $subject->type);
    }

    /** @test */
    public function it_can_be_for_class_11()
    {
        $subject = Subject::factory()->class11()->create();

        $this->assertEquals('11', $subject->class_level);
    }

    /** @test */
    public function it_can_be_for_class_12()
    {
        $subject = Subject::factory()->class12()->create();

        $this->assertEquals('12', $subject->class_level);
    }

    /** @test */
    public function it_has_total_marks_for_class_11()
    {
        $subject = Subject::factory()->create([
            'total_11' => 100,
        ]);

        $this->assertEquals(100, $subject->total_11);
    }

    /** @test */
    public function it_has_theory_marks_for_class_12()
    {
        $subject = Subject::factory()->create([
            'theory_12' => 75,
            'practical_12' => 25,
        ]);

        $this->assertEquals(75, $subject->theory_12);
        $this->assertEquals(25, $subject->practical_12);
    }

    /** @test */
    public function it_calculates_total_marks_for_class_12()
    {
        $subject = Subject::factory()->create([
            'theory_12' => 75,
            'practical_12' => 25,
        ]);

        $totalMarks = $subject->theory_12 + $subject->practical_12;
        $this->assertEquals(100, $totalMarks);
    }

    /** @test */
    public function it_has_unique_subject_code()
    {
        $subject = Subject::factory()->create([
            'code' => 'MATH-101',
        ]);

        $this->assertEquals('MATH-101', $subject->code);
    }

    /** @test */
    public function subject_can_be_for_both_classes()
    {
        $subject = Subject::factory()->create([
            'name' => 'Mathematics',
            'class_level' => 'both',
        ]);

        $this->assertEquals('Mathematics', $subject->name);
        $this->assertEquals('both', $subject->class_level);
    }
}
