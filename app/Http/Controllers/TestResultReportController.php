<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class TestResultReportController extends Controller
{
    /**
     * Show the test result report form
     */
    public function index(Request $request)
    {
        // Get distinct admission years
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        // Get distinct exam types
        $examTypes = Result::select('exam_type')
            ->distinct()
            ->orderBy('exam_type')
            ->pluck('exam_type');

        $reportData = [];
        $selectedYear = $request->admission_year;
        $selectedExamType = $request->exam_type;
        $selectedClassLevel = $request->class_level; // NEW
        $customPassPercentage = $request->pass_percentage;

        if ($selectedYear && $selectedExamType && $selectedClassLevel && is_numeric($customPassPercentage)) {
            $reportData = $this->generateReportData($selectedYear, $selectedExamType, $selectedClassLevel, $customPassPercentage);
        }

        return view('reports.test-result-report.index', compact('admissionYears', 'examTypes', 'reportData', 'selectedYear', 'selectedExamType', 'selectedClassLevel', 'customPassPercentage'));
    }

    private function generateReportData($admissionYear, $examType, $classLevel, $passPercentage)
    {
        // 1. Get all assigned (teacher, subject) pairs for this admission year and class level
        $assignments = DB::table('student_teacher_subjects')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->where('admissions.admission_year', $admissionYear)
            ->where('student_teacher_subjects.class_level', $classLevel)
            ->whereNull('admissions.deleted_at')
            ->whereNull('students.deleted_at')
            ->select('student_teacher_subjects.staff_id', 'student_teacher_subjects.subject_id')
            ->distinct()
            ->get();

        $data = [];

        foreach ($assignments as $assignment) {
            $staff = Staff::find($assignment->staff_id);
            $subject = Subject::find($assignment->subject_id);

            if (!$staff || !$subject) continue;

            // Total Enrolled in this subject for this year (Global)
            $enrolledCount = $this->getEnrolledCount($subject, $admissionYear, $classLevel);

            // Students assigned to THIS teacher for THIS subject in THIS year and class level
            $assignedStudentsQuery = DB::table('student_teacher_subjects')
                ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
                ->where('student_teacher_subjects.subject_id', $subject->id)
                ->where('student_teacher_subjects.staff_id', $staff->id)
                ->where('student_teacher_subjects.class_level', $classLevel)
                ->where('admissions.admission_year', $admissionYear)
                ->whereNull('admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->select('student_teacher_subjects.student_id');
            
            $assignedCount = $assignedStudentsQuery->count();
            $assignedStudentIds = $assignedStudentsQuery->pluck('student_id')->toArray();

            if ($assignedCount == 0) continue;

            // Calculate Results for these assigned students
            $results = Result::whereIn('student_id', $assignedStudentIds)
                ->where('subject_id', $subject->id)
                ->where('exam_type', $examType)
                ->where('class_level', $classLevel) // Separate by Class Level
                ->where('academic_year', $admissionYear) // Filter by academic year
                ->orderBy('id', 'asc') // Guarantee latest record overrides the old one in keyBy
                ->get();
            
            $pass = 0;
            $fail = 0;
            $absent = 0;
            
            // Map results by student_id to easily check who has a result
            $resultsByStudent = $results->keyBy('student_id');

            foreach ($assignedStudentIds as $studentId) {
                if ($resultsByStudent->has($studentId)) {
                    $result = $resultsByStudent[$studentId];
                    
                    if ($result->is_absent) {
                        $absent++;
                    } else {
                        // Calculate Pass/Fail based on Custom Percentage
                        $totalMarks = $result->total_marks > 0 ? $result->total_marks : 100; // Safe fallback?
                        $obtained = $result->total_obtained_marks; // Accessor in Result model usually sums theory+practical
                        
                        // If total_marks is 0, we can't calculate percentage, assume fail? or omit?
                        if ($totalMarks > 0) {
                            $obtainedPercentage = ($obtained / $totalMarks) * 100;
                            if ($obtainedPercentage >= $passPercentage) {
                                $pass++;
                            } else {
                                $fail++;
                            }
                        } else {
                            $fail++; // or treat as error
                        }
                    }
                } else {
                    // No result found. Treat as Absent or Not Appeared? 
                    // Let's stick to counting explicit results mostly, but maybe counted as absent if we want strictness?
                    // For consistency with FinalResultReport, we ignore them if no record exists.
                }
            }
            
            $appeared = $pass + $fail;
            $actualPassPercentage = $appeared > 0 ? round(($pass / $appeared) * 100, 2) : 0;

            $data[] = [
                'teacher_name' => $staff->full_name,
                'subject_name' => $subject->name,
                'year' => $admissionYear,
                'total_enrolled' => $enrolledCount,
                'total_assigned' => $assignedCount,
                'pass' => $pass,
                'fail' => $fail,
                'absent' => $absent,
                'pass_percentage' => $actualPassPercentage,
            ];
        }
        
        // Group by Subject Name for merged display
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row['subject_name']][] = $row;
        }

        // Sort by Subject Name
        ksort($groupedData);

        // Sort Teachers within each Subject group
        foreach ($groupedData as &$rows) {
            usort($rows, function($a, $b) {
                return strcmp($a['teacher_name'], $b['teacher_name']);
            });
        }
        unset($rows);

        return $groupedData;
    }

    private function getEnrolledCount($subject, $admissionYear, $classLevel)
    {
        if ($subject->type === 'Compulsory') {
             return DB::table('student_compulsory_subjects')
                ->join('admissions', 'student_compulsory_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'student_compulsory_subjects.student_id', '=', 'students.id')
                ->where('student_compulsory_subjects.subject_id', $subject->id)
                ->where('admissions.current_class', $classLevel)
                ->where('admissions.admission_year', $admissionYear)
                ->whereNull('admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->count();
        } else {
             return DB::table('student_elective_subjects')
                ->join('admissions', 'student_elective_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'student_elective_subjects.student_id', '=', 'students.id')
                ->where('student_elective_subjects.subject_id', $subject->id)
                ->where('admissions.current_class', $classLevel)
                ->where('admissions.admission_year', $admissionYear)
                ->whereNull('admissions.deleted_at')
                ->whereNull('students.deleted_at')
                ->count();
        }
    }
}
