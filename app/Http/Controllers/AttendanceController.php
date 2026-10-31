<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\MarkAttendanceAction;
use App\Exceptions\AttendanceRestrictedException;
use App\Http\Requests\Attendance\MarkAttendanceRequest;
use App\Models\Attendance;
use App\Models\Datesheet;
use App\Models\Result;
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

        $todayDayOfWeek = now()->format('l');

        // Fetch active timetable entries for this staff member
        $staffTimetables = DB::table('timetable_entries')
            ->where('staff_id', $staff->id)
            ->where('is_active', 1)
            ->select('subject_id', 'admission_year', 'section', 'day_of_week')
            ->get();

        $myClasses->transform(function ($class) use ($staffTimetables, $todayDayOfWeek) {
            $subjectTt = $staffTimetables->where('subject_id', $class->subject_id)
                ->where('admission_year', $class->admission_year);

            if ($subjectTt->isEmpty()) {
                $class->has_timetable = false;
                $class->is_scheduled_today = true;
                $class->scheduled_today_sections = collect();
                $class->scheduled_days = collect();
            } else {
                $class->has_timetable = true;
                $scheduledToday = $subjectTt->where('day_of_week', $todayDayOfWeek);
                $class->is_scheduled_today = $scheduledToday->isNotEmpty();
                $class->scheduled_today_sections = $scheduledToday->pluck('section')->filter()->unique()->values();
                $class->scheduled_days = $subjectTt->pluck('day_of_week')->unique()->values();
            }

            return $class;
        });

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

        $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');

        // Look up sections scheduled for this teacher, subject, year, and day
        $todayTtSections = DB::table('timetable_entries')
            ->where('staff_id', $staff->id)
            ->where('subject_id', $subjectId)
            ->where('admission_year', $year)
            ->where('is_active', 1)
            ->where('day_of_week', $dayOfWeek)
            ->pluck('section')
            ->filter()
            ->map(fn($s) => trim($s))
            ->unique()
            ->values();

        // If timetable has section-specific entries today and no section was explicitly chosen, auto-select today's section
        if (empty($section) && $todayTtSections->isNotEmpty()) {
            if ($todayTtSections->count() === 1) {
                $section = $todayTtSections->first();
            }
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
            ->whereNotExists(function ($query) use ($date) {
                $query->select(DB::raw(1))
                    ->from('student_struck_off_logs')
                    ->whereColumn('student_struck_off_logs.student_id', 'students.id')
                    ->whereDate('student_struck_off_logs.struck_off_at', '<=', $date)
                    ->where(function ($sub) use ($date) {
                        $sub->whereNull('student_struck_off_logs.readmitted_at')
                            ->orWhereDate('student_struck_off_logs.readmitted_at', '>', $date);
                    });
            })
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
        } elseif ($todayTtSections->isNotEmpty()) {
            // When timetable defines sections for today and no single section was selected,
            // strictly load only students belonging to sections scheduled today
            $studentsQuery->whereIn('student_subject_sections.section', $todayTtSections);
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

        // Check if a test is scheduled on this date for this subject and admission year
        $scheduledTest = Datesheet::where('subject_id', $subjectId)
            ->where('admission_year', $year)
            ->whereDate('test_date', $date)
            ->first();

        $existingTestMarks = null;
        if ($scheduledTest) {
            $existingTestMarks = Result::where('academic_year', $year)
                ->where('class_level', $class)
                ->where('subject_id', $subjectId)
                ->where('exam_name', $scheduledTest->exam_name)
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('updated_by', $user->id);
                })
                ->whereNotNull('total_marks')
                ->value('total_marks')
                ?? Result::where('academic_year', $year)
                ->where('class_level', $class)
                ->where('subject_id', $subjectId)
                ->where('exam_name', $scheduledTest->exam_name)
                ->whereNotNull('total_marks')
                ->value('total_marks');
        }

        return view('attendance.create', compact(
            'students', 'subject', 'class', 'year', 'date', 
            'existingAttendance', 'availableSections', 'section', 
            'activeLeaves', 'scheduledTest', 'existingTestMarks', 'todayTtSections'
        ));
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
