<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Subject;
use App\Models\BloodGroup;
use App\Models\Designation;
use App\Models\BsSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FileUploadService;
use App\Services\StaffAssignmentService;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Actions\Staff\CreateStaffAction;
use App\Actions\Staff\UpdateStaffAction;

class StaffController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService,
        protected StaffAssignmentService $staffAssignmentService
    ) {}

    /**
     * Display a listing of staff
     */
    public function index(Request $request)
    {
        $query = Staff::with(['bloodGroup', 'user']);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('cnic', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $staff = $query->orderBy('first_name', 'asc')->paginate($perPage);

        return view('staff.index', compact('staff'));
    }

    /**
     * Show the form for creating new staff
     */
    public function create()
    {
        $bloodGroups = BloodGroup::all();
        $subjects = Subject::orderBy('name', 'asc')->get();
        $designations = Designation::orderBy('name', 'asc')->get();
        $bsSubjects = BsSubject::orderBy('name', 'asc')->get();
        
        return view('staff.create', compact('bloodGroups', 'subjects', 'designations', 'bsSubjects'));
    }

    /**
     * Store new staff
     */
    public function store(StoreStaffRequest $request, CreateStaffAction $createStaffAction)
    {
        $createStaffAction->execute(
            $request->validated(),
            $request->file('picture'),
            Auth::id()
        );

        return redirect()->route('staff.index')->with('success', 'Staff member added successfully!');
    }

    /**
     * Display the specified staff
     */
    public function show(Staff $staff)
    {
        $staff->load([
            'bloodGroup',
            'subjectSpecializations.subject',
            'bsSubjects',
            'studentTeacherSubjects' => function($query) {
                $query->whereHas('student');
            },
            'studentTeacherSubjects.student.admissions',
            'studentTeacherSubjects.subject',
            'heTeacherAssignments' => function($query) {
                $query->whereHas('heAdmission.student');
            },
            'heTeacherAssignments.heAdmission.student',
            'heTeacherAssignments.heAdmission.program',
            'heTeacherAssignments.bsSubject',
            'academicQualifications',
            'promotionHistory',
            'serviceHistory'
        ]);

        return view('staff.show', compact('staff'));
    }

    /**
     * Show the form for editing staff
     */
    public function edit(Staff $staff)
    {
        $bloodGroups = BloodGroup::all();
        $subjects = Subject::orderBy('name', 'asc')->get();
        $currentSubjects = $staff->subjectSpecializations->pluck('subject_id')->toArray();
        $designations = Designation::orderBy('name', 'asc')->get();
        $bsSubjects = BsSubject::orderBy('name', 'asc')->get();
        $currentBsSubjects = $staff->bsSubjects->pluck('id')->toArray();
        
        return view('staff.edit', compact('staff', 'bloodGroups', 'subjects', 'currentSubjects', 'designations', 'bsSubjects', 'currentBsSubjects'));
    }

    /**
     * Update specified staff
     */
    public function update(UpdateStaffRequest $request, Staff $staff, UpdateStaffAction $updateStaffAction)
    {
        $updateStaffAction->execute(
            $staff,
            $request->validated(),
            $request->file('picture'),
            Auth::id()
        );

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully!');
    }

    /**
     * Remove the specified staff
     */
    public function destroy(Staff $staff)
    {
        if ($staff->picture) {
            $this->fileUploadService->delete($staff->picture);
        }
        $staff->delete();
        return redirect()->route('staff.index')->with('success', 'Staff member deleted successfully!');
    }

    /**
     * Staff panel - view students assigned to a teacher
     */
    public function staffPanel(Request $request)
    {
        $data = $this->staffAssignmentService->getStaffPanelData(
            $request->input('admission_year'),
            $request->input('subject_id'),
            $request->input('staff_id')
        );

        return view('staff.panel', $data);
    }

    /**
     * Student assignment management page
     */
    public function studentAssignment(Request $request)
    {
        $admissionYears = $this->staffAssignmentService->getAdmissionYears();
        return view('staff.student_assignment', compact('admissionYears'));
    }

    /**
     * Get subjects for a specific admission year (AJAX)
     */
    public function getSubjectsByYear(Request $request)
    {
        $subjects = $this->staffAssignmentService->getSubjectsByYear($request->admission_year);
        return response()->json($subjects);
    }

    /**
     * Get teachers for a specific subject (AJAX)
     */
    public function getTeachersBySubject(Request $request)
    {
        $teachers = $this->staffAssignmentService->getTeachersBySubject($request->subject_id);
        return response()->json($teachers);
    }

    /**
     * Get sections for a specific admission year and subject (AJAX)
     */
    public function getSectionsByYear(Request $request)
    {
        $sections = $this->staffAssignmentService->getSectionsByYear(
            $request->admission_year,
            $request->subject_id
        );
        return response()->json($sections);
    }

    /**
     * Get unassigned students for a subject (AJAX)
     */
    public function getUnassignedStudents(Request $request)
    {
        if (!$request->admission_year || !$request->subject_id) {
            return response()->json(['html' => '']);
        }

        $students = $this->staffAssignmentService->getUnassignedStudents(
            $request->admission_year,
            $request->subject_id
        );

        $html = view('staff.partials.unassigned_list', compact('students'))->render();
        return response()->json(['html' => $html]);
    }

    /**
     * Get assigned students for a teacher and subject (AJAX)
     */
    public function getAssignedStudents(Request $request)
    {
        if (!$request->admission_year || !$request->subject_id || !$request->staff_id) {
            return response()->json(['html' => '']);
        }

        $students = $this->staffAssignmentService->getAssignedStudents(
            $request->admission_year,
            $request->subject_id,
            $request->staff_id
        );

        $html = view('staff.partials.assigned_list', compact('students'))->render();
        return response()->json(['html' => $html]);
    }

    /**
     * Assign students to staff (AJAX)
     */
    public function assignStudents(Request $request)
    {
        $this->staffAssignmentService->assignStudents(
            $request->student_ids ?? [],
            $request->staff_id,
            $request->subject_id,
            $request->academic_year,
            Auth::id()
        );

        return response()->json(['success' => true, 'message' => 'Students assigned successfully!']);
    }

    /**
     * Unassign students from staff (AJAX)
     */
    public function unassignStudents(Request $request)
    {
        $this->staffAssignmentService->unassignStudents(
            $request->student_ids ?? [],
            $request->staff_id,
            $request->subject_id
        );

        return response()->json(['success' => true, 'message' => 'Students unassigned successfully!']);
    }

    /**
     * Export staff list as CSV
     */
    public function export(Request $request)
    {
        return $this->staffAssignmentService->exportStaffCsv($request->only(['category', 'status']));
    }

    /**
     * Download Empty Award List PDF
     */
    public function downloadAwardList(Request $request)
    {
        $year = $request->admission_year;
        $subjectId = $request->subject_id;
        $staffId = $request->staff_id;

        if (!$year || !$subjectId || !$staffId) {
            return redirect()->back()->with('error', 'Please select all fields');
        }

        return $this->staffAssignmentService->downloadAwardListPdf($year, $subjectId, $staffId);
    }

    /**
     * Get BPS for a specific designation (AJAX)
     */
    public function getDesignationBps(Request $request)
    {
        $request->validate([
            'designation_id' => 'required|exists:designations,id'
        ]);

        $designation = Designation::find($request->designation_id);
        
        return response()->json([
            'bps' => $designation->bps,
            'name' => $designation->name
        ]);
    }

    /**
     * Show award list form
     */
    public function awardListForm()
    {
        $admissionYears = $this->staffAssignmentService->getAdmissionYears();
        $subjects = Subject::orderBy('name')->get();

        return view('staff.award_list_form', compact('admissionYears', 'subjects'));
    }

    /**
     * Generate award list
     */
    public function generateAwardList(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'subject_id' => 'required|exists:subjects,id'
        ]);

        $data = $this->staffAssignmentService->getAwardListData(
            $request->admission_year,
            $request->subject_id
        );

        return view('staff.award_list', $data);
    }
}
