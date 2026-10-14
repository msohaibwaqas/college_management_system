<?php

namespace App\Http\Controllers;

use App\Models\HeAttendance;
use App\Models\HeTeacherAssignment;
use App\Models\HeAdmission;
use App\Models\BsSubject;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeAttendanceController extends Controller
{
    /**
     * Display teacher's assigned BS/ADP classes
     */
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

        // Get the teacher's assigned BS/ADP classes
        $myClasses = DB::table('he_teacher_assignments')
            ->join('bs_subjects', 'he_teacher_assignments.bs_subject_id', '=', 'bs_subjects.id')
            ->join('he_admissions', 'he_teacher_assignments.he_admission_id', '=', 'he_admissions.id')
            ->join('programs', 'he_admissions.program_id', '=', 'programs.id')
            ->where('he_teacher_assignments.staff_id', $staff->id)
            ->whereIn('he_admissions.status', ['Active', 'Admitted'])
            ->select(
                'bs_subjects.id as subject_id',
                'bs_subjects.name as subject_name',
                'bs_subjects.code as subject_code',
                'programs.id as program_id',
                'programs.name as program_name',
                'he_admissions.current_semester',
                'he_admissions.admission_year',
                DB::raw('count(distinct he_teacher_assignments.he_admission_id) as student_count')
            )
            ->groupBy(
                'bs_subjects.id', 
                'bs_subjects.name', 
                'bs_subjects.code',
                'programs.id', 
                'programs.name', 
                'he_admissions.current_semester',
                'he_admissions.admission_year'
            )
            ->orderBy('programs.name')
            ->orderBy('he_admissions.current_semester')
            ->get();

        return view('he.attendance.index', compact('myClasses'));
    }

    /**
     * Show attendance marking form
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;

        $subjectId = $request->query('subject_id');
        $programId = $request->query('program_id');
        $semester = $request->query('semester');
        $year = $request->query('year');
        $date = $request->query('date', date('Y-m-d'));

        if (!$subjectId || !$programId || !$semester || !$year) {
            return redirect()->route('he.attendance.index')->with('error', 'Invalid parameters.');
        }

        // Teacher Date Restrictions
        // Teacher Date Restrictions (Bypassed by Admin/Principal)
        if ($user->hasRole('teacher') && !$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            $today = date('Y-m-d');
            if ($date !== $today) {
                return redirect()->route('he.attendance.index')->with('error', 'Teachers can only mark attendance for the current date.');
            }
            if (date('N', strtotime($date)) == 7) {
                return redirect()->route('he.attendance.index')->with('error', 'Attendance cannot be marked on Sundays.');
            // 11:00 PM Cutoff
            if (date("H") >= 23) {
                return redirect()->route("he.attendance.index")->with("error", "Attendance marking is closed after 11:00 PM.");
            }
            }
        }

        $subject = BsSubject::find($subjectId);

        // Fetch students assigned to this teacher for this subject
        $assignments = HeTeacherAssignment::with(['heAdmission.student'])
            ->where('staff_id', $staff->id)
            ->where('bs_subject_id', $subjectId)
            ->whereHas('heAdmission', function ($query) use ($programId, $semester, $year) {
                $query->where('program_id', $programId)
                    ->where('current_semester', '>=', $semester)
                    ->where('admission_year', $year)
                    ->whereIn('status', ['Active', 'Admitted']);
            })
            ->get();

        $students = $assignments->map(function ($assignment) {
            $admission = $assignment->heAdmission;
            return (object)[
                'he_admission_id' => $admission->id,
                'roll_no' => $admission->roll_no,
                'first_name' => $admission->student->first_name ?? '',
                'last_name' => $admission->student->last_name ?? '',
            ];
        });

        // Check if attendance already exists for this date
        $existingAttendance = HeAttendance::where('staff_id', $staff->id)
            ->where('bs_subject_id', $subjectId)
            ->where('attendance_date', $date)
            ->whereIn('he_admission_id', $students->pluck('he_admission_id'))
            ->get()
            ->keyBy('he_admission_id');

        return view('he.attendance.create', compact(
            'students', 'subject', 'programId', 'semester', 'year', 'date', 'existingAttendance'
        ));
    }

    /**
     * Store attendance records
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:bs_subjects,id',
            'attendance_date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'in:present,absent,leave,late',
        ]);

        $user = Auth::user();
        $staff = $user->staff;
        $subjectId = $request->subject_id;
        $date = $request->attendance_date;

        // Teacher Date Restrictions
        // Teacher Date Restrictions (Bypassed by Admin/Principal)
        if ($user->hasRole('teacher') && !$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            $today = date('Y-m-d');
            if ($date !== $today) {
                return redirect()->route('he.attendance.index')->with('error', 'Teachers can only mark attendance for the current date.');
            }
            if (date('N', strtotime($date)) == 7) {
                return redirect()->route('he.attendance.index')->with('error', 'Attendance cannot be marked on Sundays.');
            }
            // 11:00 PM Cutoff
            if (date("H") >= 23) {
                return redirect()->route("he.attendance.index")->with("error", "Attendance marking is closed after 11:00 PM.");
            }
        }

        foreach ($request->attendance as $heAdmissionId => $status) {
            HeAttendance::updateOrCreate(
                [
                    'he_admission_id' => $heAdmissionId,
                    'bs_subject_id' => $subjectId,
                    'attendance_date' => $date,
                ],
                [
                    'staff_id' => $staff->id,
                    'status' => $status,
                    'remarks' => $request->remarks[$heAdmissionId] ?? null,
                ]
            );
        }

        return redirect()->route('he.attendance.index')
            ->with('success', 'Attendance marked successfully.');
    }

    /**
     * View attendance report
     */
    public function report(Request $request)
    {
        $programId = $request->program_id;
        $subjectId = $request->subject_id;
        $semester = $request->semester;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = HeAttendance::with(['heAdmission.student', 'bsSubject', 'staff']);

        $user = auth()->user();
        if ($user->hasRole('teacher') && !$user->is_super_admin && !$user->hasRole('admin')) {
            $query->where('staff_id', ($user->staff->id ?? 0));
        }

        if ($subjectId) {
            $query->where('bs_subject_id', $subjectId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('attendance_date', [$startDate, $endDate]);
        }

        if ($programId) {
            $query->whereHas('heAdmission', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(50);

        return view('he.attendance.report', compact('attendances'));
    }
}
