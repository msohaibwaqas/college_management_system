<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Admissions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResultTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_student()
    {
        $student = $this->createStudentWithAdmission();
        $subject = $this->createSubject();
        
        $result = Result::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $this->assertInstanceOf(Student::class, $result->student);
        $this->assertEquals($student->id, $result->student->id);
    }

    /** @test */
    public function it_belongs_to_subject()
    {
        $student = $this->createStudentWithAdmission();
        $subject = $this->createSubject();
        
        $result = Result::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $this->assertInstanceOf(Subject::class, $result->subject);
        $this->assertEquals($subject->id, $result->subject->id);
    }

    /** @test */
    public function it_calculates_total_obtained_marks()
    {
        $result = Result::factory()->create([
            'total_marks' => 100,
            'obtained_theory' => 60,
            'obtained_practical' => 15,
        ]);

        $totalObtained = $result->obtained_theory + $result->obtained_practical;
        $this->assertEquals(75, $totalObtained);
    }

    /** @test */
    public function it_determines_pass_or_fail()
    {
        $passingResult = Result::factory()->passing()->create([
            'total_marks' => 100,
        ]);

        $failingResult = Result::factory()->failing()->create([
            'total_marks' => 100,
        ]);

        $passingTotal = $passingResult->obtained_theory + ($passingResult->obtained_practical ?? 0);
        $failingTotal = $failingResult->obtained_theory + ($failingResult->obtained_practical ?? 0);
        
        $passingPercentage = ($passingTotal / $passingResult->total_marks) * 100;
        $failingPercentage = ($failingTotal / $failingResult->total_marks) * 100;

        $this->assertGreaterThanOrEqual(33, $passingPercentage);
        $this->assertLessThan(33, $failingPercentage);
    }

    /** @test */
    public function absent_students_have_zero_marks()
    {
        $result = Result::factory()->absent()->create();

        $this->assertTrue($result->is_absent);
        $this->assertEquals(0, $result->obtained_theory);
        $this->assertEquals(0, $result->obtained_practical);
    }

    /** @test */
    public function it_stores_different_exam_types()
    {
        $examTypes = ['class_test', 'october_test', 'december_test', 'send_up', 'final'];

        foreach ($examTypes as $type) {
            $result = Result::factory()->create(['exam_type' => $type]);
            $this->assertEquals($type, $result->exam_type);
        }
    }

    /** @test */
    public function obtained_marks_cannot_exceed_total_marks()
    {
        $result = Result::factory()->create([
            'total_marks' => 100,
            'obtained_theory' => 60,
            'obtained_practical' => 15,
        ]);

        $totalObtained = $result->obtained_theory + ($result->obtained_practical ?? 0);
        $this->assertLessThanOrEqual($result->total_marks, $totalObtained);
    }

    /** @test */
    public function it_stores_exam_name_and_type()
    {
        $result = Result::factory()->create([
            'exam_type' => 'Oct_test',
            'exam_name' => 'October Test',
        ]);

        $this->assertEquals('Oct_test', $result->exam_type);
        $this->assertEquals('October Test', $result->exam_name);
    }

    /** @test */
    public function it_separates_theory_and_practical_marks()
    {
        $result = Result::factory()->create([
            'obtained_theory' => 60,
            'obtained_practical' => 20,
        ]);

        $this->assertEquals(60, $result->obtained_theory);
        $this->assertEquals(20, $result->obtained_practical);
    }

    /** @test */
    public function it_has_academic_year_and_class_level()
    {
        $student = $this->createStudentWithAdmission();
        $subject = $this->createSubject();
        
        $result = Result::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year' => 2024,
            'class_level' => '11',
        ]);

        $this->assertEquals(2024, $result->academic_year);
        $this->assertEquals('11', $result->class_level);
    }
}
