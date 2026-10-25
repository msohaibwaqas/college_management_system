<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StudentStruckOffLog;
use App\Models\Student;
use App\Services\StruckOffService;
use App\Http\Requests\StruckOff\StoreStruckOffRequest;
use App\Http\Requests\StruckOff\ReadmitStudentRequest;
use App\Http\Requests\StruckOff\UpdateStruckOffSettingsRequest;

class StruckOffController extends Controller
{
    public function __construct(
        protected StruckOffService $struckOffService
    ) {}

    /**
     * View students eligible to be struck off (Teacher & Admin).
     */
    public function teacherPanel(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;
        $staffId = $staff ? $staff->id : null;

        $data = $this->struckOffService->getTeacherPanelData($request, $staffId, $staff);

        return view('struck-off.teacher', $data);
    }

    /**
     * Strike off an eligible student.
     */
    public function store(StoreStruckOffRequest $request)
    {
        $user = Auth::user();
        $staff = $user->staff;
        $staffId = $staff ? $staff->id : null;
        $studentId = (int) $request->validated('student_id');
        $subjectId = (int) $request->validated('subject_id');

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

        $recorderStaffId = $staffId ?? ($user->staff_id ?? 1);

        try {
            $this->struckOffService->strikeOff(
                $studentId,
                $subjectId,
                $recorderStaffId,
                $request->validated('reason'),
                $request->validated('month')
            );

            $student = Student::find($studentId);
            return back()->with('success', "Student {$student->full_name} has been struck off successfully.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * List struck-off students.
     */
    public function index(Request $request)
    {
        $data = $this->struckOffService->getIndexData($request, Auth::user());
        return view('struck-off.index', $data);
    }

    /**
     * Readmit a single struck-off student.
     */
    public function readmit(ReadmitStudentRequest $request, StudentStruckOffLog $log)
    {
        try {
            $this->struckOffService->readmit(
                $log,
                Auth::id(),
                $request->validated('readmission_note')
            );

            $student = $log->student;
            $readmissionCount = $log->getReadmissionsCountForYear();
            $maxAllowed = $this->struckOffService->getSettings()->max_readmissions_per_year;
            return back()->with('success', "Student {$student->full_name} has been readmitted successfully (Readmission #{$readmissionCount} of max {$maxAllowed}).");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk readmit struck-off students.
     */
    public function bulkReadmit(Request $request)
    {
        $note = $request->input('readmission_note', 'Bulk readmitted as per policy reset');
        try {
            $count = $this->struckOffService->bulkReadmit(Auth::id(), $note);
            return back()->with('success', "{$count} students have been successfully readmitted.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update struck-off rules and limits.
     */
    public function updateSettings(UpdateStruckOffSettingsRequest $request)
    {
        $settings = $this->struckOffService->updateSettings(
            (int) $request->validated('absent_threshold'),
            (int) $request->validated('max_readmissions_per_year'),
            $request->validated('calculation_period')
        );

        return back()->with('success', "Struck-off rules updated successfully: Default threshold is {$settings->absent_threshold} absents, max readmissions is {$settings->max_readmissions_per_year} per year.");
    }

    /**
     * View full history for a single student.
     */
    public function history(Student $student)
    {
        $data = $this->struckOffService->getStudentHistoryData($student);
        return view('struck-off.history', $data);
    }
}
