<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_view_staff_list()
    {
        $this->actingAsAdmin();
        
        Staff::factory()->count(3)->create();

        $response = $this->get('/staff');

        $response->assertStatus(200);
        $response->assertViewIs('staff.index');
    }

    /** @test */
    public function authenticated_user_can_view_create_staff_form()
    {
        $this->actingAsAdmin();

        $response = $this->get('/staff/create');

        $response->assertStatus(200);
        $response->assertViewIs('staff.create');
    }

    /** @test */
    public function authenticated_user_can_create_staff()
    {
        $this->actingAsAdmin();

        $staffData = [
            'staff_id' => 'STAFF-12345',
            'first_name' => 'John',
            'last_name' => 'Teacher',
            'father_name' => 'Father Name',
            'cnic' => '12345-1234567-1',
            'date_of_birth' => '1980-01-01',
            'gender' => 'male',
            'marital_status' => 'Married',
            'religion' => 'Islam',
            'nationality' => 'Pakistani',
            'contact_number' => '0300-1234567',
            'email' => 'john.teacher@example.com',
            'permanent_address' => '123 Main St',
            'current_address' => '123 Main St',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_number' => '0301-1234567',
            'date_of_joining' => '2020-01-01',
            'designation' => 'Lecturer',
            'department' => 'Science',
            'bps' => 17,
            'category' => 'teaching',
            'employment_type' => 'Permanent',
            'salary' => 50000,
            'status' => 'active',
        ];

        $response = $this->post('/staff', $staffData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('staff', [
            'first_name' => 'John',
            'last_name' => 'Teacher',
            'email' => 'john.teacher@example.com',
        ]);
    }

    /** @test */
    public function staff_creation_requires_first_name()
    {
        $this->actingAsAdmin();

        $response = $this->post('/staff', [
            'last_name' => 'Teacher',
            'email' => 'teacher@example.com',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    /** @test */
    public function staff_email_must_be_unique()
    {
        $this->actingAsAdmin();
        
        Staff::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/staff', [
            'staff_id' => 'STAFF-54321',
            'first_name' => 'John',
            'last_name' => 'Teacher',
            'father_name' => 'Father Name',
            'cnic' => '54321-7654321-1',
            'date_of_birth' => '1985-01-01',
            'gender' => 'male',
            'email' => 'existing@example.com',
            'category' => 'teaching',
            'designation' => 'Lecturer',
            'bps' => 17,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function authenticated_user_can_view_single_staff()
    {
        $this->actingAsAdmin();
        
        $staff = Staff::factory()->create();

        $response = $this->get("/staff/{$staff->id}");

        $response->assertStatus(200);
        $response->assertViewIs('staff.show');
    }

    /** @test */
    public function authenticated_user_can_update_staff()
    {
        $this->actingAsAdmin();
        
        $staff = Staff::factory()->create();

        $response = $this->put("/staff/{$staff->id}", [
            'staff_id' => $staff->staff_id,
            'first_name' => 'Jane',
            'last_name' => 'Updated',
            'father_name' => $staff->father_name,
            'cnic' => $staff->cnic,
            'date_of_birth' => $staff->date_of_birth->format('Y-m-d'),
            'gender' => $staff->gender,
            'marital_status' => $staff->marital_status,
            'religion' => $staff->religion,
            'nationality' => $staff->nationality,
            'contact_number' => '0300-7654321', // Changed from phone_number to contact_number
            'email' => 'jane.updated@example.com',
            'permanent_address' => $staff->permanent_address,
            'current_address' => $staff->current_address,
            'emergency_contact_name' => $staff->emergency_contact_name,
            'emergency_contact_number' => $staff->emergency_contact_number,
            'date_of_joining' => $staff->date_of_joining,
            'designation' => $staff->designation,
            'department' => $staff->department,
            'bps' => $staff->bps,
            'category' => $staff->category,
            'employment_type' => $staff->employment_type,
            'salary' => $staff->salary,
            'status' => $staff->status,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'first_name' => 'Jane',
            'last_name' => 'Updated',
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_staff()
    {
        $this->actingAsAdmin();
        
        $staff = Staff::factory()->create();

        $response = $this->delete("/staff/{$staff->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('staff', ['id' => $staff->id]);
    }

    /** @test */
    public function staff_list_can_be_filtered_by_department()
    {
        $this->actingAsAdmin();
        
        $scienceStaff = Staff::factory()->create(['department' => 'Science']);
        $artsStaff = Staff::factory()->create(['department' => 'Arts']);

        $response = $this->get('/staff?department=Science');

        $response->assertStatus(200);
        $response->assertSee($scienceStaff->first_name);
    }

    /** @test */
    public function staff_list_can_be_filtered_by_designation()
    {
        $this->actingAsAdmin();
        
        $lecturer = Staff::factory()->create(['designation' => 'Lecturer']);
        $professor = Staff::factory()->create(['designation' => 'Professor']);

        $response = $this->get('/staff?designation=Lecturer');

        $response->assertStatus(200);
        $response->assertSee($lecturer->first_name);
    }
}
