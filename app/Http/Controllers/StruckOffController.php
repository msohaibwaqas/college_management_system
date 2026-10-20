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
use App\Models\StruckOffSetting;
use Carbon\Carbon;

class StruckOffController extends Controller
{
    // -------------------------------------------------------
    // TEACHER & ADMIN: view students eligible to be struck off
    // -------------------------------------------------------
    public function teacherPanel(Request $request)
    {
        $user     = Auth::user();
        $staff    = $user->staff;
        $staffId  = $staff ? $staff->id : null;
        $settings = StruckOffSetting::getSettings();

        // 1. Min Absents is strictly controlled by Admin policy
        $threshold = (int) $settings->absent_threshold;
        if ($threshold < 1) {
            $threshold = 10;
        }

        // 2. Parse request parameters
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $month = $request->query('month', $settings->calculation_period === 'monthly' ? now()->format('Y-m') : 'all');

        // Handle class_subject format: subject_id:class:year
        if ($request->filled('class_subject') && str_contains($request->class_subject, ':')) {
            $parts     = explode(':', $request->class_subject);
            $subjectId = (int) ($parts[0] ?? 0);
            $class     = $parts[1] ?? null;
            $optYear   = (int) ($parts[2] ?? 0);
            // If year wasn't explicitly set in query, use option's year
            if (!$year && $optYear > 0) {
                $year = $optYear;
            }
        } else {
            $subjectId = $request->filled('subject_id') ? (int) $request->query('subject_id') : null;
            $class     = $request->query('class');
        }

        // 3. Fetch ONLY assigned classes & subjects for this specific teacher
        $subjectsQuery = DB::table('student_teacher_subjects')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNotIn('admissions.status', ['Struck Off', 'struck_off', 'Graduated'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('admissions.deleted_at');

        // Strictly enforce teacher assignment filter if user has a linked staff record
        if ($staffId) {
            $subjectsQuery->where('student_teacher_subjects.staff_id', $staffId);
        }

        $mySubjects = $subjectsQuery->select(
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'admissions.current_class as current_class',
                'admissions.admission_year as admission_year',
                DB::raw('COUNT(DISTINCT student_teacher_subjects.student_id) as student_count')
            )
            ->groupBy('subjects.id', 'subjects.name', 'admissions.current_class', 'admissions.admission_year')
            ->orderByDesc('admissions.admission_year')
            ->orderBy('admissions.current_class')
            ->orderBy('subjects.name')
            ->get();

        $availableYears = $mySubjects->pluck('admission_year')->filter()->unique()->values()->sortDesc();

        // Default to first available year if none selected
        if (!$year && $availableYears->isNotEmpty()) {
            $year = (int) $availableYears->first();
        }

        // Filter subjects to ONLY the classes belonging to the selected batch/year
        $subjectsForYear = $mySubjects->where('admission_year', $year)->values();

        // Ensure selected subject belongs to this batch
        $validCurrentSubject = $subjectsForYear->first(function ($item) use ($subjectId, $class) {
            return (int) $item->subject_id === (int) $subjectId && (!$class || $item->current_class == $class);
        });

        if ($validCurrentSubject) {
            $subjectId = (int) $validCurrentSubject->subject_id;
            $class     = $validCurrentSubject->current_class;
        } elseif ($subjectsForYear->isNotEmpty()) {
            $firstSub  = $subjectsForYear->first();
            $subjectId = (int) $firstSub->subject_id;
            $class     = $firstSub->current_class;
        } else {
            $subjectId = null;
            $class     = null;
        }

        // 4. Available months from attendance records
        $availableMonths = DB::table('attendances')
            ->select(DB::raw("DATE_FORMAT(attendance_date, '%Y-%m') as m"))
            ->distinct()
            ->orderByDesc('m')
            ->limit(12)
            ->pluck('m');

        // 5. Query eligible students strictly assigned to this teacher
        $eligibleStudents = collect();

        if ($subjectId && $year) {
            $attQuery = DB::table('attendances')
                ->where('subject_id', $subjectId)
                ->where('status', 'absent');

            if ($month && $month !== 'all') {
                $attQuery->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$month]);
            }

            $absSubQuery = $attQuery->select('student_id', DB::raw('COUNT(*) AS absence_count'))
                ->groupBy('student_id');

            $eligibleQuery = DB::table('student_teacher_subjects')
                ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
                ->join('admissions', 'students.id', '=', 'admissions.student_id')
                ->leftJoin('fathers', 'students.id', '=', 'fathers.student_id')
                ->joinSub($absSubQuery, 'abs', function ($join) {
                    $join->on('students.id', '=', 'abs.student_id');
                })
                ->where('student_teacher_subjects.subject_id', $subjectId)
                ->where('admissions.admission_year', $year)
                ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
                ->whereNull('student_teacher_subjects.deleted_at')
                ->whereNull('students.deleted_at')
                ->whereNull('admissions.deleted_at')
                ->where('abs.absence_count', '>=', $threshold);

            // Strictly filter by this specific teacher's assignments
            if ($staffId) {
                $eligibleQuery->where('student_teacher_subjects.staff_id', $staffId);
            }

            if ($class) {
                $eligibleQuery->where('admissions.current_class', $class);
            }

            $eligibleStudents = $eligibleQuery->select(
                'students.id as student_id',
                'students.first_name',
                'students.last_name',
                'admissions.id as admission_id',
                'admissions.college_roll_no as roll_number',
                'admissions.current_class',
                'admissions.admission_year',
                'admissions.academic_year',
                'fathers.name as father_name',
                'abs.absence_count'
            )
            ->distinct()
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
        $selectedClass     = $class;
        $selectedMonth     = $month;
        $selectedThreshold = $threshold;

        return view('struck-off.teacher', compact(
            'mySubjects',
            'subjectsForYear',
            'eligibleStudents',
            'selectedSubjectId',
            'selectedYear',
            'selectedClass',
            'selectedMonth',
            'selectedThreshold',
            'availableYears',
            'availableMonths',
            'settings',
            'staff'
        ));
    }

    // -------------------------------------------------------
    // TEACHER & ADMIN: strike off an eligible student
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'subject_id'  => 'required|exists:subjects,id',
            'month'       => 'nullable|string',
            'reason'      => 'nullable|string|max:500',
        ]);

        $user      = Auth::user();
        $staff     = $user->staff;
        $staffId   = $staff ? $staff->id : null;
        $studentId = (int) $request->student_id;
        $subjectId = (int) $request->subject_id;
        $settings  = StruckOffSetting::getSettings();
        $threshold = (int) $settings->absent_threshold; // Admin-set threshold only
        $month     = $request->input('month');

        // Strictly verify assignment to this teacher if staff record exists
        if ($staffId) {
            $assignment = DB::table('student_teacher_subjects')
                ->where('staff_id', $staffId)
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->whereNull('deleted_at')
                ->first();

            if (!$assignment) {
                return back()->with('error', 'You are not assigned to teach this student in this subject.');
            }
        }

        // Verify absence count meets policy threshold
        $attQuery = Attendance::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('status', 'absent');

        if ($month && $month !== 'all') {
            $attQuery->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$month]);
        }

        $absenceCount = $attQuery->count();

        if ($absenceCount < $threshold) {
            return back()->with('error', "Student has {$absenceCount} absents in this subject, which is below the required policy threshold of {$threshold} to strike off.");
        }

        // Check if already struck off
        $alreadyStruckOff = StudentStruckOffLog::where('student_id', $studentId)
            ->where('status', 'struck_off')
            ->exists();

        if ($alreadyStruckOff) {
            return back()->with('error', 'This student is already struck off. They must be readmitted before being struck off again.');
        }

        // Get active admission
        $admission = Admissions::where('student_id', $studentId)
            ->whereIn('status', ['Active', 'active', 'Admitted', 'admitted'])
            ->first();

        if (!$admission) {
            return back()->with('error', 'No active admission record found for this student.');
        }

        $recorderStaffId = $staffId ?? ($user->staff_id ?? 1);

        DB::transaction(function () use ($studentId, $admission, $recorderStaffId, $subjectId, $absenceCount, $request) {
            StudentStruckOffLog::create([
                'student_id'             => $studentId,
                'admission_id'           => $admission->id,
                'struck_off_by'          => $recorderStaffId,
                'subject_id'             => $subjectId,
                'absence_count_at_strike'=> $absenceCount,
                'struck_off_at'          => now(),
                'struck_off_reason'      => $request->reason,
                'academic_year'          => $admission->academic_year ?? date('Y'),
                'status'                 => 'struck_off',
            ]);

            $admission->update(['status' => 'Struck Off']);
        });

        $student = Student::find($studentId);
        return back()->with('success', "Student {$student->full_name} has been struck off successfully.");
    }

    // -------------------------------------------------------
    // ADMIN / CLERK / PRINCIPAL / TEACHER: list struck-off students
    // -------------------------------------------------------
    public function index(Request $request)
    {
        $settings   = StruckOffSetting::getSettings();
        $user       = Auth::user();
        $activeRole = session('active_role');

        // Check if user is operating under the teacher role
        $isTeacher = ($activeRole === 'teacher') || (!$user->is_super_admin && !$user->hasAnyRole(['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'super_admin']));

        $query = StudentStruckOffLog::with([
                'student',
                'struckOffBy',         // Staff (teacher)
                'subject',
                'readmittedBy',        // User (admin/clerk)
                'admission',
            ])
            ->orderByDesc('struck_off_at');

        // Teachers can ONLY see students whom they personally struck off
        if ($isTeacher) {
            $staffId = $user->staff?->id ?? ($user->staff_id ?? 0);
            $query->where('struck_off_by', $staffId);
        }

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

        $yearsQuery = StudentStruckOffLog::select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year');

        if ($isTeacher) {
            $staffId = $user->staff?->id ?? ($user->staff_id ?? 0);
            $yearsQuery->where('struck_off_by', $staffId);
        }

        $availableYears = $yearsQuery->pluck('academic_year');

        return view('struck-off.index', compact('logs', 'statusFilter', 'availableYears', 'settings', 'isTeacher'));
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

        $settings = StruckOffSetting::getSettings();
        $maxAllowed = $settings->max_readmissions_per_year;

        // Check how many times readmitted in this academic year
        $readmissionCount = StudentStruckOffLog::where('student_id', $log->student_id)
            ->where('academic_year', $log->academic_year)
            ->where('status', 'readmitted')
            ->count();

        if ($readmissionCount >= $maxAllowed) {
            return back()->with('error', "This student has already been readmitted {$readmissionCount} times this academic year. Maximum {$maxAllowed} readmissions per year are allowed according to college policy.");
        }

        DB::transaction(function () use ($log, $request) {
            $log->update([
                'status'           => 'readmitted',
                'readmitted_by'    => Auth::id(),
                'readmitted_at'    => now(),
                'readmission_note' => $request->readmission_note,
            ]);

            Admissions::where('id', $log->admission_id)
                ->update(['status' => 'Active']);
        });

        $student = $log->student;
        return back()->with('success', "Student {$student->full_name} has been readmitted successfully (Readmission #".($readmissionCount + 1)." of max {$maxAllowed}).");
    }

    // -------------------------------------------------------
    // ADMIN / PRINCIPAL: update struck-off rules and limits
    // -------------------------------------------------------
    public function updateSettings(Request $request)
    {
        $request->validate([
            'absent_threshold'          => 'required|integer|min:1|max:100',
            'max_readmissions_per_year' => 'required|integer|min:1|max:10',
            'calculation_period'        => 'required|in:monthly,session',
        ]);

        $settings = StruckOffSetting::getSettings();
        $settings->update([
            'absent_threshold'          => $request->absent_threshold,
            'max_readmissions_per_year' => $request->max_readmissions_per_year,
            'calculation_period'        => $request->calculation_period,
        ]);

        return back()->with('success', "Struck-off rules updated successfully: Default threshold is {$request->absent_threshold} absents, max readmissions is {$request->max_readmissions_per_year} per year.");
    }

    // -------------------------------------------------------
    // View full history for a single student
    // -------------------------------------------------------
    public function history(Student $student)
    {
        $settings = StruckOffSetting::getSettings();

        $logs = StudentStruckOffLog::where('student_id', $student->id)
            ->with(['struckOffBy', 'subject', 'readmittedBy'])
            ->orderByDesc('struck_off_at')
            ->get();

        $totalStruckOff       = $logs->count();
        $currentYear          = date('Y');
        $struckOffThisYear    = $logs->where('academic_year', $currentYear)->count();
        $readmissionsThisYear = $logs->where('academic_year', $currentYear)->where('status', 'readmitted')->count();

        return view('struck-off.history', compact(
            'student', 'logs', 'totalStruckOff', 'struckOffThisYear', 'readmissionsThisYear', 'settings'
        ));
    }
}
