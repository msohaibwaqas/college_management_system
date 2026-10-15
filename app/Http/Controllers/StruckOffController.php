<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StudentStruckOffLog;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\Subject;

class StruckOffController extends Controller
{
    // -------------------------------------------------------
    // TEACHER: view students eligible to be struck off
    // (those who have > 10 absents in a specific subject taught
    // by this teacher)
    // -------------------------------------------------------

    public function teacherPanel(Request $request)
    {
        $user  = Auth::user();
        $staff = $user->staff;

        if (!$staff) {
            return redirect()->route('dashboard')->with('error', 'No staff record linked to your account.');
        }

        // Get all subject-class combinations the teacher is assigned to
        $subjectId = $request->query('subject_id');
        $year      = $request->query('year');

        // Subjects/classes this teacher teaches
        $mySubjects = DB::table('student_teacher_subjects')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->where('student_teacher_subjects.staff_id', $staff->id)
            ->whereIn('admissions.status', ['active', 'admitted'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->select(
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'admissions.current_class as current_class',
                'admissions.admission_year as admission_year',
            )
            ->groupBy('subjects.id', 'subjects.name', 'admissions.current_class', 'admissions.admission_year')
            ->orderByDesc('admissions.admission_year')
            ->get();

        $eligibleStudents = collect();

        if ($subjectId && $year) {
            // Count absents per student for this specific subject only
            $eligibleStudents = DB::table('student_teacher_subjects')
                ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
                ->join('admissions', 'students.id', '=', 'admissions.student_id')
                ->leftJoin('fathers', 'students.id', '=', 'fathers.student_id')
                ->join(
                    // Sub-select: absence count per student in this subject
                    DB::raw('(
                        SELECT student_id, COUNT(*) AS absence_count
                        FROM attendances
                        WHERE subject_id = ' . (int)$subjectId . '
                          AND status = \'absent\'
                        GROUP BY student_id
                    ) AS abs'),
                    'students.id', '=', 'abs.student_id'
                )
                ->where('student_teacher_subjects.staff_id', $staff->id)
                ->where('student_teacher_subjects.subject_id', $subjectId)
                ->where('admissions.admission_year', $year)
                ->whereIn('admissions.status', ['active', 'admitted'])
                ->whereNull('student_teacher_subjects.deleted_at')
                ->whereNull('students.deleted_at')
                ->whereNull('admissions.deleted_at')
                ->where('abs.absence_count', '>', 10)
                ->select(
                    'students.id as student_id',
                    'students.first_name',
                    'students.last_name',
                    'admissions.id as admission_id',
                    'admissions.college_roll_no as roll_number',
                    'admissions.current_class',
                    'admissions.academic_year',
                    'fathers.name as father_name',
                    'abs.absence_count',
                )
                ->orderByDesc('abs.absence_count')
                ->get();

            // Annotate with already-struck-off flag
            $struckOffStudentIds = StudentStruckOffLog::where('status', 'struck_off')
                ->whereIn('student_id', $eligibleStudents->pluck('student_id'))
                ->pluck('student_id')
                ->toArray();

            foreach ($eligibleStudents as $s) {
                $s->already_struck_off = in_array($s->student_id, $struckOffStudentIds);
            }
        }

        $selectedSubjectId = $subjectId;
        $selectedYear      = $year;

        return view('struck-off.teacher', compact(
            'mySubjects', 'eligibleStudents', 'selectedSubjectId', 'selectedYear'
        ));
    }

    // -------------------------------------------------------
    // TEACHER: strike off a student
    // -------------------------------------------------------

    public function store(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'subject_id'  => 'required|exists:subjects,id',
            'reason'      => 'nullable|string|max:500',
        ]);

        $user  = Auth::user();
        $staff = $user->staff;

        if (!$staff) {
            return back()->with('error', 'No staff record linked to your account.');
        }

        $studentId = $request->student_id;
        $subjectId = $request->subject_id;

        // --- Verify teacher is assigned to this student in this subject ---
        $assignment = DB::table('student_teacher_subjects')
            ->where('staff_id', $staff->id)
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->whereNull('deleted_at')
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not authorized to strike off this student.');
        }

        // --- Verify absence threshold ---
        $absenceCount = Attendance::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('status', 'absent')
            ->count();

        if ($absenceCount <= 10) {
            return back()->with('error', "Student only has {$absenceCount} absents in this subject. Must exceed 10 to strike off.");
        }

        // --- Check if already struck off ---
        $alreadyStruckOff = StudentStruckOffLog::where('student_id', $studentId)
            ->where('status', 'struck_off')
            ->exists();

        if ($alreadyStruckOff) {
            return back()->with('error', 'This student is already struck off. They must be readmitted before being struck off again.');
        }

        // --- Get active admission ---
        $admission = Admissions::where('student_id', $studentId)
            ->whereIn('status', ['Active', 'active', 'Admitted', 'admitted'])
            ->first();

        if (!$admission) {
            return back()->with('error', 'No active admission record found for this student.');
        }

        DB::transaction(function () use ($studentId, $admission, $staff, $subjectId, $absenceCount, $request) {
            // 1. Create the struck-off log
            StudentStruckOffLog::create([
                'student_id'             => $studentId,
                'admission_id'           => $admission->id,
                'struck_off_by'          => $staff->id,
                'subject_id'             => $subjectId,
                'absence_count_at_strike'=> $absenceCount,
                'struck_off_at'          => now(),
                'struck_off_reason'      => $request->reason,
                'academic_year'          => $admission->academic_year,
                'status'                 => 'struck_off',
            ]);

            // 2. Update admission status
            $admission->update(['status' => 'Struck Off']);
        });

        $student = Student::find($studentId);
        return back()->with('success', "Student {$student->full_name} has been struck off successfully.");
    }

    // -------------------------------------------------------
    // ADMIN / CLERK / PRINCIPAL: list all struck-off students
    // -------------------------------------------------------

    public function index(Request $request)
    {
        $query = StudentStruckOffLog::with([
                'student',
                'struckOffBy',         // Staff (teacher)
                'subject',
                'readmittedBy',        // User (admin/clerk)
                'admission',
            ])
            ->orderByDesc('struck_off_at');

        // Filter by status
        $statusFilter = $request->query('status', 'struck_off');
        if ($statusFilter === 'all') {
            // show everything
        } elseif ($statusFilter === 'readmitted') {
            $query->where('status', 'readmitted');
        } else {
            $query->where('status', 'struck_off');
        }

        // Filter by academic year
        if ($request->filled('year')) {
            $query->where('academic_year', $request->year);
        }

        $logs = $query->paginate(30)->withQueryString();

        $availableYears = StudentStruckOffLog::select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        return view('struck-off.index', compact('logs', 'statusFilter', 'availableYears'));
    }

    // -------------------------------------------------------
    // ADMIN / CLERK: readmit a struck-off student
    // -------------------------------------------------------

    public function readmit(Request $request, StudentStruckOffLog $log)
    {
        $request->validate([
            'readmission_note' => 'nullable|string|max:500',
        ]);

        if ($log->status !== 'struck_off') {
            return back()->with('error', 'This student is not currently struck off.');
        }

        // --- Enforce 2 readmissions per academic year ---
        $readmissionCount = StudentStruckOffLog::where('student_id', $log->student_id)
            ->where('academic_year', $log->academic_year)
            ->where('status', 'readmitted')
            ->count();

        if ($readmissionCount >= 2) {
            return back()->with('error', "This student has already been readmitted {$readmissionCount} times this academic year. Maximum 2 readmissions per year are allowed.");
        }

        DB::transaction(function () use ($log, $request) {
            // 1. Update the log entry
            $log->update([
                'status'           => 'readmitted',
                'readmitted_by'    => Auth::id(),
                'readmitted_at'    => now(),
                'readmission_note' => $request->readmission_note,
            ]);

            // 2. Restore admission status to Active
            Admissions::where('id', $log->admission_id)
                ->update(['status' => 'Active']);
        });

        $student = $log->student;
        return back()->with('success', "Student {$student->full_name} has been readmitted successfully.");
    }

    // -------------------------------------------------------
    // View full history for a single student
    // -------------------------------------------------------

    public function history(Student $student)
    {
        $logs = StudentStruckOffLog::where('student_id', $student->id)
            ->with(['struckOffBy', 'subject', 'readmittedBy'])
            ->orderByDesc('struck_off_at')
            ->get();

        $totalStruckOff   = $logs->count();
        $currentYear      = date('Y');
        $struckOffThisYear = $logs->where('academic_year', $currentYear)->count();
        $readmissionsThisYear = $logs->where('academic_year', $currentYear)->where('status', 'readmitted')->count();

        return view('struck-off.history', compact(
            'student', 'logs', 'totalStruckOff', 'struckOffThisYear', 'readmissionsThisYear'
        ));
    }
}
