<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\Result;
use App\Models\StudentElectiveSubject;
use App\Models\StudentCompulsorySubject;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_required_fillable_attributes()
    {
        $student = Student::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2005-01-15',
            'gender' => 'Male',
        ]);

        $this->assertEquals('John', $student->first_name);
        $this->assertEquals('Doe', $student->last_name);
        $this->assertEquals('2005-01-15', $student->date_of_birth->format('Y-m-d'));
        $this->assertEquals('Male', $student->gender);
    }

    /** @test */
    public function it_has_full_name_accessor()
    {
        $student = Student::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $student->full_name);
    }

    /** @test */
    public function it_calculates_age_correctly()
    {
        $student = Student::factory()->create([
            'date_of_birth' => now()->subYears(18)->format('Y-m-d'),
        ]);

        $this->assertEquals(18, $student->age);
    }

    /** @test */
    public function it_has_attribute_relationship()
    {
        $student = $this->createCompleteStudent();

        $this->assertNotNull($student->attribute);
        $this->assertInstanceOf(\App\Models\StudentAttribute::class, $student->attribute);
    }

    /** @test */
    public function it_has_contact_relationship()
    {
        $student = $this->createCompleteStudent();

        $this->assertNotNull($student->contact);
        $this->assertInstanceOf(\App\Models\Contact::class, $student->contact);
    }

    /** @test */
    public function it_has_father_relationship()
    {
        $student = $this->createCompleteStudent();

        $this->assertNotNull($student->father);
        $this->assertInstanceOf(\App\Models\Father::class, $student->father);
    }

    /** @test */
    public function it_has_ssc_record_relationship()
    {
        $student = $this->createCompleteStudent();

        $this->assertNotNull($student->sscRecord);
        $this->assertInstanceOf(\App\Models\SscRecord::class, $student->sscRecord);
    }

    /** @test */
    public function it_has_admissions_relationship()
    {
        $student = $this->createStudentWithAdmission();

        $this->assertCount(1, $student->admissions);
        $this->assertInstanceOf(Admissions::class, $student->admissions->first());
    }

    /** @test */
    public function it_has_results_relationship()
    {
        $student = $this->createStudentWithAdmission();
        $subject = $this->createSubject();
        
        Result::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);

        $this->assertCount(1, $student->results);
        $this->assertInstanceOf(Result::class, $student->results->first());
    }

    /** @test */
    public function search_by_name_scope_works()
    {
        Student::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Student::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $results = Student::searchByName('John')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->first_name);
    }

    /** @test */
    public function for_class_scope_filters_by_class_level()
    {
        $student11 = $this->createStudentWithAdmission('11');
        $student12 = $this->createStudentWithAdmission('12');

        $results = Student::forClass('11')->get();

        $this->assertTrue($results->contains($student11));
        $this->assertFalse($results->contains($student12));
    }

    /** @test */
    public function for_year_scope_filters_by_admission_year()
    {
        $student2023 = $this->createStudentWithAdmission('11', 2023);
        $student2024 = $this->createStudentWithAdmission('11', 2024);

        $results = Student::forYear(2023)->get();

        $this->assertTrue($results->contains($student2023));
        $this->assertFalse($results->contains($student2024));
    }

    /** @test */
    public function active_scope_returns_only_active_students()
    {
        $activeStudent = $this->createStudentWithAdmission();
        $inactiveStudent = $this->createCompleteStudent();
        
        Admissions::factory()->create([
            'student_id' => $inactiveStudent->id,
            'status' => 'Dropped',
        ]);

        $results = Student::active()->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->contains($activeStudent));
    }

    /** @test */
    public function it_capitalizes_first_name_on_save()
    {
        $student = Student::factory()->create([
            'first_name' => 'john',
        ]);

        $this->assertEquals('John', $student->first_name);
    }

    /** @test */
    public function it_capitalizes_last_name_on_save()
    {
        $student = Student::factory()->create([
            'last_name' => 'doe',
        ]);

        $this->assertEquals('Doe', $student->last_name);
    }

    /** @test */
    public function it_determines_if_student_is_minor()
    {
        $minor = Student::factory()->create([
            'date_of_birth' => now()->subYears(16)->format('Y-m-d'),
        ]);

        $adult = Student::factory()->create([
            'date_of_birth' => now()->subYears(19)->format('Y-m-d'),
        ]);

        $this->assertTrue($minor->is_minor);
        $this->assertFalse($adult->is_minor);
    }

    /** @test */
    public function it_determines_if_student_is_adult()
    {
        $minor = Student::factory()->create([
            'date_of_birth' => now()->subYears(16)->format('Y-m-d'),
        ]);

        $adult = Student::factory()->create([
            'date_of_birth' => now()->subYears(19)->format('Y-m-d'),
        ]);

        $this->assertFalse($minor->is_adult);
        $this->assertTrue($adult->is_adult);
    }

    /** @test */
    public function it_gets_current_class_from_latest_admission()
    {
        $student = $this->createStudentWithAdmission('12');

        $this->assertEquals('12', $student->current_class);
    }

    /** @test */
    public function get_full_details_returns_complete_student_info()
    {
        $student = $this->createCompleteStudent();

        $details = $student->getFullDetails();

        $this->assertIsArray($details);
        $this->assertArrayHasKey('first_name', $details);
        $this->assertArrayHasKey('last_name', $details);
        $this->assertArrayHasKey('date_of_birth', $details);
        $this->assertArrayHasKey('age', $details);
    }

    /** @test */
    public function soft_delete_works_correctly()
    {
        $student = Student::factory()->create();
        $studentId = $student->id;

        $student->delete();

        $this->assertSoftDeleted('students', ['id' => $studentId]);
        $this->assertNotNull($student->fresh()->deleted_at);
    }
}
