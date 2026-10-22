<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HeAdmission;
use App\Models\Student;
use App\Models\Program;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\BiseRecord;
use App\Models\Father;
use App\Models\Contact;
use App\Models\SscRecord;
use App\Models\HsscRecord;
use App\Models\HeStudentSubject;
use App\Models\StudentAttribute;
use App\Models\Admissions;
use App\Models\AdmissionSetting;
use App\Services\FeeCalculationService;
use App\Http\Requests\HeAdmission\StoreHeAdmissionRequest;
use App\Actions\Student\CreateHeStudentAction;
use App\Services\Student\StudentBulkOperationService;
use App\Services\Export\StudentExportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class HeAdmissionController extends Controller
{
    public function __construct(
        protected FeeCalculationService $feeService,
        protected StudentBulkOperationService $bulkOperationService,
        protected StudentExportService $exportService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Build Query for HE Admissions
        $query = HeAdmission::with(['student.father', 'student.contact', 'program']);

        // Search (Name, Roll No)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                  });
            });
        }

        // Filter by Program
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by Admission Year
        if ($request->filled('admission_year')) {
            $query->where('admission_year', $request->admission_year);
        }

        // Filter by Status / Graduated
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->boolean('show_graduated')) {
            $query->where('status', '!=', 'Graduated');
        }

        // Filter by Semester
        if ($request->filled('semester')) {
            $query->where('current_semester', $request->semester);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'roll_no');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'name') {
             $query->leftJoin('students', 'he_admissions.student_id', '=', 'students.id')
                  ->orderBy('students.first_name', $sortOrder)
                  ->select('he_admissions.*');
        } elseif (in_array($sortBy, ['roll_no', 'admission_year', 'status', 'current_semester'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $heAdmissions = $query->paginate($perPage)->appends($request->all());

        $legacyQuery = Admissions::whereIn('program_type', ['BS', 'ADP', 'DIT'])
            ->with(['student.father', 'student.contact']);
            
        if ($request->filled('search')) {
             $search = $request->search;
             $legacyQuery->where(function($q) use ($search) {
                $q->where('college_roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $legacyAdmissions = $legacyQuery->limit(20)->get();

        // Get filter options
        $programs = Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();
        $admissionYears = HeAdmission::select('admission_year')->distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');

        return view('he.admissions.index', compact('heAdmissions', 'legacyAdmissions', 'programs', 'admissionYears'));
    }

    /**
     * Show the form for creating a new resource.
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
     * Store a newly created resource in storage.
     */
    public function store(StoreHeAdmissionRequest $request, CreateHeStudentAction $action)
    {
        try {
            $heAdmission = $action->execute(
                $request->validated(),
                $request->file('picture'),
                Auth::id()
            );

            return redirect()->route('admissions.index')
                ->with('success', 'HE Admission completed successfully. Fee voucher has been generated.');
        } catch (\Exception $e) {
            \Log::error('HE Admission creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\HeAdmission $admission)
    {
        $admission->load(['student', 'program', 'subjects.bsSubject']);
        return view('he.admissions.show', compact('admission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\HeAdmission $admission)
    {
        $students = \App\Models\Student::whereHas('heAdmissions')
            ->orWhereHas('admissions', function($q) {
                $q->whereIn('program_type', ['BS', 'ADP', 'DIT']);
            })
            ->orWhere('id', $admission->student_id)
            ->get();

        $programs = \App\Models\Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();
        return view('he.admissions.edit', compact('admission', 'students', 'programs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\HeAdmission $admission)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'program_id' => 'required|exists:programs,id',
            'roll_no' => 'nullable|string|max:50',
            'admission_year' => 'required|integer|min:2000|max:2099',
            'current_semester' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Applied,Admitted,Active,Graduated,Dropped,Suspended',
        ]);

        $validated['updated_by'] = auth()->id();
        $admission->update($validated);

        return redirect()->route('admissions.index')->with('success', 'Admission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\HeAdmission $admission)
    {
        $admission->update(['deleted_by' => auth()->id()]);
        $admission->delete();

        return redirect()->route('admissions.index')->with('success', 'Admission deleted successfully.');
    }

    public function searchStudent(Request $request)
    {
        $rollNo = $request->query('roll_no');
        if (!$rollNo) {
            return response()->json(['error' => 'Roll number is required'], 400);
        }

        $admission = Admissions::where(function (Builder $query) use ($rollNo) {
                $query->where('bise_roll_11', $rollNo)
                      ->orWhere('bise_roll_12', $rollNo);
            })
            ->with(['student.attribute', 'student.father', 'student.contact', 'student.sscRecord'])
            ->first();

        if ($admission && $admission->student) {
            $student = $admission->student;
            $student->load(['attribute', 'father', 'contact', 'sscRecord']);
            $hsscRecord = HsscRecord::where('student_id', $student->id)->first();
            
            return response()->json([
                'found' => true,
                'source' => 'Former Intermediate Student',
                'data' => $this->formatStudentData($student, $hsscRecord, $admission)
            ]);
        }

        $hsscRecord = HsscRecord::where('roll_no', $rollNo)
            ->with(['student.attribute', 'student.father', 'student.contact', 'student.sscRecord'])
            ->first();

        if ($hsscRecord && $hsscRecord->student) {
            $student = $hsscRecord->student;
            return response()->json([
                'found' => true,
                'source' => 'Existing Student Record',
                'data' => $this->formatStudentData($student, $hsscRecord)
            ]);
        }

        return response()->json(['found' => false]);
    }

    private function formatStudentData($student, $hsscRecord = null, $admission = null)
    {
        $settings = \App\Models\InstituteSetting::getSettings();
        
        if (!$hsscRecord && $admission) {
            $finalResults = \App\Models\Result::where('student_id', $student->id)->where('exam_type', 'Final')->get();
            $totalMarks = $finalResults->sum('total_marks');
            $obtainedMarks = $finalResults->sum(function($r) {
                return ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0);
            });

            return [
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'gender' => $student->gender,
                'date_of_birth' => $student->date_of_birth->format('Y-m-d'),
                'student_cnic' => $student->cnic,
                'blood_group_id' => $student->attribute->blood_group_id ?? null,
                'quota_id' => $student->attribute->quota_id ?? null,
                'religion' => $student->religion ?? 'Islam',
                'nationality' => $student->nationality ?? 'Pakistani',
                
                'father_name' => $student->father->name ?? '',
                'father_cnic' => $student->father->cnic ?? '',
                'father_contact' => $student->father->contact ?? '',
                'father_occupation' => $student->father->occupation ?? '',
                'father_annual_income' => $student->father->annual_income ?? '',
                
                'student_contact' => $student->contact->student_contact ?? '',
                'whatsapp_no' => $student->contact->whatsapp_no ?? '',
                'email' => $student->contact->email ?? '',
                'address' => $student->contact->address ?? '',
                
                'ssc_bise_id' => $student->sscRecord->bise_id ?? '',
                'ssc_roll_no' => $student->sscRecord->roll_no ?? '',
                'ssc_reg_no' => $student->sscRecord->reg_no ?? '',
                'ssc_school_name' => $student->sscRecord->school_name ?? '',
                'ssc_total_mark' => $student->sscRecord->total_mark ?? '',
                'ssc_obtained_mark' => $student->sscRecord->obtained_mark ?? '',
                'ssc_passing_year' => $student->sscRecord->passing_year ?? '',
                'ssc_passing_session' => $student->sscRecord->passing_session ?? '',
                'ssc_group' => $student->sscRecord->ssc_group ?? '',
                
                'hssc_bise_id' => $student->sscRecord->bise_id ?? '',
                'hssc_roll_no' => $admission->bise_roll_12 ?: $admission->bise_roll_11,
                'hssc_reg_no_academic' => $admission->hssc_reg_no ?? '',
                'hssc_college_name' => $settings->institute_title ?? 'This College',
                'hssc_total_mark' => $totalMarks ?: '',
                'hssc_obtained_mark' => $obtainedMarks ?: '',
                'hssc_passing_year' => $admission->academic_year ?? date('Y'),
                'hssc_passing_session' => '1st Annual',
                'hssc_group' => $admission->program_name ?? '',
            ];
        }

        return [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'gender' => $student->gender,
            'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '',
            'student_cnic' => $student->cnic,
            'blood_group_id' => $student->attribute->blood_group_id ?? null,
            'quota_id' => $student->attribute->quota_id ?? null,
            'religion' => $student->religion ?? 'Islam',
            'nationality' => $student->nationality ?? 'Pakistani',
            
            'father_name' => $student->father->name ?? '',
            'father_cnic' => $student->father->cnic ?? '',
            'father_contact' => $student->father->contact ?? '',
            'father_occupation' => $student->father->occupation ?? '',
            'father_annual_income' => $student->father->annual_income ?? '',
            
            'student_contact' => $student->contact->student_contact ?? '',
            'whatsapp_no' => $student->contact->whatsapp_no ?? '',
            'email' => $student->contact->email ?? '',
            'address' => $student->contact->address ?? '',
            
            'ssc_bise_id' => $student->sscRecord->bise_id ?? '',
            'ssc_roll_no' => $student->sscRecord->roll_no ?? '',
            'ssc_reg_no' => $student->sscRecord->reg_no ?? '',
            'ssc_school_name' => $student->sscRecord->school_name ?? '',
            'ssc_total_mark' => $student->sscRecord->total_mark ?? '',
            'ssc_obtained_mark' => $student->sscRecord->obtained_mark ?? '',
            'ssc_passing_year' => $student->sscRecord->passing_year ?? '',
            'ssc_passing_session' => $student->sscRecord->passing_session ?? '',
            'ssc_group' => $student->sscRecord->ssc_group ?? '',
            
            'hssc_bise_id' => $hsscRecord->bise_id ?? '',
            'hssc_roll_no' => $hsscRecord->roll_no ?? '',
            'hssc_reg_no_academic' => $hsscRecord->reg_no ?? '',
            'hssc_college_name' => $hsscRecord->college_name ?? '',
            'hssc_total_mark' => $hsscRecord->total_mark ?? '',
            'hssc_obtained_mark' => $hsscRecord->obtained_mark ?? '',
            'hssc_passing_year' => $hsscRecord->passing_year ?? '',
            'hssc_passing_session' => $hsscRecord->passing_session ?? '',
            'hssc_group' => $hsscRecord->hssc_group ?? '',
        ];
    }

    /**
     * Export HE admissions to CSV.
     */
    public function export(Request $request)
    {
        $query = HeAdmission::with(['student.father', 'student.contact', 'program']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function($subQ) use ($search) {
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
            'admission_ids' => 'nullable|array',
            'admission_ids.*' => 'integer|exists:he_admissions,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:students,id',
        ]);

        try {
            $deletedCount = $this->bulkOperationService->deleteHeAdmissions(
                $request->input('admission_ids', []),
                $request->input('student_ids', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} HE admission(s).",
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
            'admission_ids' => 'nullable|array',
            'admission_ids.*' => 'integer|exists:he_admissions,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:students,id',
            'status' => 'required|in:' . implode(',', \App\Enums\AdmissionStatus::values()),
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
                'success' => true,
                'message' => "Successfully updated status to '{$status}' for {$updatedCount} student(s).",
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
            'admission_ids' => 'nullable|array',
            'admission_ids.*' => 'integer|exists:he_admissions,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:students,id',
            'action' => 'required|in:next_semester,specific_semester,graduate',
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
                'success' => true,
                'message' => $msg,
                'promoted_count' => $result['promoted_count'],
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