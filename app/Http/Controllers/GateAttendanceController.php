<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\GateAttendance;
use Carbon\Carbon;

class GateAttendanceController extends Controller
{
    public function index()
    {
        return view('attendance.gate.index');
    }

    public function scan(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
        ]);

        $studentId = $request->student_id;
        
        // 1. Try to find student by primary ID first (direct scan or manual ID entry)
        $student = Student::with('admissions')->find($studentId);

        // 1b. If not found, try to find by removing leading zeros (for padded barcodes)
        if (!$student && is_numeric($studentId)) {
            $unpaddedId = ltrim($studentId, '0');
            if ($unpaddedId !== $studentId && !empty($unpaddedId)) {
                $student = Student::with('admissions')->find($unpaddedId);
            } elseif ($unpaddedId === '' && $studentId !== '') {
                // If the input was all zeros, it could be student ID 0 (rare but possible) or just invalid
                $student = Student::with('admissions')->find(0);
            }
        }

        // 2. If not found, try to find by college_roll_no in active admissions
        if (!$student) {
            $admissions = \App\Models\Admissions::where('college_roll_no', $studentId)
                ->whereIn('status', ['Active', 'Admitted'])
                ->with('student')
                ->get();
            
            if ($admissions->count() === 1) {
                $student = $admissions->first()->student;
            } elseif ($admissions->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Multiple active students found with Roll No ' . $studentId . '. Please use the barcode for unique identification.',
                ]);
            }
        }

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with ID or Roll No: ' . $studentId,
            ]);
        }
        
        $studentId = $student->id; // Use clear ID for subsequent operations

        $today = Carbon::today();
        $currentTime = Carbon::now()->format('H:i:s');

        // Check if attendance already exists for today
        $attendance = GateAttendance::where('student_id', $studentId)
            ->where('date', $today->format('Y-m-d'))
            ->first();

        if (!$attendance) {
            // First scan of the day -> In Time
            $attendance = GateAttendance::create([
                'student_id' => $studentId,
                'date' => $today,
                'in_time' => $currentTime,
                'status' => 'Present',
            ]);

            return response()->json([
                'success' => true,
                'action' => 'in',
                'message' => 'In-time recorded successfully.',
                'student' => [
                    'name' => $student->full_name,
                    'class' => $student->current_class,
                    'photo' => $student->getProfilePictureUrl(),
                ],
                'time' => Carbon::parse($currentTime)->format('h:i A'),
            ]);
        } else if (is_null($attendance->out_time)) {
            // Second scan of the day -> Out Time
            // Removed buffer to prevent timezone/clock-skew issues

            $attendance->update([
                'out_time' => $currentTime,
            ]);

            return response()->json([
                'success' => true,
                'action' => 'out',
                'message' => 'Out-time recorded successfully.',
                'student' => [
                    'name' => $student->full_name,
                    'class' => $student->current_class,
                    'photo' => $student->getProfilePictureUrl(),
                ],
                'time' => Carbon::parse($currentTime)->format('h:i A'),
            ]);
        } else {
            // Already scanned in and out
            return response()->json([
                'success' => false,
                'message' => 'Student already marked in and out for today.',
            ]);
        }
    }
}
