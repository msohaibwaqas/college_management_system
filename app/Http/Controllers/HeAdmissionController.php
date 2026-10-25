<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HeAdmission;
use App\Models\Student;
use App\Models\Program;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\BiseRecord;
use App\Services\HeAdmissionService;
use App\Services\FeeCalculationService;
use App\Http\Requests\HeAdmission\StoreHeAdmissionRequest;
use App\Actions\Student\CreateHeStudentAction;
use App\Services\Student\StudentBulkOperationService;
use App\Services\Export\StudentExportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HeAdmissionController extends Controller
{
    public function __construct(
        protected HeAdmissionService $heAdmissionService,
        protected FeeCalculationService $feeService,
        protected StudentBulkOperationService $bulkOperationService,
        protected StudentExportService $exportService
    ) {}

    /**
     * Display a listing of HE admissions.
     */
    public function index(Request $request)
    {
        $data = $this->heAdmissionService->getIndexData($request);
        return view('he.admissions.index', $data);
    }

    /**
     * Show the form for creating a new HE admission.
     */
    public function create()
    {
        $bloodGroups = BloodGroup::all();
        $quotas = Quota::all();
        $bises = BiseRecord::all();
        $programs = Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();

        return view('he.admissions.create', compact('bloodGroups', 'quotas', 'bises', 'programs'));
    }

    /**
     * Store a newly created HE admission in storage.
     */
    public function store(StoreHeAdmissionRequest $request, CreateHeStudentAction $action)
    {
        try {
            $action->execute(
                $request->validated(),
                $request->file('picture'),
                Auth::id()
            );

            return redirect()->route('admissions.index')
                ->with('success', 'HE Admission completed successfully. Fee voucher has been generated.');
        } catch (\Exception $e) {
            Log::error('HE Admission creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified HE admission.
     */
    public function show(HeAdmission $admission)
    {
        $admission->load(['student', 'program', 'subjects.bsSubject']);
        return view('he.admissions.show', compact('admission'));
    }

    /**
     * Show the form for editing the specified HE admission.
     */
    public function edit(HeAdmission $admission)
    {
        $students = Student::whereHas('heAdmissions')
            ->orWhereHas('admissions', function ($q) {
                $q->whereIn('program_type', ['BS', 'ADP', 'DIT']);
            })
            ->orWhere('id', $admission->student_id)
            ->get();

        $programs = Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();
        return view('he.admissions.edit', compact('admission', 'students', 'programs'));
    }

    /**
     * Update the specified HE admission in storage.
     */
    public function update(Request $request, HeAdmission $admission)
    {
        $validated = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'program_id'       => 'required|exists:programs,id',
            'roll_no'          => 'nullable|string|max:50',
            'admission_year'   => 'required|integer|min:2000|max:2099',
            'current_semester' => 'required|integer|min:1|max:10',
            'status'           => 'required|in:Applied,Admitted,Active,Graduated,Dropped,Suspended',
        ]);

        $validated['updated_by'] = Auth::id();
        $admission->update($validated);

        return redirect()->route('admissions.index')->with('success', 'Admission updated successfully.');
    }

    /**
     * Remove the specified HE admission from storage.
     */
    public function destroy(HeAdmission $admission)
    {
        $admission->update(['deleted_by' => Auth::id()]);
        $admission->delete();

        return redirect()->route('admissions.index')->with('success', 'Admission deleted successfully.');
    }

    /**
     * Search former Intermediate student or HSSC records (AJAX).
     */
    public function searchStudent(Request $request)
    {
        $rollNo = $request->query('roll_no');
        if (!$rollNo) {
            return response()->json(['error' => 'Roll number is required'], 400);
        }

        $result = $this->heAdmissionService->searchCandidateByRollNo($rollNo);
        return response()->json($result);
    }

    /**
     * Export HE admissions to CSV.
     */
    public function export(Request $request)
    {
        $query = HeAdmission::with(['student.father', 'student.contact', 'program']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                  });
            });
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('admission_year')) {
            $query->where('admission_year', $request->admission_year);
        }

        if ($request->filled('semester')) {
            $query->where('current_semester', $request->semester);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->boolean('show_graduated')) {
            $query->where('status', '!=', 'Graduated');
        }

        return $this->exportService->exportHe($query->orderBy('roll_no', 'asc')->get());
    }

    /**
     * Bulk delete HE admissions and students.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'admission_ids'   => 'nullable|array',
            'admission_ids.*' => 'integer|exists:he_admissions,id',
            'student_ids'     => 'nullable|array',
            'student_ids.*'   => 'integer|exists:students,id',
        ]);

        try {
            $deletedCount = $this->bulkOperationService->deleteHeAdmissions(
                $request->input('admission_ids', []),
                $request->input('student_ids', [])
            );

            return response()->json([
                'success'       => true,
                'message'       => "Successfully deleted {$deletedCount} HE admission(s).",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting admissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update status for HE admissions.
     */
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'admission_ids'   => 'nullable|array',
            'admission_ids.*' => 'integer|exists:he_admissions,id',
            'student_ids'     => 'nullable|array',
            'student_ids.*'   => 'integer|exists:students,id',
            'status'          => 'required|in:' . implode(',', \App\Enums\AdmissionStatus::values()),
        ]);

        try {
            $status = $request->status;
            $updatedCount = $this->bulkOperationService->updateStatus(
                $request->input('student_ids', []),
                $status,
                $request->input('admission_ids', []),
                'he',
                Auth::id()
            );

            return response()->json([
                'success'       => true,
                'message'       => "Successfully updated status to '{$status}' for {$updatedCount} student(s).",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk promote HE admissions to next or specific semester.
     */
    public function bulkPromote(Request $request)
    {
        $request->validate([
            'admission_ids'   => 'nullable|array',
            'admission_ids.*' => 'integer|exists:he_admissions,id',
            'student_ids'     => 'nullable|array',
            'student_ids.*'   => 'integer|exists:students,id',
            'action'          => 'required|in:next_semester,specific_semester,graduate',
            'target_semester' => 'nullable|integer|min:1|max:8',
        ]);

        try {
            $result = $this->bulkOperationService->promoteHe(
                $request->input('admission_ids', []),
                $request->input('student_ids', []),
                $request->input('action', 'next_semester'),
                $request->input('target_semester'),
                Auth::id()
            );

            $msg = "Successfully processed {$result['total_processed']} student(s): {$result['promoted_count']} promoted";
            if ($result['graduated_count'] > 0) {
                $msg .= ", {$result['graduated_count']} graduated";
            }
            $msg .= ".";

            return response()->json([
                'success'         => true,
                'message'         => $msg,
                'promoted_count'  => $result['promoted_count'],
                'graduated_count' => $result['graduated_count'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error promoting students: ' . $e->getMessage()
            ], 500);
        }
    }
}
