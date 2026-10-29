<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentLeave\StoreStudentLeaveRequest;
use App\Http\Requests\StudentLeave\UpdateStudentLeaveRequest;
use App\Models\Student;
use App\Models\StudentLeave;
use App\Models\Attendance;
use App\Models\HeAttendance;
use App\Services\StudentLeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentLeaveController extends Controller
{
    public function __construct(
        protected StudentLeaveService $studentLeaveService
    ) {}

    /**
     * Display a listing of all student leaves with filters and statistics.
     */
    public function index(Request $request)
    {
        $query = StudentLeave::with([
            'student.admissions',
            'student.father',
            'markedBy'
        ])->orderByDesc('start_date');

        // Status filter
        $status = $request->query('status', 'approved');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search filter (roll no, student name, reason, approver)
        if ($request->filled('search')) {
            $query->search($request->query('search'));
        }

        // Class filter
        if ($request->filled('class')) {
            $class = $request->query('class');
            $query->whereHas('student.admissions', function ($q) use ($class) {
                $q->where('current_class', $class);
            });
        }

        // Date range filter
        $dateFilter = $request->query('date_filter');
        $today = now()->toDateString();

        if ($dateFilter === 'today') {
            $query->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today);
        } elseif ($dateFilter === 'this_week') {
            $startOfWeek = now()->startOfWeek()->toDateString();
            $endOfWeek = now()->endOfWeek()->toDateString();
            $query->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('start_date', [$startOfWeek, $endOfWeek])
                  ->orWhereBetween('end_date', [$startOfWeek, $endOfWeek]);
            });
        } elseif ($dateFilter === 'this_month') {
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();
            $query->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth]);
            });
        } elseif ($dateFilter === 'upcoming') {
            $query->whereDate('start_date', '>', $today);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('start_date', '<=', $request->end_date)
                  ->whereDate('end_date', '>=', $request->start_date);
            });
        }

        $perPage = (int) $request->get('per_page', 20);
        if ($perPage < 1) $perPage = 20;

        $leaves = $query->paginate($perPage)->withQueryString();
        $stats = $this->studentLeaveService->getLeaveStats();

        return view('student_leaves.index', compact('leaves', 'stats', 'status', 'dateFilter'));
    }

    /**
     * Show form to mark a new student leave.
     */
    public function create(Request $request)
    {
        $selectedStudent = null;
        if ($request->filled('student_id')) {
            $selectedStudent = Student::with(['admissions', 'father'])->find($request->student_id);
        }

        return view('student_leaves.create', compact('selectedStudent'));
    }

    /**
     * Store a newly created student leave and synchronize attendance.
     */
    public function store(StoreStudentLeaveRequest $request)
    {
        try {
            $leave = $this->studentLeaveService->createLeave(
                $request->validated(),
                $request->file('document'),
                Auth::id()
            );

            $studentName = $leave->student ? $leave->student->full_name : "Student";
            $message = "Leave successfully marked for {$studentName} ({$leave->formatted_date_range}). All subject attendance records have been synchronized to Leave status, and the student is protected from being struck off.";

            if ($request->filled('redirect_to_student') && $leave->student_id) {
                return redirect()->route('students.show', $leave->student_id)->with('success', $message);
            }

            return redirect()->route('student-leaves.index')->with('success', $message);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to mark student leave: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified student leave details and impacted attendance records.
     */
    public function show(StudentLeave $studentLeave)
    {
        $studentLeave->load([
            'student.admissions',
            'student.father',
            'student.contact',
            'markedBy'
        ]);

        $attendanceRecords = Attendance::with(['subject', 'staff'])
            ->where('student_id', $studentLeave->student_id)
            ->whereBetween('attendance_date', [$studentLeave->start_date, $studentLeave->end_date])
            ->orderBy('attendance_date')
            ->get();

        $heAttendanceRecords = collect();
        if ($studentLeave->student && $studentLeave->student->heAdmissions()->exists()) {
            $heAdmissionIds = $studentLeave->student->heAdmissions()->pluck('id');
            $heAttendanceRecords = HeAttendance::with(['bsSubject', 'staff'])
                ->whereIn('he_admission_id', $heAdmissionIds)
                ->whereBetween('attendance_date', [$studentLeave->start_date, $studentLeave->end_date])
                ->orderBy('attendance_date')
                ->get();
        }

        return view('student_leaves.show', compact('studentLeave', 'attendanceRecords', 'heAttendanceRecords'));
    }

    /**
     * Show form to edit an existing student leave.
     */
    public function edit(StudentLeave $studentLeave)
    {
        $studentLeave->load(['student.admissions', 'student.father']);
        return view('student_leaves.edit', compact('studentLeave'));
    }

    /**
     * Update an existing student leave and re-synchronize attendance.
     */
    public function update(UpdateStudentLeaveRequest $request, StudentLeave $studentLeave)
    {
        try {
            $leave = $this->studentLeaveService->updateLeave(
                $studentLeave,
                $request->validated(),
                $request->file('document')
            );

            return redirect()->route('student-leaves.show', $leave)
                ->with('success', 'Student leave details updated and attendance re-synchronized successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update leave: ' . $e->getMessage());
        }
    }

    /**
     * Cancel an approved student leave.
     */
    public function destroy(Request $request, StudentLeave $studentLeave)
    {
        try {
            $cancelReason = $request->input('cancel_reason', 'Cancelled by administrative staff');
            $this->studentLeaveService->cancelLeave($studentLeave, $cancelReason);

            return redirect()->route('student-leaves.index')
                ->with('success', 'Student leave has been cancelled.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel student leave: ' . $e->getMessage());
        }
    }

    /**
     * AJAX search endpoint for finding students to mark leave.
     */
    public function searchStudents(Request $request)
    {
        $term = trim($request->query('q', ''));
        if (strlen($term) < 1) {
            return response()->json(['results' => []]);
        }

        $students = Student::has('admissions')
            ->search($term)
            ->with(['admissions', 'father'])
            ->limit(20)
            ->get();

        $results = $students->map(function ($s) {
            $adm = $s->admissions->first();
            $roll = $adm?->college_roll_no ?? 'No Roll';
            $class = $adm?->current_class ? "Class {$adm->current_class}th" : '';
            $father = $s->father?->name ? "S/O {$s->father->name}" : '';
            
            $text = "Roll #{$roll} - {$s->full_name}";
            if ($class) $text .= " ({$class})";
            if ($father) $text .= " [{$father}]";

            return [
                'id'         => $s->id,
                'text'       => $text,
                'roll_no'    => $roll,
                'name'       => $s->full_name,
                'class'      => $class,
                'father_name'=> $s->father?->name ?? '',
            ];
        });

        return response()->json(['results' => $results]);
    }
}
