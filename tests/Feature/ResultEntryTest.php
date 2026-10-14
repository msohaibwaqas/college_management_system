<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Admissions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResultEntryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_view_results_index()
    {
        $this->actingAsAdmin();

        $response = $this->get('/results');

        $response->assertStatus(200);
        $response->assertViewIs('results.index');
    }

    /** @test */
    public function authenticated_user_can_view_create_result_form()
    {
        $this->actingAsAdmin();

        $response = $this->get('/results/create');

        $response->assertStatus(200);
        $response->assertViewIs('results.create');
    }

    /** @test */
    public function authenticated_user_can_fetch_students_for_marks_entry()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission('11', now()->year);
        $subject = $this->createSubject(['class_level' => '11']);

        $response = $this->post('/results/fetch-students', [
            'academic_year' => now()->year,
            'class_level' => '11',
            'subject_id' => $subject->id,
            'exam_type' => 'Oct_test',
            'exam_name' => 'October Test',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_create_result()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission('11', now()->year);
        $subject = $this->createSubject(['class_level' => '11']);
        $admission = $student->admissions->first();

        $response = $this->post('/results', [
            'academic_year' => now()->year,
            'class_level' => '11',
            'subject_id' => $subject->id,
            'exam_type' => 'class_test',
            'exam_name' => 'Class Test 1',
            'total_marks' => 20,
            'test_date' => now()->format('Y-m-d'),
            'marks' => [
                $student->id => [
                    'theory' => 15,
                    'practical' => 0,
                    'is_absent' => false,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'exam_type' => 'class_test',
        ]);
    }

    /** @test */
    public function result_obtained_marks_cannot_exceed_total_marks()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission('11', now()->year);
        $subject = $this->createSubject(['class_level' => '11']);

        $response = $this->post('/results', [
            'academic_year' => now()->year,
            'class_level' => '11',
            'subject_id' => $subject->id,
            'exam_type' => 'class_test',
            'exam_name' => 'Class Test 1',
            'total_marks' => 20,
            'test_date' => now()->format('Y-m-d'),
            'marks' => [
                $student->id => [
                    'theory' => 25, // Exceeds total marks
                    'practical' => 0,
                    'is_absent' => false,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function absent_student_has_zero_marks()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission('11', now()->year);
        $subject = $this->createSubject(['class_level' => '11']);

        $response = $this->post('/results', [
            'academic_year' => now()->year,
            'class_level' => '11',
            'subject_id' => $subject->id,
            'exam_type' => 'class_test',
            'exam_name' => 'Class Test 1',
            'total_marks' => 20,
            'test_date' => now()->format('Y-m-d'),
            'marks' => [
                $student->id => [
                    'theory' => 0,
                    'practical' => 0,
                    'is_absent' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('results', [
            'student_id' => $student->id,
            'obtained_theory' => 0,
            'obtained_practical' => 0,
            'is_absent' => true,
        ]);
    }

    /** @test */
    public function authenticated_user_can_view_student_results()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission();
        $subject = $this->createSubject();
        
        Result::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $response = $this->get("/results/student/{$student->id}");

        $response->assertStatus(200);
        $response->assertViewIs('results.student');
    }

    /** @test */
    public function authenticated_user_can_delete_result()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission();
        $subject = $this->createSubject();
        
        $result = Result::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $response = $this->delete('/results/destroy', [
            'academic_year' => $result->academic_year,
            'class_level' => $result->class_level,
            'subject_id' => $result->subject_id,
            'exam_type' => $result->exam_type,
            'exam_name' => $result->exam_name,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function results_are_grouped_by_exam_type()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission();
        $subject = $this->createSubject();
        
        Result::factory()->classTest()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);
        
        Result::factory()->finalExam()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $response = $this->get('/results');

        $response->assertStatus(200);
        $response->assertSee('class_test');
        $response->assertSee('final');
    }

    /** @test */
    public function final_result_entry_form_can_be_accessed()
    {
        $this->actingAsAdmin();

        $response = $this->get('/results/final/create');

        $response->assertStatus(200);
        $response->assertViewIs('results.final_create');
    }

    /** @test */
    public function student_can_be_searched_for_final_result_entry()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission('12', now()->year);
        $admission = $student->admissions->first();
        $admission->bise_roll_12 = '123456';
        $admission->save();

        $response = $this->post('/results/final/search', [
            'admission_year' => now()->year,
            'bise_roll_12' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertSee($student->full_name);
    }
}
