<?php

namespace Tests;

use App\Models\User;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Roles;
use App\Models\Subject;
use App\Models\Admissions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed essential lookup data
        $this->seedEssentialData();
    }

    /**
     * Seed essential lookup data needed for tests.
     */
    protected function seedEssentialData(): void
    {
        // Create roles if they don't exist
        if (Roles::count() === 0) {
            Roles::create(['name' => 'Admin', 'description' => 'Administrator']);
            Roles::create(['name' => 'Teacher', 'description' => 'Teacher']);
            Roles::create(['name' => 'Staff', 'description' => 'Staff']);
        }
    }

    /**
     * Create and authenticate a user with admin role.
     */
    protected function actingAsAdmin(): User
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        return $user;
    }

    /**
     * Create and authenticate a user with teacher role.
     */
    protected function actingAsTeacher(): User
    {
        $user = User::factory()->teacher()->create();
        $this->actingAs($user);
        return $user;
    }

    /**
     * Create and authenticate a user with staff role.
     */
    protected function actingAsStaff(): User
    {
        $user = User::factory()->staff()->create();
        $this->actingAs($user);
        return $user;
    }

    /**
     * Create a complete student with all related data.
     */
    protected function createCompleteStudent(array $overrides = []): Student
    {
        $student = Student::factory()->create($overrides);
        
        // Create related records
        \App\Models\StudentAttribute::factory()->create(['student_id' => $student->id]);
        \App\Models\Contact::factory()->create(['student_id' => $student->id]);
        \App\Models\Father::factory()->create(['student_id' => $student->id]);
        \App\Models\SscRecord::factory()->create(['student_id' => $student->id]);
        
        // Create 3 elective subjects
        $subjects = \App\Models\Subject::factory()->count(3)->create();
        foreach ($subjects as $index => $subject) {
            \App\Models\StudentElectiveSubject::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'sequence' => $index + 1,
            ]);
        }
        
        // Create compulsory subjects
        $compulsorySubjects = \App\Models\Subject::factory()->count(3)->create();
        foreach ($compulsorySubjects as $subject) {
            \App\Models\StudentCompulsorySubject::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'class_level' => '11',
            ]);
        }
        
        return $student->fresh();
    }

    /**
     * Create a student with admission.
     */
    protected function createStudentWithAdmission(string $classLevel = '11', int $year = null): Student
    {
        $student = $this->createCompleteStudent();
        
        Admissions::factory()->create([
            'student_id' => $student->id,
            'current_class' => $classLevel,
            'admission_year' => $year ?? now()->year,
            'academic_year' => $year ?? now()->year,
            'status' => 'Active',
        ]);
        
        return $student->fresh();
    }

    /**
     * Create a subject for testing.
     */
    protected function createSubject(array $overrides = []): Subject
    {
        return Subject::factory()->create($overrides);
    }

    /**
     * Create a staff member for testing.
     */
    protected function createStaff(array $overrides = []): Staff
    {
        return Staff::factory()->create($overrides);
    }

    /**
     * Assert that the response contains validation errors for specific fields.
     */
    protected function assertValidationErrors(array $fields): void
    {
        $this->assertSessionHasErrors($fields);
    }
}
