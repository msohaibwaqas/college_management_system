<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\StudentTeacherSubject;
use App\Models\Subject;
use App\Models\Admissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('teacher')) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $staff = $user->staff;
        if (!$staff) {
            return redirect()->route('dashboard')->with('error', 'No staff record found.');
        }

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

        return view('attendance.index', compact('myClasses'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        $subjectId = $request->query('subject_id');
        $class = $request->query('class');
        $year = $request->query('year');
        $date = $request->query('date', now()->toDateString());
        $section = $request->query('section');

        if (!$subjectId || !$class || !$year) {
            return redirect()->route('attendance.index')->with('error', 'Invalid parameters.');
        }

        // Teacher Date Restrictions
        if ($user->hasRole('teacher') && !$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            $today = date('Y-m-d');
            $isPastOrSunday = ($date !== $today) || (date('N', strtotime($date)) == 7);
            $isPastCutoff = (date('H') >= 23);

            if ($isPastOrSunday || $isPastCutoff) {
                $staffId = $staff->id ?? null;
                $hasReminder = $this->hasValidUnusedReminder($staffId, $subjectId, $class, $year, $date, $section);
                if (!$hasReminder) {
                    if ($date !== $today) {
                        return redirect()->route('attendance.index')->with('error', 'Teachers can only mark attendance for the current date unless granted permission by the Principal via a reminder. If you received a reminder, it may have already been used.');
                    }
                    if (date('N', strtotime($date)) == 7) {
                        return redirect()->route('attendance.index')->with('error', 'Attendance cannot be marked on Sundays.');
                    }
                    if (date('H') >= 23) {
                        return redirect()->route('attendance.index')->with('error', 'Attendance marking is closed after 11:00 PM.');
                    }
                }
            }
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

        // Add full_name attribute and compute recent attendance history for status indicators
        $studentIds = $students->pluck('id');
        $recentAttendances = Attendance::whereIn('student_id', $studentIds)
            ->where('subject_id', $subjectId)
            ->where('attendance_date', '<', $date)
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            $student->full_name = trim($student->first_name . ' ' . $student->last_name);

            $history = $recentAttendances->get($student->id, collect());
            $consecutiveAbsents = 0;
            $lastStatus = null;

            if ($history->isNotEmpty()) {
                $lastRecord = $history->first();
                $lastStatus = strtolower($lastRecord->status);

                if ($lastStatus === 'absent') {
                    foreach ($history as $record) {
                        if (strtolower($record->status) === 'absent') {
                            $consecutiveAbsents++;
                        } else {
                            break;
                        }
                    }
                }
            }

            // Assign status label, badge style, and row highlight style based on recent attendance
            if ($lastStatus === 'present' || $lastStatus === 'late') {
                $student->recent_status_code = 'green';
                $student->recent_status_label = 'Present Last Class';
                $student->recent_badge_style = 'background-color: #198754 !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(25, 135, 84, 0.08);';
            } elseif ($consecutiveAbsents == 1) {
                $student->recent_status_code = 'yellow';
                $student->recent_status_label = '1 Day Absent';
                $student->recent_badge_style = 'background-color: #ffc107 !important; color: #212529 !important;';
                $student->row_bg_style = 'background-color: rgba(255, 193, 7, 0.15);';
            } elseif ($consecutiveAbsents == 2) {
                $student->recent_status_code = 'orange';
                $student->recent_status_label = '2 Consecutive Absents';
                $student->recent_badge_style = 'background-color: #fd7e14 !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(253, 126, 20, 0.15);';
            } elseif ($consecutiveAbsents == 3) {
                $student->recent_status_code = 'blue';
                $student->recent_status_label = '3 Consecutive Absents';
                $student->recent_badge_style = 'background-color: #0d6efd !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(13, 110, 253, 0.15);';
            } elseif ($consecutiveAbsents >= 4) {
                $student->recent_status_code = 'red';
                $student->recent_status_label = "{$consecutiveAbsents} Consecutive Absents";
                $student->recent_badge_style = 'background-color: #dc3545 !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(220, 53, 69, 0.18);';
            } elseif ($lastStatus === 'leave') {
                $student->recent_status_code = 'leave';
                $student->recent_status_label = 'On Leave Last Class';
                $student->recent_badge_style = 'background-color: #0dcaf0 !important; color: #000000 !important;';
                $student->row_bg_style = 'background-color: rgba(13, 202, 240, 0.1);';
            } else {
                $student->recent_status_code = 'none';
                $student->recent_status_label = 'No Record';
                $student->recent_badge_style = 'background-color: #6c757d !important; color: #ffffff !important;';
                $student->row_bg_style = '';
            }
        }
        
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

        return view('attendance.create', compact('students', 'subject', 'class', 'year', 'date', 'existingAttendance', 'availableSections', 'section'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'attendance_date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'in:present,absent,leave,late',
        ]);

        $user = Auth::user();
        $staff = $user->staff;
        $subjectId = $request->subject_id;
        $date = $request->attendance_date;
        $class = $request->input('class') ?? $request->input('class_level');
        $year = $request->input('year') ?? $request->input('admission_year');
        $section = $request->input('section');

        // Teacher Date Restrictions
        if ($user->hasRole('teacher') && !$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            $today = date('Y-m-d');
            $isPastOrSunday = ($date !== $today) || (date('N', strtotime($date)) == 7);
            $isPastCutoff = (date('H') >= 23);

            if ($isPastOrSunday || $isPastCutoff) {
                $staffId = $staff->id ?? null;
                $hasReminder = \App\Models\AttendanceReminder::hasUnused($staffId, $subjectId, $date, $class, $year, $section);
                if (!$hasReminder) {
                    return redirect()->route('attendance.index')->with('error', 'Attendance marking for this date is restricted. If you received a reminder from the Principal, it may have already been used.');
                }
            }
        }

        // Optimization: Use upsert for bulk database operation instead of N+1 updateOrCreate calls
        $upsertData = [];
        $timestamp = now();

        foreach ($request->attendance as $studentId => $status) {
            $upsertData[] = [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'staff_id' => $staff->id,
                'attendance_date' => $date,
                'status' => $status,
                'remarks' => $request->remarks[$studentId] ?? null,
            ];
        }

        if (!empty($upsertData)) {
            Attendance::upsert(
                $upsertData,
                ['student_id', 'subject_id', 'attendance_date'], // Unique keys to check
                ['status', 'remarks', 'staff_id'] // Columns to update if exists
            );
        }

        // Mark matching reminder as used (one-time exception consumed)
        $staffId = $staff->id ?? null;
        if ($staffId) {
            \App\Models\AttendanceReminder::consume($staffId, $subjectId, $date, $class, $year, $section);
        }

        return redirect()->route('attendance.index')->with('success', 'Attendance marked successfully.');
    }
}
