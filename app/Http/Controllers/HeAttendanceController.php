<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\MarkHeAttendanceAction;
use App\Exceptions\AttendanceRestrictedException;
use App\Http\Requests\Attendance\MarkHeAttendanceRequest;
use App\Models\BsSubject;
use App\Models\HeAttendance;
use App\Models\HeTeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeAttendanceController extends Controller
{
    public function __construct(
        protected MarkHeAttendanceAction $markHeAttendanceAction
    ) {}

    /**
     * Display teacher's assigned BS/ADP classes
     */
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
        if (!$user || !$user->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'Access denied.');
        }

        $staff = $user->staff;
        if (!$staff) {
            return redirect()->route('dashboard')->with('error', 'No staff record found.');
        }

        $subjectId = $request->query('subject_id');
        $programId = $request->query('program_id');
        $semester = $request->query('semester');
        $year = $request->query('year');
        $date = $request->query('date', date('Y-m-d'));

        if (!$subjectId || !$programId || !$semester || !$year) {
            return redirect()->route('he.attendance.index')->with('error', 'Invalid parameters.');
        }

        // Validate teacher date, day, and time restrictions
        $restrictionError = $this->markHeAttendanceAction->checkRestrictions($user, $date);
        if ($restrictionError !== null) {
            return redirect()->route('he.attendance.index')->with('error', $restrictionError);
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
    public function store(MarkHeAttendanceRequest $request)
    {
        $user = Auth::user();

        try {
            $this->markHeAttendanceAction->execute($user, $request->validated());
            return redirect()->route('he.attendance.index')
                ->with('success', 'Attendance marked successfully.');
        } catch (AttendanceRestrictedException $e) {
            return redirect()->route('he.attendance.index')
                ->with('error', $e->getMessage());
        }
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
        if ($user && $user->isTeacher() && !$user->isAdministrativeStaff()) {
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
