<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Admissions;
use App\Models\FeeVoucher;
use App\Models\InstituteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\FileUploadService;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Actions\Student\CreateStudentAction;
use App\Actions\Student\UpdateStudentAction;
use App\Services\Student\StudentBulkOperationService;
use App\Services\Student\StudentSubjectService;
use App\Services\Student\StudentFormService;
use App\Services\Export\StudentExportService;

class StudentController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService,
        protected StudentBulkOperationService $bulkOperationService,
        protected StudentExportService $exportService,
        protected StudentSubjectService $subjectService,
        protected StudentFormService $formService
    ) {}

    /**
     * List all students
     */
    public function index(Request $request)
    {
        $query = Student::has('admissions')->with([
            'admissions' => function($q) {
                $q->select('id', 'student_id', 'college_roll_no', 'bise_roll_11', 'bise_roll_12', 'admission_year', 'status', 'program_type', 'program_name', 'shift', 'semester', 'current_class');
            },
            'father' => function($q) {
                $q->select('id', 'student_id', 'name');
            },
            'electives.subject' => function($q) {
                $q->select('id', 'name', 'code');
            }
        ]);

        $isTeacher = Auth::check() && Auth::user()->isTeacher();

        if ($isTeacher) {
            $staff = Auth::user()->staff;
            $query->assignedToTeacher($staff?->id);
        }

        if ($request->filled('admission_year')) {
            $query->inBatch((int) $request->admission_year);
        }

        if (!$request->filled('show_graduated') || $request->show_graduated != '1') {
            $query->whereNotGraduated();
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $sortBy = $request->get('sort_by', 'college_roll_no');
        $sortOrder = $request->get('sort_order', 'asc');

        try {
            if (in_array($sortBy, ['bise_roll_11', 'bise_roll_12', 'college_roll_no', 'admission_year'])) {
                $query->orderByRaw("(SELECT CAST({$sortBy} AS UNSIGNED) FROM admissions WHERE admissions.student_id = students.id LIMIT 1) {$sortOrder}");
            } elseif ($sortBy === 'status') {
                $query->join('admissions', 'students.id', '=', 'admissions.student_id')
                      ->orderBy('admissions.status', $sortOrder)
                      ->select('students.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } catch (\Exception $e) {
            Log::error('Sorting error: ' . $e->getMessage());
            $query->orderBy('id', 'asc');
        }

        $perPage = $request->get('per_page', 20);
        $students = $query->paginate($perPage)->appends($request->except('page'));
        $admissionYears = Admissions::distinctAdmissionYears();

        return view('students.index', compact('students', 'admissionYears'));
    }

    /**
     * Show form to create a new student
     */
    public function create()
    {
        $refData = $this->formService->getCachedReferenceData();
        return view('students.create', $refData);
    }

    /**
     * Store new student
     */
    public function store(StoreStudentRequest $request, CreateStudentAction $createStudentAction)
    {
        try {
            $createStudentAction->execute(
                $request->validated(),
                $request->file('picture'),
                Auth::id()
            );

            return redirect()->route('students.index')->with('success', 'Student added successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Student DB Exception in store: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $this->formService->formatDatabaseErrorMessage($e));
        } catch (\Exception $e) {
            Log::error('General Exception in store: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Student $student)
    {
        if (Auth::user()?->isTeacher()) {
            return redirect()->route('students.index')->with('error', 'Access denied.');
        }

        $redirectTo = $request->get('redirect_to', url()->previous());
        $data = $this->formService->prepareEditData($student, $redirectTo);

        return view('students.edit', $data);
    }

    /**
     * Update student
     */
    public function update(UpdateStudentRequest $request, Student $student, UpdateStudentAction $updateStudentAction)
    {
        if (Auth::user()?->isTeacher()) {
            return redirect()->route('students.index')->with('error', 'Access denied.');
        }

        try {
            $updateStudentAction->execute(
                $student,
                $request->validated(),
                $request->file('picture'),
                Auth::id()
            );

            $redirectTo = $request->input('redirect_to');
            if (!empty($redirectTo) && (str_contains($redirectTo, '/students') || str_contains($redirectTo, 'students'))) {
                return redirect($redirectTo)->with('success', 'Student updated successfully!');
            }

            return redirect()->route('students.index')->with('success', 'Student updated successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Student DB Exception in update: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $this->formService->formatDatabaseErrorMessage($e));
        } catch (\Exception $e) {
            Log::error('Student update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'An error occurred while updating the student. Please try again.');
        }
    }

    /**
     * Delete student (Soft Delete)
     */
    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Student moved to trash successfully!');
    }

    /**
     * View deleted students (Trash)
     */
    public function trash(Request $request)
    {
        $query = Student::onlyTrashed()
            ->with(['admissions', 'father'])
            ->orderBy('deleted_at', 'desc');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $perPage = (int) $request->get('per_page', 50);
        if ($perPage < 1) $perPage = 50;

        $students = $query->paginate($perPage)->withQueryString();
            
        return view('students.trash', compact('students'));
    }

    /**
     * Restore deleted student
     */
    public function restore($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();
        
        return redirect()->route('students.trash')->with('success', 'Student restored successfully!');
    }

    /**
     * Permanently delete student
     */
    public function forceDelete($id)
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        
        if ($student->picture) {
            $this->fileUploadService->delete($student->picture);
        }
        $student->forceDelete();
        
        return redirect()->route('students.trash')->with('success', 'Student permanently deleted!');
    }

    /**
     * Empty all students from trash
     */
    public function emptyTrash()
    {
        if (!Auth::user()->hasRole('principal') && !Auth::user()->is_super_admin) {
            return redirect()->route('students.trash')->with('error', 'Unauthorized access.');
        }

        Student::onlyTrashed()->forceDelete();

        return redirect()->route('students.trash')->with('success', 'Recycle bin empty successfully!');
    }

    /**
     * Bulk delete students
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id'
        ]);

        try {
            $deletedCount = $this->bulkOperationService->deleteStudents($request->student_ids);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} student(s).",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update student status
     */
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'status' => 'required|in:Active,Struck Off,Graduated,Dropped,Suspended'
        ]);

        try {
            $updatedCount = $this->bulkOperationService->updateStatus(
                $request->student_ids,
                $request->status,
                [],
                'all',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully updated status for {$updatedCount} admission record(s).",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk promote students from Class 11 to Class 12
     */
    public function bulkPromote(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        try {
            $promotedCount = $this->bulkOperationService->promoteHssc($request->student_ids, Auth::id());

            return response()->json([
                'success' => true,
                'message' => "Successfully promoted {$promotedCount} student(s) to Class 12.",
                'promoted_count' => $promotedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while promoting students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export students to CSV
     */
    public function export(Request $request)
    {
        $query = Student::with([
            'admissions',
            'father',
            'electives.subject'
        ]);

        if ($request->filled('admission_year')) {
            $query->whereHas('admissions', function($q) use ($request) {
                $q->where('admission_year', $request->admission_year);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('admissions', function($subQ) use ($search) {
                      $subQ->where('college_roll_no', 'like', "%{$search}%")
                           ->orWhere('bise_roll_11', 'like', "%{$search}%")
                           ->orWhere('bise_roll_12', 'like', "%{$search}%");
                  });
            });
        }

        return $this->exportService->exportHssc($query->get());
    }

    /**
     * View single student details
     */
    public function show(Student $student)
    {
        $student->load([
            'admissions',
            'father',
            'contact',
            'attribute.bloodGroup',
            'attribute.quota',
            'sscRecord.bise',
            'compulsorySubjects.subject',
            'electives.subject',
            'studentTeacherSubjects.staff',
            'studentTeacherSubjects.subject',
            'results.subject',
            'leaves.markedBy'
        ]);

        return view('students.show', compact('student'));
    }

    /**
     * Students by subject - filter students by selected subject
     */
    public function studentsBySubject(Request $request)
    {
        $data = $this->subjectService->getStudentsBySubjectData(
            $request->input('admission_year'),
            $request->input('subject_id')
        );

        return view('students.by_subject', $data);
    }

    /**
     * Group students by their subject combinations
     */
    public function groupStudents(Request $request)
    {
        $data = $this->subjectService->getGroupedStudentsData($request->input('admission_year'));
        return view('students.groups', $data);
    }

    /**
     * Export grouped students as CSV
     */
    public function exportGroupsCSV($year)
    {
        return $this->subjectService->exportGroupsCSV($year);
    }

    /**
     * Show form to change subjects for an admitted student
     */
    public function changeSubjects(Student $student)
    {
        if (!$student->canChangeElectives()) {
            $latestAdmission = $student->admissions()->latest('admission_year')->first();
            if ($latestAdmission && $latestAdmission->current_class === '12') {
                return redirect()->route('students.show', $student)->with('error', 'Elective subjects cannot be changed for Class 12 students.');
            }
            return redirect()->route('students.show', $student)->with('error', 'You cannot change subjects because there is already a pending Subject Change fee voucher that must be paid first.');
        }

        $data = $this->subjectService->getChangeSubjectsData($student);
        return view('students.change_subjects', $data);
    }

    /**
     * Update subjects and handle fee difference
     */
    public function updateSubjects(Request $request, Student $student)
    {
        if (!$student->canChangeElectives()) {
            $latestAdmission = $student->admissions()->latest('admission_year')->first();
            if ($latestAdmission && $latestAdmission->current_class === '12') {
                return redirect()->route('students.show', $student)->with('error', 'Elective subjects cannot be changed for Class 12 students.');
            }
            return redirect()->route('students.show', $student)->with('error', 'You cannot change subjects because there is already a pending Subject Change fee voucher that must be paid first.');
        }

        $request->validate([
            'elective_subjects' => 'required|array|size:3',
            'elective_subjects.*' => 'required|exists:subjects,id',
        ]);

        try {
            $result = $this->subjectService->updateStudentSubjects(
                $student,
                $request->elective_subjects,
                Auth::id()
            );

            $redirect = redirect()->route('students.show', $student)->with('success', $result['message']);

            if (!empty($result['has_voucher']) && isset($result['voucher'])) {
                $redirect->with('voucher_id', $result['voucher']->id);
            }

            return $redirect;
        } catch (\Exception $e) {
            Log::error('Subject change failed: ' . $e->getMessage());
            return back()->with('error', 'Subject change failed: ' . $e->getMessage());
        }
    }

    /**
     * Download or print a fee voucher
     */
    public function downloadVoucher(FeeVoucher $voucher)
    {
        $voucher->load(['items', 'student.admissions', 'student.heAdmissions', 'application']);
        $settings = InstituteSetting::getSettings();
        
        return view('pdfs.fee-voucher', compact('voucher', 'settings'));
    }

    /**
     * Confirm payment for a student fee voucher
     */
    public function confirmVoucherPayment(Request $request, FeeVoucher $voucher)
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        try {
            $voucher->confirmPayment(
                (float) $request->paid_amount,
                $request->payment_date,
                Auth::id()
            );

            return back()->with('success', 'Payment for voucher ' . $voucher->voucher_number . ' confirmed successfully.');
        } catch (\Exception $e) {
            Log::error('Voucher payment confirmation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to confirm payment: ' . $e->getMessage());
        }
    }
}
