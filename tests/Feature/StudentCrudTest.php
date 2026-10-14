<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\BiseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_view_students_list()
    {
        $this->actingAsAdmin();
        
        Student::factory()->count(3)->create();

        $response = $this->get('/students');

        $response->assertStatus(200);
        $response->assertViewIs('students.index');
    }

    /** @test */
    public function guest_cannot_view_students_list()
    {
        $response = $this->get('/students');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_user_can_view_create_student_form()
    {
        $this->actingAsAdmin();

        $response = $this->get('/students/create');

        $response->assertStatus(200);
        $response->assertViewIs('students.create');
    }

    /** @test */
    public function authenticated_user_can_create_student()
    {
        $this->actingAsAdmin();
        
        $bloodGroup = BloodGroup::factory()->create();
        $quota = Quota::factory()->create();
        $bise = BiseRecord::factory()->create();

        $studentData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '2005-01-15',
            'gender' => 'Male',
            'cnic' => '12345-1234567-1',
            
            // Student Attribute
            'blood_group_id' => $bloodGroup->id,
            'quota_id' => $quota->id,
            'religion' => 'Islam',
            'nationality' => 'Pakistani',
            'domicile' => 'Punjab',
            
            // Contact
            'permanent_address' => '123 Main St',
            'current_address' => '123 Main St',
            'phone_number' => '042-1234567',
            'mobile_number' => '0300-1234567',
            'emergency_contact' => '0301-1234567',
            'email' => 'john@example.com',
            
            // Father
            'father_name' => 'John Doe Sr',
            'father_cnic' => '12345-1234567-2',
            'father_occupation' => 'Business',
            'father_income' => '50,000 - 100,000',
            'father_phone' => '0300-7654321',
            
            // SSC Record
            'ssc_records' => [
                [
                    'bise_id' => $bise->id,
                    'roll_no' => '123456',
                    'reg_no' => '123-456-789',
                    'school_name' => 'ABC High School',
                    'passing_year' => '2023',
                    'passing_session' => '1st Annual',
                    'ssc_group' => 'Science',
                    'total_mark' => '1100',
                    'obtained_mark' => '900',
                ]
            ],
            
            // Admission
            'admission_year' => now()->year,
            'academic_year' => now()->year,
            'current_class' => '11',
            'college_roll_no' => '1001',
            'hssc_reg_no' => 'HSSC-1001',
            'program_type' => 'Inter',
            'status' => 'regular',
        ];

        $response = $this->post('/students', $studentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /** @test */
    public function student_creation_requires_first_name()
    {
        $this->actingAsAdmin();

        $response = $this->post('/students', [
            'last_name' => 'Doe',
            'date_of_birth' => '2005-01-15',
            'gender' => 'Male',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    /** @test */
    public function student_creation_requires_last_name()
    {
        $this->actingAsAdmin();

        $response = $this->post('/students', [
            'first_name' => 'John',
            'date_of_birth' => '2005-01-15',
            'gender' => 'Male',
        ]);

        $response->assertSessionHasErrors('last_name');
    }

    /** @test */
    public function authenticated_user_can_view_single_student()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission();

        $response = $this->get("/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertViewIs('students.show');
        $response->assertViewHas('student', $student);
    }

    /** @test */
    public function authenticated_user_can_view_edit_student_form()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission();

        $response = $this->get("/students/{$student->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('students.edit');
    }

    /** @test */
    public function authenticated_user_can_update_student()
    {
        $this->actingAsAdmin();
        
        $student = $this->createStudentWithAdmission();

        $response = $this->put("/students/{$student->id}", [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => $student->date_of_birth,
            'gender' => $student->gender,
            'student_cnic' => $student->cnic,
            
            // Include other required fields
            'blood_group_id' => $student->attribute->blood_group_id,
            'quota_id' => $student->attribute->quota_id,
            'religion' => $student->attribute->religion,
            'nationality' => $student->attribute->nationality,
            'domicile' => $student->attribute->domicile,
            
            'permanent_address' => $student->contact->permanent_address,
            'current_address' => $student->contact->current_address,
            'student_contact' => $student->contact->student_contact,
            'whatsapp_no' => $student->contact->whatsapp_no,
            'email' => $student->contact->email,
            'address' => $student->contact->address,
            
            'father_name' => $student->father->name,
            'father_cnic' => $student->father->cnic,
            'father_occupation' => $student->father->occupation,
            'father_annual_income' => $student->father->annual_income,
            'father_contact' => $student->father->contact,
            
            // Admission
            'admission_year' => $student->admissions->first()->admission_year,
            'academic_year' => $student->admissions->first()->academic_year,
            'college_roll_no' => $student->admissions->first()->college_roll_no,
            'hssc_reg_no' => $student->admissions->first()->hssc_reg_no,
            'current_class' => $student->admissions->first()->current_class,
            'program_type' => $student->admissions->first()->program_type ?? 'Inter',
            'status' => $student->admissions->first()->status ?? 'regular',
            
            // SSC
            'ssc' => [
                'bise_id' => $student->sscRecord->bise_id,
                'roll_no' => $student->sscRecord->roll_no,
                'reg_no' => $student->sscRecord->reg_no,
                'school_name' => $student->sscRecord->school_name,
                'total_mark' => $student->sscRecord->total_mark,
                'obtained_mark' => $student->sscRecord->obtained_mark,
                'passing_year' => $student->sscRecord->passing_year,
                'passing_session' => $student->sscRecord->passing_session,
                'ssc_group' => $student->sscRecord->ssc_group,
            ],
            
            // Subjects
            'elective_subjects' => $student->electives->pluck('subject_id')->toArray(),
            'compulsory_subjects' => $student->compulsorySubjects->pluck('subject_id')->toArray(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_student()
    {
        $this->actingAsAdmin();
        
        $student = Student::factory()->create();

        $response = $this->delete("/students/{$student->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    /** @test */
    public function authenticated_user_can_bulk_delete_students()
    {
        $this->actingAsAdmin();
        
        $students = Student::factory()->count(3)->create();
        $studentIds = $students->pluck('id')->toArray();

        $response = $this->post('/students/bulk-delete', [
            'student_ids' => $studentIds,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        foreach ($studentIds as $id) {
            $this->assertSoftDeleted('students', ['id' => $id]);
        }
    }

    /** @test */
    public function students_list_can_be_filtered_by_class()
    {
        $this->actingAsAdmin();
        
        $student11 = $this->createStudentWithAdmission('11');
        $student12 = $this->createStudentWithAdmission('12');

        $response = $this->get('/students?class=11');

        $response->assertStatus(200);
        $response->assertSee($student11->full_name);
    }

    /** @test */
    public function students_list_can_be_searched_by_name()
    {
        $this->actingAsAdmin();
        
        $john = Student::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $jane = Student::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $response = $this->get('/students?search=John');

        $response->assertStatus(200);
        $response->assertSee('John Doe');
    }
}
