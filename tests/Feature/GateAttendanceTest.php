<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\GateAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class GateAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_scan_attendance_using_padded_student_id()
    {
        $this->actingAsAdmin();
        $student = $this->createStudentWithAdmission();

        $response = $this->postJson(route('attendance.gate.scan'), [
            'student_id' => str_pad((string)$student->id, 6, '0', STR_PAD_LEFT)
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'in',
            ]);

        $this->assertDatabaseHas('gate_attendances', [
            'student_id' => $student->id,
            'date' => Carbon::today(),
        ]);
    }

    /** @test */
    public function it_can_scan_attendance_using_student_id()
    {
        $this->actingAsAdmin();
        $student = $this->createStudentWithAdmission();

        $response = $this->postJson(route('attendance.gate.scan'), [
            'student_id' => $student->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'in',
            ]);

        $this->assertDatabaseHas('gate_attendances', [
            'student_id' => $student->id,
            'date' => Carbon::today(),
            'status' => 'Present'
        ]);
    }

    /** @test */
    public function it_can_scan_attendance_using_college_roll_no()
    {
        $this->actingAsAdmin();
        $student = $this->createStudentWithAdmission();
        $admission = $student->admissions()->first();
        $rollNo = 'ROLL-123';
        $admission->update(['college_roll_no' => $rollNo]);

        $response = $this->postJson(route('attendance.gate.scan'), [
            'student_id' => $rollNo // Testing that roll number works in the student_id field
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'in',
            ]);

        $this->assertDatabaseHas('gate_attendances', [
            'student_id' => $student->id,
            'date' => Carbon::today(),
        ]);
    }

    /** @test */
    public function it_returns_error_if_multiple_active_students_have_same_roll_no()
    {
        $this->actingAsAdmin();
        $rollNo = 'ROLL-CONFLICT';
        
        // Create first active student with this roll no in year 2024
        $student1 = $this->createStudentWithAdmission('12', 2024);
        $student1->admissions()->first()->update(['college_roll_no' => $rollNo]);
        
        // Create second active student with this roll no in year 2025
        $student2 = $this->createStudentWithAdmission('11', 2025);
        $student2->admissions()->first()->update(['college_roll_no' => $rollNo]);

        $response = $this->postJson(route('attendance.gate.scan'), [
            'student_id' => $rollNo
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Multiple active students found with Roll No ' . $rollNo . '. Please use the barcode for unique identification.',
            ]);
    }

    /** @test */
    public function it_returns_error_for_invalid_identifier()
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('attendance.gate.scan'), [
            'student_id' => 'NON-EXISTENT'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found with ID or Roll No: NON-EXISTENT',
            ]);
    }
}
