<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HeAdmission;
use App\Models\Program;
use App\Models\BsSubject;
use App\Models\HeResult;
use App\Models\HeAttendance;
use App\Models\Staff;
use App\Models\InstituteSetting;
use App\Models\HeTeacherAssignment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HeReportController extends Controller
{
    /**
     * Show award list form
     */
    public function awardListForm()
    {
        $programs = Program::all();
        $admissionYears = HeAdmission::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        return view('he.reports.award_list_form', compact('programs', 'admissionYears'));
    }

    /**
     * Generate award list
     */
    public function generateAwardList(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1|max:12',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'admission_year' => 'required|integer',
        ]);

        $program = Program::find($request->program_id);
        $subject = BsSubject::find($request->bs_subject_id);
        $semester = $request->semester_no;
        $year = $request->admission_year;

        // Get students enrolled in this subject, program, and year
        $students = HeAdmission::with(['student.father', 'student.contact'])
            ->where('program_id', $request->program_id)
            ->where('admission_year', $request->admission_year)
            ->whereHas('subjects', function($q) use ($request) {
                $q->where('bs_subject_id', $request->bs_subject_id)
                  ->where('semester_no', $request->semester_no);
            })
            ->get()
            ->sortBy('roll_no');

        // Get teacher assigned to this subject/program
        $teacher = Staff::whereHas('heTeacherAssignments', function($q) use ($request) {
            $q->where('bs_subject_id', $request->bs_subject_id);
        })->first();

        $settings = InstituteSetting::getSettings();

        return view('he.reports.award_list', compact('students', 'program', 'subject', 'semester', 'year', 'teacher', 'settings'));
    }

    /**
     * Show tabulation sheet form
     */
    public function tabulationSheetForm()
    {
        $programs = Program::all();
        $admissionYears = HeAdmission::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        return view('he.reports.tabulation_sheet_form', compact('programs', 'admissionYears'));
    }

    /**
     * Generate tabulation sheet
     */
    public function generateTabulationSheet(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1|max:12',
            'exam_type' => 'required|in:Mid,Final',
            'admission_year' => 'required|integer',
        ]);

        $program = Program::find($request->program_id);
        $semester = $request->semester_no;
        $examType = $request->exam_type;
        $year = $request->admission_year;

        // Get all subjects for this program and semester
        $subjects = BsSubject::whereHas('semesters', function($q) use ($request, $semester) {
            $q->where('program_id', $request->program_id)
              ->where('semester_no', $semester);
        })->get();

        // Get students
        $students = HeAdmission::with(['student.father', 'student.contact'])
            ->where('program_id', $request->program_id)
            ->where('admission_year', $year)
            ->get()
            ->sortBy('roll_no');

        // Get results
        $results = HeResult::whereIn('he_admission_id', $students->pluck('id'))
            ->where('semester_no', $semester)
            ->where('exam_type', $examType)
            ->get()
            ->groupBy('he_admission_id');

        $settings = InstituteSetting::getSettings();

        return view('he.reports.tabulation_sheet', compact('students', 'subjects', 'results', 'program', 'semester', 'examType', 'year', 'settings'));
    }

    /**
     * Search student for transcript
     */
    public function transcriptSearch(Request $request)
    {
        $query = HeAdmission::with(['student', 'program']);

        if ($request->filled('roll_no')) {
            $query->where('roll_no', $request->roll_no)
                  ->orWhere('uni_roll_no', $request->roll_no);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(20);

        return view('he.reports.transcript_search', compact('students'));
    }

    /**
     * Generate individual transcript
     */
    public function generateTranscript(HeAdmission $admission)
    {
        $admission->load(['student', 'program', 'heResults.bsSubject']);
        
        $resultsBySemester = $admission->heResults
            ->where('exam_type', 'Final')
            ->groupBy('semester_no');

        $settings = InstituteSetting::getSettings();

        // Calculate CGPA
        $totalQualityPoints = 0;
        $totalCreditHours = 0;

        foreach ($admission->heResults->where('exam_type', 'Final') as $result) {
            $creditHours = ($result->bsSubject->theory_credit_hours ?? 0) + ($result->bsSubject->practical_credit_hours ?? 0);
            $totalQualityPoints += $result->gpa * $creditHours;
            $totalCreditHours += $creditHours;
        }

        $cgpa = $totalCreditHours > 0 ? round($totalQualityPoints / $totalCreditHours, 2) : 0.0;

        return view('he.reports.transcript', compact('admission', 'resultsBySemester', 'settings', 'cgpa'));
    }

    /**
     * Show attendance report form
     */
    public function attendanceReportForm()
    {
        $programs = Program::all();
        return view('he.reports.attendance_report_form', compact('programs'));
    }

    /**
     * Generate attendance report
     */
    public function generateAttendanceReport(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $program = Program::find($request->program_id);
        $subject = BsSubject::find($request->bs_subject_id);
        $semester = $request->semester_no;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $students = HeAdmission::with(['student.father', 'student.contact'])
            ->where('program_id', $request->program_id)
            ->whereHas('subjects', function($q) use ($request) {
                $q->where('bs_subject_id', $request->bs_subject_id)
                  ->where('semester_no', $request->semester_no);
            })
            ->get()
            ->sortBy('roll_no');

        $attendanceData = HeAttendance::whereIn('he_admission_id', $students->pluck('id'))
            ->where('bs_subject_id', $request->bs_subject_id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get()
            ->groupBy('he_admission_id');

        $settings = InstituteSetting::getSettings();

        return view('he.reports.attendance_report', compact('students', 'attendanceData', 'program', 'subject', 'semester', 'startDate', 'endDate', 'settings'));
    }
}
