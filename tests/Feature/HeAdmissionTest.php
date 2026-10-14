<?php

namespace Tests\Feature;

use App\Models\BsSubject;
use App\Models\HeAdmission;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeAdmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_subject_assignment_on_enrolment()
    {
        // 1. Create a user (for audit fields)
        $user = User::factory()->create();
        $this->actingAs($user);

        // 2. Create a program
        $program = Program::create([
            'name' => 'BS English',
            'type' => 'BS',
            'total_semesters' => 8,
            'created_by' => $user->id,
        ]);

        // 3. Create some BS subjects
        $subjectA = BsSubject::create([
            'name' => 'Introduction to Linguistics',
            'code' => 'ENG-101',
            'credit_hours' => 3,
            'created_by' => $user->id,
        ]);

        $subjectB = BsSubject::create([
            'name' => 'English Grammar',
            'code' => 'ENG-102',
            'credit_hours' => 3,
            'created_by' => $user->id,
        ]);

        // 4. Link subjects to Program Semester 1
        Semester::create([
            'program_id' => $program->id,
            'bs_subject_id' => $subjectA->id,
            'semester_no' => 1,
            'created_by' => $user->id,
        ]);

        Semester::create([
            'program_id' => $program->id,
            'bs_subject_id' => $subjectB->id,
            'semester_no' => 1,
            'created_by' => $user->id,
        ]);

        // 5. Create a student
        $student = Student::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2005-01-01',
            'gender' => 'Male',
            'cnic' => '1234567890123',
            'created_by' => $user->id,
        ]);

        // 6. Enrol student in Semester 1
        $admission = HeAdmission::create([
            'student_id' => $student->id,
            'program_id' => $program->id,
            'roll_no' => 'BS-ENG-001',
            'admission_year' => 2026,
            'current_semester' => 1,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        // 7. Verify subjects are automatically assigned
        $assignedSubjects = $admission->subjects;
        
        $this->assertCount(2, $assignedSubjects);
        $this->assertTrue($assignedSubjects->contains('bs_subject_id', $subjectA->id));
        $this->assertTrue($assignedSubjects->contains('bs_subject_id', $subjectB->id));
        $this->assertEquals(1, $assignedSubjects->first()->semester_no);
    }
}
