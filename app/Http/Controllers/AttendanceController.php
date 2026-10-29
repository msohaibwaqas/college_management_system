<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\MarkAttendanceAction;
use App\Exceptions\AttendanceRestrictedException;
use App\Http\Requests\Attendance\MarkAttendanceRequest;
use App\Models\Attendance;
use App\Models\Subject;
use App\Services\AcademicCalendarService;
use App\Services\AttendanceCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceCalculatorService $attendanceCalculatorService,
        protected MarkAttendanceAction $markAttendanceAction,
        protected AcademicCalendarService $academicCalendarService
    ) {}

    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $staff = $user->staff;
        if (!$staff) {
            return redirect()->route('dashboard')->with('error', 'No staff record found.');
        }

        $today = now()->toDateString();
        [$isTodayOff, $todayOffReason] = $this->academicCalendarService->checkIfSundayOrHoliday($today);

        $myClasses = DB::table('student_teacher_subjects')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->leftJoin('student_subject_sections', function($join) {
                $join->on('students.id', '=', 'student_subject_sections.student_id')
                     ->on('student_teacher_subjects.subject_id', '=', 'student_subject_sections.subject_id')
                     ->on('admissions.admission_year', '=', 'student_subject_sections.admission_year')
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->where('student_teacher_subjects.staff_id', $staff->id)
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNotIn('admissions.status', ['Struck Off', 'struck_off'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereNull('students.deleted_at')
            ->select(
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'admissions.current_class as current_class',
                'admissions.admission_year as admission_year',
                DB::raw('COUNT(DISTINCT student_teacher_subjects.student_id) as student_count'),
                DB::raw('COUNT(DISTINCT student_subject_sections.section) as section_count')
            )
            ->groupBy(
                'subjects.id',
                'subjects.name',
                'admissions.current_class',
                'admissions.admission_year'
            )
            ->orderBy('admissions.admission_year', 'desc')
            ->get();

        return view('attendance.index', compact('myClasses', 'isTodayOff', 'todayOffReason'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $staff = $user->staff;
        if (!$staff) {
            return redirect()->route('dashboard')->with('error', 'No staff record found.');
        }
        
        $subjectId = $request->query('subject_id');
        $class = $request->query('class');
        $year = $request->query('year');
        $date = $request->query('date', now()->toDateString());
        $section = $request->query('section');

        if (!$subjectId || !$class || !$year) {
            return redirect()->route('attendance.index')->with('error', 'Invalid parameters.');
        }

        // Validate teacher date, day, and time restrictions
        $restrictionError = $this->markAttendanceAction->checkRestrictions(
            $user,
            (int) $subjectId,
            $date,
            $class,
            $year ? (int) $year : null,
            $section
        );
        if ($restrictionError !== null) {
            return redirect()->route('attendance.index')->with('error', $restrictionError);
        }

        $subject = Subject::findOrFail($subjectId);

        // Fetch students with their corresponding assignment record + student_subject_sections from Student Affairs
        $studentsQuery = DB::table('student_teacher_subjects')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->leftJoin('fathers', 'students.id', '=', 'fathers.student_id')
            ->leftJoin('student_subject_sections', function($join) use ($subjectId, $year) {
                $join->on('students.id', '=', 'student_subject_sections.student_id')
                     ->where('student_subject_sections.subject_id', '=', $subjectId)
                     ->where('student_subject_sections.admission_year', '=', $year)
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->where('student_teacher_subjects.staff_id', $staff->id)
            ->where('student_teacher_subjects.subject_id', $subjectId)
            ->where('admissions.current_class', $class)
            ->where('admissions.admission_year', $year)
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNotIn('admissions.status', ['Struck Off', 'struck_off'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereNull('fathers.deleted_at')
            ->select(
                'students.id', 
                'students.first_name', 
                'students.last_name', 
                'fathers.name as father_name', 
                'admissions.college_roll_no as roll_number',
                'admissions.hssc_reg_no as hssc_reg_no',
                'student_subject_sections.section as student_affairs_section',
                'student_subject_sections.section as effective_section'
            );

        // Apply section filter if provided (strictly from Student Affairs section)
        if ($section) {
            if ($section === 'unassigned') {
                $studentsQuery->whereNull('student_subject_sections.section');
            } else {
                $studentsQuery->where('student_subject_sections.section', $section);
            }
        }
        
        $students = $studentsQuery->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED) ASC')->get();

        // Enrich student models with attendance history, badges, and row highlight styles
        $students = $this->attendanceCalculatorService->enrichStudentsWithRecentAttendance(
            $students,
            (int) $subjectId,
            $date
        );
        
        // Get all unique sections from Student Affairs sections
        $availableSections = DB::table('student_teacher_subjects')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->join('student_subject_sections', function($join) use ($subjectId, $year) {
                $join->on('student_teacher_subjects.student_id', '=', 'student_subject_sections.student_id')
                     ->where('student_subject_sections.subject_id', '=', $subjectId)
                     ->where('student_subject_sections.admission_year', '=', $year)
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->where('student_teacher_subjects.staff_id', $staff->id)
            ->where('student_teacher_subjects.subject_id', $subjectId)
            ->where('admissions.current_class', $class)
            ->where('admissions.admission_year', $year)
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNotNull('student_subject_sections.section')
            ->pluck('student_subject_sections.section')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Check if attendance already exists for this date
        $existingAttendance = Attendance::where('staff_id', $staff->id)
            ->where('subject_id', $subjectId)
            ->where('attendance_date', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        // Check active student leaves for this date
        $activeLeaves = \App\Models\StudentLeave::approved()
            ->whereIn('student_id', $students->pluck('id'))
            ->forDate($date)
            ->get()
            ->keyBy('student_id');

        return view('attendance.create', compact('students', 'subject', 'class', 'year', 'date', 'existingAttendance', 'availableSections', 'section', 'activeLeaves'));
    }

    public function store(MarkAttendanceRequest $request)
    {
        $user = Auth::user();

        try {
            $this->markAttendanceAction->execute($user, $request->validated());
            return redirect()->route('attendance.index')->with('success', 'Attendance marked successfully.');
        } catch (AttendanceRestrictedException $e) {
            return redirect()->route('attendance.index')->with('error', $e->getMessage());
        }
    }
}
