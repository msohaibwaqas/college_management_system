<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAttribute;
use App\Models\Father;
use App\Models\Contact;
use App\Models\Admissions;
use App\Models\SscRecord;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\AdmissionApplication;
use App\Models\StudentCompulsorySubject;
use App\Models\StudentElectiveSubject;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\BiseRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\FileUploadService;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Actions\Student\CreateStudentAction;
use App\Actions\Student\UpdateStudentAction;

class StudentController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

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

        // Determine if we should restrict to teacher's students
        $isTeacher = Auth::check() && Auth::user()->isTeacher();

        // If user is a teacher, show only assigned students
        if ($isTeacher) {
            $staff = Auth::user()->staff;
            $query->assignedToTeacher($staff?->id);
        }

        // Filter by admission year
        if ($request->filled('admission_year')) {
            $query->inBatch((int) $request->admission_year);
        }

        // Hide graduated students by default
        if (!$request->filled('show_graduated') || $request->show_graduated != '1') {
            $query->whereNotGraduated();
        }

        // Search by name or roll number
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'college_roll_no');
        $sortOrder = $request->get('sort_order', 'asc');

        try {
            if (in_array($sortBy, ['bise_roll_11', 'bise_roll_12', 'college_roll_no', 'admission_year'])) {
                // Use orderBy with a subquery to avoid join conflicts
                $query->orderByRaw("(SELECT CAST({$sortBy} AS UNSIGNED) FROM admissions WHERE admissions.student_id = students.id LIMIT 1) {$sortOrder}");
            } elseif ($sortBy === 'status') {
                $query->join('admissions', 'students.id', '=', 'admissions.student_id')
                      ->orderBy('admissions.status', $sortOrder)
                      ->select('students.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } catch (\Exception $e) {
            \Log::error('Sorting error: ' . $e->getMessage());
            // Fallback to default sorting
            $query->orderBy('id', 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $students = $query->paginate($perPage)->appends($request->except('page'));

        // Get unique admission years for filter
        $admissionYears = Admissions::distinctAdmissionYears();

        return view('students.index', compact('students', 'admissionYears'));
    }

    /**
     * Show form to create a new student
     */
    public function create()
    {
        // Optimization: Cache static reference data for 1 hour
        $bloodGroups = \Illuminate\Support\Facades\Cache::remember('blood_groups', 3600, fn() => BloodGroup::all());
        $quotas = \Illuminate\Support\Facades\Cache::remember('quotas', 3600, fn() => Quota::all());
        $bises = \Illuminate\Support\Facades\Cache::remember('bise_records', 3600, fn() => BiseRecord::all());
        $subjects = \Illuminate\Support\Facades\Cache::remember('subjects_elective', 3600, fn() => Subject::where('type', 'Elective')->orderBy('name')->get());
        $subjectGroups = \Illuminate\Support\Facades\Cache::remember('subject_groups_all', 3600, fn() => SubjectGroup::with('subjects')->get());
        
        return view('students.create', compact('bloodGroups', 'quotas', 'bises', 'subjects', 'subjectGroups'));
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
            return $this->handleDatabaseException($e);
        } catch (\Exception $e) {
            \Log::error('General Exception in store: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, Student $student)
    {
        $redirectTo = $request->get('redirect_to', url()->previous());
        if (Auth::user()?->isTeacher()) {
            return redirect()->route('students.index')->with('error', 'Access denied.');
        }

        // Optimization: Cache static reference data for 1 hour
        $bloodGroups = \Illuminate\Support\Facades\Cache::remember('blood_groups', 3600, fn() => BloodGroup::all());
        $quotas = \Illuminate\Support\Facades\Cache::remember('quotas', 3600, fn() => Quota::all());
        $bises = \Illuminate\Support\Facades\Cache::remember('bise_records', 3600, fn() => BiseRecord::all());
        $subjects = \Illuminate\Support\Facades\Cache::remember('subjects_elective', 3600, fn() => Subject::where('type', 'Elective')->orderBy('name')->get());
        $subjectGroups = \Illuminate\Support\Facades\Cache::remember('subject_groups_all', 3600, fn() => SubjectGroup::with('subjects')->get());

        // Force fresh load of relationships
        $student->load([
            'contact',
            'father',
            'admissions',
            'attribute',
            'sscRecord',
            'compulsorySubjects',
        ]);
        
        // Force fresh electives (bypass cache)
        $student->setRelation('electives', StudentElectiveSubject::where('student_id', $student->id)
            ->orderBy('sequence')
            ->with('subject')
            ->get());

        // Get the current subject group ID from one of the electives
        $currentSubjectGroupId = $student->electives->first() ? $student->electives->first()->subject_group_id : null;

        $admission = $student->admissions->first();
        $collegeRollNo = $admission ? $admission->college_roll_no : '';
        $hsscRegNo = $admission ? $admission->hssc_reg_no : '';

        // Auto-split first_name if last_name is empty
        if (empty(trim($student->last_name ?? ''))) {
            $fullName = trim(preg_replace('/\s+/', ' ', $student->first_name ?? ''));
            $parts = explode(' ', $fullName);
            if (count($parts) > 1) {
                $student->last_name = array_pop($parts);
                $student->first_name = implode(' ', $parts);
            } else {
                $student->last_name = $fullName;
            }
        }

        // Auto-default domicile
        if ($student->attribute && empty(trim($student->attribute->domicile ?? ''))) {
            $student->attribute->domicile = 'Bahawalnagar';
        }

        // Auto-default student_contact
        if ($student->contact && empty(trim($student->contact->student_contact ?? ''))) {
            $student->contact->student_contact = $hsscRegNo;
        }

        // Ensure SSC always exists with sensible defaults
        $biseBahawalpur = BiseRecord::where('name', 'like', '%Bahawalpur%')->first();
        $biseId = $biseBahawalpur ? $biseBahawalpur->id : 1;

        if (!$student->sscRecord) {
            $student->sscRecord = new SscRecord([
                'bise_id' => $biseId,
                'roll_no' => $collegeRollNo,
                'reg_no' => $hsscRegNo,
                'school_name' => 'Govt. High School Chishtian',
                'total_mark' => 1200,
                'obtained_mark' => 600,
                'passing_year' => (int)date('Y'),
                'passing_session' => '1st Annual',
                'ssc_group' => 'Science',
            ]);
        } else {
            if (empty($student->sscRecord->bise_id)) $student->sscRecord->bise_id = $biseId;
            if (empty(trim($student->sscRecord->roll_no ?? ''))) $student->sscRecord->roll_no = $collegeRollNo;
            if (empty(trim($student->sscRecord->reg_no ?? ''))) $student->sscRecord->reg_no = $hsscRegNo;
            if (empty(trim($student->sscRecord->school_name ?? ''))) $student->sscRecord->school_name = 'Govt. High School Chishtian';
            if (empty($student->sscRecord->total_mark)) $student->sscRecord->total_mark = 1200;
            if (empty($student->sscRecord->obtained_mark)) $student->sscRecord->obtained_mark = 600;
            if (empty($student->sscRecord->passing_year)) $student->sscRecord->passing_year = (int)date('Y');
            if (empty($student->sscRecord->passing_session)) $student->sscRecord->passing_session = '1st Annual';
            if (empty($student->sscRecord->ssc_group)) $student->sscRecord->ssc_group = 'Science';
        }

        return view('students.edit', compact('student', 'bloodGroups', 'quotas', 'bises', 'subjects', 'subjectGroups', 'currentSubjectGroupId', 'redirectTo'));
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
            return $this->handleDatabaseException($e);
        } catch (\Exception $e) {
            \Log::error('Student update failed: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the student. Please try again or contact support if the problem persists.');
        }
    }

    /**
     * Handle common duplicate constraint violations in student creation/updates.
     */
    protected function handleDatabaseException(\Illuminate\Database\QueryException $e)
    {
        \Log::error('Student DB Exception: ' . $e->getMessage());

        if ($e->getCode() == 23000) {
            if (str_contains($e->getMessage(), 'uq_ssc_roll_reg')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'The SSC Roll Number and Registration Number combination already exists in the system. Please check and try again.');
            } elseif (str_contains($e->getMessage(), 'college_roll_no')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'The College Roll Number already exists for this admission year/program. Please use a different roll number.');
            } elseif (str_contains($e->getMessage(), 'hssc_reg_no')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'The HSSC Registration Number already exists. Please use a different registration number.');
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'A duplicate entry was detected: ' . $e->getMessage());
            }
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'A database error occurred: ' . $e->getMessage());
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
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('admissions', function($subQ) use ($search) {
                      $subQ->where('college_roll_no', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->paginate(50)->appends($request->except('page'));
            
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
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            
            // Optimization: Use single query for bulk delete instead of looping
            $deletedCount = Student::whereIn('id', $studentIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} student(s).",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
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
            'status' => 'required|in:Applied,Admitted,Active,Graduated,Dropped,Suspended'
        ]);

        try {
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            $status = $request->status;
            $updatedCount = 0;

            // Update admissions status for selected students
            // We assume we are updating their *active* or most recent admission if they have multiple, 
            // but typically a student has one main admission record we care about for status.
            // A safer approach is to update all non-graduated admissions or just the latest one.
            // Given the context, we'll update the admission record associated with the current year or just all for simplicity 
            // as usually students have one active admission track.
            
            // Better approach: Update the admission records where student_id is in the list.
            $updatedCount = Admissions::whereIn('student_id', $studentIds)
                ->update(['status' => $status]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated status for {$updatedCount} admission record(s).",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
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
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            $promotedCount = 0;

            // Find Class 11 admissions for these students
            $admissions = Admissions::whereIn('student_id', $studentIds)
                ->where('current_class', '11')
                ->whereIn('status', ['Admitted', 'Active', 'admitted', 'active'])
                ->get();

            foreach ($admissions as $admission) {
                if ($admission->promoteToClass12()) {
                    $promotedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully promoted {$promotedCount} student(s) to Class 12.",
                'promoted_count' => $promotedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
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

        // Apply same filters as index
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

        $students = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Student Name',
                'Father Name',
                'College Roll No',
                'BISE Roll 11',
                'BISE Roll 12',
                'Admission Year',
                'Elective 1',
                'Elective 2',
                'Elective 3'
            ]);

            foreach ($students as $student) {
                $admission = $student->admissions->first();
                $electives = $student->electives->sortBy('sequence');
                
                fputcsv($file, [
                    $student->first_name . ' ' . $student->last_name,
                    $student->father ? $student->father->name : '',
                    $admission ? $admission->college_roll_no : '',
                    $admission ? $admission->bise_roll_11 : '',
                    $admission ? $admission->bise_roll_12 : '',
                    $admission ? $admission->admission_year : '',
                    $electives->get(0) ? $electives->get(0)->subject->name : '',
                    $electives->get(1) ? $electives->get(1)->subject->name : '',
                    $electives->get(2) ? $electives->get(2)->subject->name : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
            'results.subject'
        ]);

        return view('students.show', compact('student'));
    }

    /**
     * Students by subject - filter students by selected subject
     */
    public function studentsBySubject(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $selectedYear = $request->input('admission_year');
        $selectedSubject = $request->input('subject_id');

        // Get subjects for selected year
        $subjects = collect();
        if ($selectedYear) {
            $subjects = Subject::where('type', 'Elective')
                ->whereHas('studentElectives.student.admissions', function($q) use ($selectedYear) {
                    $q->where('admission_year', $selectedYear);
                })
                ->orderBy('name', 'ASC')
                ->get();
        }

        // Get students for selected year and subject
        $students = collect();
        $subjectName = '';
        
        if ($selectedYear && $selectedSubject) {
            $students = Student::with(['admissions', 'father', 'attribute'])
                ->whereHas('admissions', function($q) use ($selectedYear) {
                    $q->where('admission_year', $selectedYear);
                })
                ->whereHas('electives', function($q) use ($selectedSubject) {
                    $q->where('subject_id', $selectedSubject);
                })
                ->get();

            $subject = Subject::find($selectedSubject);
            $subjectName = $subject ? $subject->name : '';
        }

        return view('students.by_subject', compact('admissionYears', 'subjects', 'selectedYear', 'selectedSubject', 'students', 'subjectName'));
    }

    /**
     * Group students by their subject combinations
     */
    public function groupStudents(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $selectedYear = $request->input('admission_year');
        $groups = [];

        if ($selectedYear) {
            $students = Student::with(['electives.subject', 'admissions', 'father'])
                ->inBatch((int) $selectedYear)
                ->get();

            foreach ($students as $student) {
                $subjectNames = $student->electives->pluck('subject.name')->toArray();
                if (count($subjectNames) > 0) {
                    $group = $student->subject_group;
                    $admission = $student->admissions->first();

                    $groups[$group][] = [
                        'roll_no' => $admission ? $admission->college_roll_no : 'N/A',
                        'name' => $student->full_name,
                        'father_name' => $student->father ? $student->father->name : 'N/A',
                        'subjects' => implode(', ', $subjectNames)
                    ];
                }
            }
        }

        return view('students.groups', compact('admissionYears', 'selectedYear', 'groups'));
    }

    /**
     * Export grouped students as CSV
     */
    public function exportGroupsCSV($year)
    {
        $students = Student::with(['electives.subject', 'admissions', 'father'])
            ->inBatch((int) $year)
            ->get();

        $filename = "grouped_students_{$year}.csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];

        $callback = function() use ($students) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Group', 'Roll No', 'Name', 'Father Name', 'Subjects']);
            
            foreach ($students as $student) {
                $subjectNames = $student->electives->pluck('subject.name')->toArray();
                $group = count($subjectNames) > 0 ? $student->subject_group : 'Unknown';
                $admission = $student->admissions->first();
                
                fputcsv($output, [
                    $group,
                    $admission ? $admission->college_roll_no : 'N/A',
                    $student->full_name,
                    $student->father ? $student->father->name : 'N/A',
                    implode(', ', $subjectNames)
                ]);
            }
            
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
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

        $student->load(['electives.subject', 'admissions']);
        $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
        $subjectGroups = SubjectGroup::with('subjects')->get();
        
        // Find current group ID if all subjects belong to it
        $currentGroupIds = $student->electives->pluck('subject_group_id')->filter()->unique();
        $groupId = $currentGroupIds->count() === 1 ? $currentGroupIds->first() : null;

        // Fallback: If no subject_group_id on electives, try to find a group that matches these subjects
        if (!$groupId && $student->electives->count() === 3) {
            $studentSubjectIds = $student->electives->pluck('subject_id')->sort()->values()->toArray();
            foreach ($subjectGroups as $group) {
                $groupSubjectIds = $group->subjects->pluck('id')->sort()->values()->toArray();
                // Check if all student subjects are in this group
                if (count(array_intersect($studentSubjectIds, $groupSubjectIds)) === 3) {
                    $groupId = $group->id;
                    break;
                }
            }
        }
        
        return view('students.change_subjects', compact('student', 'subjects', 'subjectGroups', 'groupId'));
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
            $feeService = new \App\Services\FeeCalculationService();
            $result = $feeService->calculateDifference($student, $request->elective_subjects);

            $difference = $result['difference'];

            if ($difference > 0) {
                // Generate a difference voucher
                $voucher = $feeService->createVoucher($student, $result['extra_items']);
                
                $student->updateElectives($request->elective_subjects, Auth::id());

                return redirect()->route('students.show', $student)
                    ->with('success', "Subjects updated successfully. A new voucher ({$voucher->voucher_number}) for the fee difference of {$difference} has been generated.")
                    ->with('voucher_id', $voucher->id);
            } else {
                // No difference or negative (we don't refund automatically here)
                $student->updateElectives($request->elective_subjects, Auth::id());

                return redirect()->route('students.show', $student)
                    ->with('success', 'Subjects updated successfully. No additional fees required.');
            }
        } catch (\Exception $e) {
            \Log::error('Subject change failed: ' . $e->getMessage());
            return back()->with('error', 'Subject change failed: ' . $e->getMessage());
        }
    }

    /**
     * Download or print a fee voucher
     */
    public function downloadVoucher(\App\Models\FeeVoucher $voucher)
    {
        $voucher->load(['items', 'student.admissions', 'student.heAdmissions', 'application']);
        $settings = \App\Models\InstituteSetting::getSettings();
        
        return view('pdfs.fee-voucher', compact('voucher', 'settings'));
    }

    /**
     * Confirm payment for a student fee voucher
     */
    public function confirmVoucherPayment(Request $request, \App\Models\FeeVoucher $voucher)
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
            \Log::error('Voucher payment confirmation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to confirm payment: ' . $e->getMessage());
        }
    }
}
