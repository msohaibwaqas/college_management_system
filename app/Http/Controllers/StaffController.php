<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentTeacherSubject;
use App\Models\StudentSubjectSection;
use App\Models\Admissions;
use App\Models\StaffSubjectSpecialization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\FileUploadService;

class StaffController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of staff
     */
    public function index(Request $request)
    {
        $query = Staff::with(['bloodGroup', 'user']);

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
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
        $bloodGroups = \App\Models\BloodGroup::all();
        $subjects = Subject::orderBy('name', 'asc')->get();
        $designations = \App\Models\Designation::orderBy('name', 'asc')->get();
        $bsSubjects = \App\Models\BsSubject::orderBy('name', 'asc')->get();
        
        return view('staff.create', compact('bloodGroups', 'subjects', 'designations', 'bsSubjects'));
    }

    /**
     * Store a newly created staff
     */
    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|string|max:50|unique:staff,staff_id',
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => ['nullable', 'string', 'max:80', 'regex:/^[a-zA-Z\s]*$/'],
            'father_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'cnic' => ['required', 'string', 'max:15', 'unique:staff,cnic', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'contact_number' => ['nullable', 'string', 'max:20', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^03\d{2}-\d{7}$/'],
            'date_of_birth' => 'required|date',
            'category' => 'required|in:teaching,non_teaching',
            'designation' => 'required|string|max:100',
            'bps' => 'required|integer|min:1|max:22',
            'gender' => 'required|in:male,female',
            'status' => 'required|in:active,left,transferred,retired,suspended',
            'email' => 'nullable|email|max:120|unique:staff,email',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'first_name.regex' => 'The first name must only contain letters and spaces.',
            'last_name.regex' => 'The last name must only contain letters and spaces.',
            'father_name.regex' => 'The father name must only contain letters and spaces.',
            'cnic.regex' => 'The CNIC must follow the format 00000-0000000-0.',
            'contact_number.regex' => 'The contact number must follow the format 03XX-XXXXXXX.',
            'whatsapp_number.regex' => 'The WhatsApp number must follow the format 03XX-XXXXXXX.',
        ]);

        DB::transaction(function () use ($request) {
            // Auto-set designation_id based on designation string
            $designationId = null;
            if ($request->filled('designation')) {
                $designation = \App\Models\Designation::where('name', $request->designation)->first();
                if ($designation) {
                    $designationId = $designation->id;
                }
            }

            $staffData = [
                'staff_id' => $request->staff_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'father_name' => $request->father_name,
                'cnic' => $request->cnic,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'blood_group_id' => $request->blood_group_id,
                'whatsapp_number' => $request->whatsapp_number,
                'email' => $request->email,
                'category' => $request->category,
                'highest_qualification' => $request->highest_qualification,
                'designation' => $request->designation,
                'designation_id' => $designationId,
                'bps' => $request->bps,
                'entry_date_govt_service' => $request->entry_date_govt_service,
                'hed_joining_date' => $request->hed_joining_date,
                'current_college_joining_date' => $request->current_college_joining_date,
                'status' => $request->status,
                'gender' => $request->gender,
                'created_by' => Auth::id(),
            ];

            if ($request->hasFile('picture')) {
                $staffData['picture'] = $this->fileUploadService->upload($request->file('picture'), 'staff_pictures');
            }

            $staff = Staff::create($staffData);

            // Add subject specializations if teaching staff
            if ($request->category == 'teaching' && $request->has('subject_specializations')) {
                foreach ($request->subject_specializations as $subjectId) {
                    StaffSubjectSpecialization::create([
                        'staff_id' => $staff->id,
                        'subject_id' => $subjectId,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Add BS subject specializations if teaching staff
            if ($request->category == 'teaching' && $request->has('bs_subject_specializations')) {
                $staff->bsSubjects()->sync($request->bs_subject_specializations);
            }
        });

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
                $query->whereHas('student'); // Only load assignments for non-deleted students
            },
            'studentTeacherSubjects.student.admissions',
            'studentTeacherSubjects.subject',
            'heTeacherAssignments' => function($query) {
                $query->whereHas('heAdmission.student'); // Only load assignments for non-deleted HE students
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
        $bloodGroups = \App\Models\BloodGroup::all();
        $subjects = Subject::orderBy('name', 'asc')->get();
        $currentSubjects = $staff->subjectSpecializations->pluck('subject_id')->toArray();
        $designations = \App\Models\Designation::orderBy('name', 'asc')->get();
        $bsSubjects = \App\Models\BsSubject::orderBy('name', 'asc')->get();
        $currentBsSubjects = $staff->bsSubjects->pluck('id')->toArray();
        
        return view('staff.edit', compact('staff', 'bloodGroups', 'subjects', 'currentSubjects', 'designations', 'bsSubjects', 'currentBsSubjects'));
    }

    public function update(Request $request, Staff $staff)
    {
        $request->validate([
            'staff_id' => 'required|string|max:50|unique:staff,staff_id,' . $staff->id,
            'first_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => ['nullable', 'string', 'max:80', 'regex:/^[a-zA-Z\s]*$/'],
            'father_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z\s]+$/'],
            'cnic' => ['required', 'string', 'max:15', 'unique:staff,cnic,' . $staff->id, 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'contact_number' => ['nullable', 'string', 'max:20', 'regex:/^03\d{2}-\d{7}$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^03\d{2}-\d{7}$/'],
            'category' => 'required|in:teaching,non_teaching',
            'designation' => 'required|string|max:100',
            'bps' => 'required|integer|min:1|max:22',
            'gender' => 'required|in:male,female',
            'status' => 'required|in:active,left,transferred,retired,suspended',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'first_name.regex' => 'The first name must only contain letters and spaces.',
            'last_name.regex' => 'The last name must only contain letters and spaces.',
            'father_name.regex' => 'The father name must only contain letters and spaces.',
            'cnic.regex' => 'The CNIC must follow the format 00000-0000000-0.',
            'contact_number.regex' => 'The contact number must follow the format 03XX-XXXXXXX.',
            'whatsapp_number.regex' => 'The WhatsApp number must follow the format 03XX-XXXXXXX.',
        ]);

        DB::transaction(function () use ($request, $staff) {
            // Auto-set designation_id based on designation string
            $designationId = null;
            if ($request->filled('designation')) {
                $designation = \App\Models\Designation::where('name', $request->designation)->first();
                if ($designation) {
                    $designationId = $designation->id;
                }
            }

            $updateData = [
                'staff_id' => $request->staff_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'father_name' => $request->father_name,
                'cnic' => $request->cnic,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'blood_group_id' => $request->blood_group_id,
                'whatsapp_number' => $request->whatsapp_number,
                'email' => $request->email,
                'category' => $request->category,
                'highest_qualification' => $request->highest_qualification,
                'designation' => $request->designation,
                'designation_id' => $designationId, // Auto-set designation_id
                'bps' => $request->bps,
                'entry_date_govt_service' => $request->entry_date_govt_service,
                'hed_joining_date' => $request->hed_joining_date,
                'current_college_joining_date' => $request->current_college_joining_date,
                'status' => $request->status,
                'gender' => $request->gender,
                'updated_by' => Auth::id(),
            ];

            // Handle picture upload
            if ($request->hasFile('picture')) {
                $updateData['picture'] = $this->fileUploadService->update(
                    $request->file('picture'),
                    $staff->picture,
                    'staff_pictures'
                );
            }

            $staff->update($updateData);

            // Sync user account status
            if ($staff->user) {
                $userStatus = $staff->status === 'active' ? 'active' : 'suspended';
                if ($staff->user->status !== $userStatus) {
                    $staff->user->update(['status' => $userStatus]);
                }
            }

            // Update subject specializations
            if ($request->category == 'teaching') {
                \App\Models\StaffSubjectSpecialization::where('staff_id', $staff->id)->forceDelete();
                
                if ($request->has('subject_specializations')) {
                    foreach ($request->subject_specializations as $subjectId) {
                        \App\Models\StaffSubjectSpecialization::create([
                            'staff_id' => $staff->id,
                            'subject_id' => $subjectId,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                // Sync BS subject specializations
                $staff->bsSubjects()->sync($request->bs_subject_specializations ?? []);
            } else {
                // Clear BS subjects if not teaching
                $staff->bsSubjects()->detach();
            }

            // Update Academic Qualifications
            if ($request->has('qualifications')) {
                \App\Models\StaffAcademicQualification::where('staff_id', $staff->id)->forceDelete();
                foreach ($request->qualifications as $qual) {
                    if (!empty($qual['degree_level'])) {
                        \App\Models\StaffAcademicQualification::create(array_merge($qual, [
                            'staff_id' => $staff->id,
                            'created_by' => Auth::id()
                        ]));
                    }
                }
            }

            // Update Promotion History
            if ($request->has('promotions')) {
                \App\Models\StaffPromotionHistory::where('staff_id', $staff->id)->forceDelete();
                foreach ($request->promotions as $index => $promo) {
                    if (!empty($promo['new_designation'])) {
                        \App\Models\StaffPromotionHistory::create(array_merge($promo, [
                            'staff_id' => $staff->id,
                            'promotion_number' => $index + 1,
                            'previous_designation' => $promo['previous_designation'] ?: 'N/A',
                            'previous_bps' => $promo['previous_bps'] ?: 0,
                            'promotion_date' => $promo['promotion_date'] ?: now(),
                            'created_by' => Auth::id()
                        ]));
                    }
                }
            }

            // Update Service History
            if ($request->has('service_history')) {
                \App\Models\StaffServiceHistory::where('staff_id', $staff->id)->forceDelete();
                foreach ($request->service_history as $service) {
                    if (!empty($service['institution_name'])) {
                        \App\Models\StaffServiceHistory::create(array_merge($service, [
                            'staff_id' => $staff->id,
                            'created_by' => Auth::id()
                        ]));
                    }
                }
            }
        });

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
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $selectedYear = $request->input('admission_year');
        $selectedSubject = $request->input('subject_id');
        $selectedStaff = $request->input('staff_id');

        // Get subjects for selected year
        $subjects = collect();
        if ($selectedYear) {
            $subjects = Subject::whereHas('studentTeacherSubjects.student.admissions', function($q) use ($selectedYear) {
                $q->where('admission_year', $selectedYear);
            })->orderBy('name', 'asc')->get();
        }

        // Get staff for selected year and subject
        $staffMembers = collect();
        if ($selectedYear && $selectedSubject) {
            $staffMembers = Staff::where('category', 'teaching')
                ->where('status', 'active')
                ->whereHas('studentTeacherSubjects', function($q) use ($selectedYear, $selectedSubject) {
                    $q->whereHas('student.admissions', function($subQ) use ($selectedYear) {
                        $subQ->where('admission_year', $selectedYear);
                    })->where('subject_id', $selectedSubject);
                })
                ->orderBy('first_name', 'asc')
                ->get();
        }

        // Get students
        $students = collect();
        $staffName = '';
        
        if ($selectedYear && $selectedSubject && $selectedStaff) {
            $staffMember = Staff::find($selectedStaff);
            $staffName = $staffMember ? $staffMember->first_name . ' ' . $staffMember->last_name : '';

            $students = Student::with(['admissions', 'father'])
                ->whereHas('admissions', function($q) use ($selectedYear) {
                    $q->where('admission_year', $selectedYear);
                })
                ->whereHas('studentTeacherSubjects', function($q) use ($selectedSubject, $selectedStaff) {
                    $q->where('subject_id', $selectedSubject)
                      ->where('staff_id', $selectedStaff);
                })
                ->get();
        }

        return view('staff.panel', compact('admissionYears', 'subjects', 'staffMembers', 'students', 'selectedYear', 'selectedSubject', 'selectedStaff', 'staffName'));
    }

    /**
     * Student assignment management
     */
    /**
     * Student assignment management
     */
    public function studentAssignment(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        return view('staff.student_assignment', compact('admissionYears'));
    }

    /**
     * Get subjects for a specific admission year
     */
    public function getSubjectsByYear(Request $request)
    {
        $year = $request->admission_year;
        
        if (!$year) {
            return response()->json([]);
        }

        // Get subjects that have students from this admission year (via electives or compulsory)
        $subjects = Subject::where(function($query) use ($year) {
            $query->whereHas('studentElectives.student.admissions', function($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->orWhereHas('studentCompulsorySubjects.student.admissions', function($q) use ($year) {
                $q->where('admission_year', $year);
            });
        })
        ->orderBy('name', 'asc')
        ->get(['id', 'name']);

        return response()->json($subjects);
    }

    /**
     * Get teachers for a specific subject
     */
    public function getTeachersBySubject(Request $request)
    {
        $subjectId = $request->subject_id;

        if (!$subjectId) {
            return response()->json([]);
        }

        $teachers = Staff::where('category', 'teaching')
            ->where('status', 'active')
            ->whereHas('subjectSpecializations', function($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->orderBy('first_name', 'asc')
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($teachers);
    }

    /**
     * Get sections for a specific admission year and subject
     * Reads from student_subject_sections (section is per-student per-subject)
     */
    public function getSectionsByYear(Request $request)
    {
        $year      = $request->admission_year;
        $subjectId = $request->subject_id;

        if (!$year || !$subjectId) {
            return response()->json([]);
        }

        // Query sections from the dedicated table
        $sections = StudentSubjectSection::where('admission_year', $year)
            ->where('subject_id', $subjectId)
            ->select('section')
            ->selectRaw('COUNT(*) as student_count')
            ->selectRaw('GROUP_CONCAT(student_id ORDER BY student_id) as student_ids_csv')
            ->groupBy('section')
            ->orderBy('section')
            ->get()
            ->map(function ($row) {
                return [
                    'section'       => $row->section,
                    'student_count' => (int) $row->student_count,
                    'student_ids'   => array_map('intval', explode(',', $row->student_ids_csv)),
                ];
            });

        return response()->json($sections);
    }

    /**
     * Get unassigned students for a subject
     */
    public function getUnassignedStudents(Request $request)
    {
        $year = $request->admission_year;
        $subject = $request->subject_id;

        if (!$year || !$subject) {
            return response()->json(['html' => '']);
        }

        $students = Student::with([
                'admissions',
                'father',
                'subjectSections' => function($q) use ($subject, $year) {
                    $q->where('subject_id', $subject)->where('admission_year', $year);
                }
            ])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereHas('admissions', function($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->where(function($query) use ($subject) {
                $query->whereHas('electives', function($q) use ($subject) {
                    $q->where('subject_id', $subject);
                })
                ->orWhereHas('compulsorySubjects', function($q) use ($subject) {
                    $q->where('subject_id', $subject);
                });
            })
            ->whereDoesntHave('studentTeacherSubjects', function($q) use ($subject) {
                $q->where('subject_id', $subject);
            })
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
            ->select('students.*')
            ->get();

        $html = view('staff.partials.unassigned_list', compact('students'))->render();
        return response()->json(['html' => $html]);
    }

    /**
     * Get assigned students for a teacher and subject
     */
    public function getAssignedStudents(Request $request)
    {
        $year = $request->admission_year;
        $subject = $request->subject_id;
        $staffId = $request->staff_id;

        if (!$year || !$subject || !$staffId) {
            return response()->json(['html' => '']);
        }

        $students = Student::with([
                'admissions',
                'father',
                'subjectSections' => function($q) use ($subject, $year) {
                    $q->where('subject_id', $subject)->where('admission_year', $year);
                }
            ])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereHas('admissions', function($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->whereHas('studentTeacherSubjects', function($q) use ($subject, $staffId) {
                $q->where('subject_id', $subject)
                  ->where('staff_id', $staffId);
            })
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
            ->select('students.*')
            ->get();

        $html = view('staff.partials.assigned_list', compact('students'))->render();
        return response()->json(['html' => $html]);
    }

    /**
     * Assign students to staff
     */
    public function assignStudents(Request $request)
    {
        $studentIds = $request->student_ids ?? [];
        $staffId = $request->staff_id;
        $subjectId = $request->subject_id;
        $academicYear = $request->academic_year;

        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            if (!$student) continue;
            
            $admission = $student->admissions->first();

            // Check if already assigned (including soft deleted)
            $existingAssignment = StudentTeacherSubject::withTrashed()
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->first();

            if ($existingAssignment) {
                // Update and restore
                $existingAssignment->staff_id = $staffId;
                $existingAssignment->class_level = $admission->current_class ?? '11';
                $existingAssignment->academic_year = $academicYear;
                $existingAssignment->assignment_type = 'elective';
                $existingAssignment->updated_by = Auth::id();
                $existingAssignment->restore(); // Use restore() method to fire 'restored' event
                $existingAssignment->save();
            } else {
                StudentTeacherSubject::create([
                    'student_id' => $studentId,
                    'staff_id' => $staffId,
                    'subject_id' => $subjectId,
                    'class_level' => $admission->current_class ?? '11', // Default to 11 if not set
                    'academic_year' => $academicYear,
                    'assignment_type' => 'elective',
                    'created_by' => Auth::id(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Students assigned successfully!']);
    }

    /**
     * Unassign students from staff
     */
    public function unassignStudents(Request $request)
    {
        $studentIds = $request->student_ids ?? [];
        $staffId = $request->staff_id;
        $subjectId = $request->subject_id;

        $assignments = StudentTeacherSubject::whereIn('student_id', $studentIds)
            ->where('staff_id', $staffId)
            ->where('subject_id', $subjectId)
            ->get();

        foreach ($assignments as $assignment) {
            $assignment->delete();
        }

        return response()->json(['success' => true, 'message' => 'Students unassigned successfully!']);
    }

    /**
     * Export staff list as CSV
     */
    public function export(Request $request)
    {
        $query = Staff::with('bloodGroup');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $staff = $query->orderBy('first_name', 'asc')->get();

        $filename = "staff_export_" . date('Y-m-d_His') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function() use ($staff) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Staff ID',
                'Name',
                'Father Name',
                'CNIC',
                'Contact',
                'Category',
                'Designation',
                'BPS',
                'Status'
            ]);

            foreach ($staff as $member) {
                fputcsv($file, [
                    $member->staff_id,
                    $member->first_name . ' ' . $member->last_name,
                    $member->father_name,
                    $member->cnic,
                    $member->contact_number,
                    ucfirst($member->category),
                    $member->designation,
                    $member->bps,
                    ucfirst($member->status)
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

        $subject = Subject::findOrFail($subjectId);
        $staff = Staff::findOrFail($staffId);

        $students = Student::with(['admissions'])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereHas('admissions', function($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->whereHas('studentTeacherSubjects', function($q) use ($subjectId, $staffId) {
                $q->where('subject_id', $subjectId)
                  ->where('staff_id', $staffId);
            })
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
            ->select('students.*')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('staff.award_list_pdf', compact('students', 'subject', 'staff', 'year'));
        return $pdf->download('award_list_' . $subject->name . '_' . $year . '.pdf');
    }

    /**
     * Get BPS for a specific designation
     */
    public function getDesignationBps(Request $request)
    {
        $request->validate([
            'designation_id' => 'required|exists:designations,id'
        ]);

        $designation = \App\Models\Designation::find($request->designation_id);
        
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
        // Get distinct admission years
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        // Get all subjects
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

        $year = $request->admission_year;
        $subjectId = $request->subject_id;
        $subject = Subject::findOrFail($subjectId);

        // Get teachers who have students assigned for this subject and year
        $teachersData = [];
        
        $teachers = Staff::whereHas('studentTeacherSubjects', function($q) use ($subjectId, $year) {
            $q->where('subject_id', $subjectId)
              ->whereHas('student.admissions', function($aq) use ($year) {
                  $aq->where('admission_year', $year);
              });
        })
        ->where('category', 'teaching')
        ->where('status', 'active')
        ->orderBy('first_name')
        ->get();

        foreach ($teachers as $teacher) {
            // Get students assigned to this teacher for this subject and year
            $students = Student::whereHas('admissions', function($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->whereHas('studentTeacherSubjects', function($q) use ($subjectId, $teacher) {
                $q->where('subject_id', $subjectId)
                  ->where('staff_id', $teacher->id);
            })
            ->with(['admissions' => function($q) use ($year) {
                $q->where('admission_year', $year);
            }])
            ->get()
            ->map(function($student) {
                $admission = $student->admissions->first();
                return [
                    'roll_no' => $admission ? $admission->college_roll_no : 'N/A'
                ];
            })
            ->sortBy('roll_no')
            ->values();

            if ($students->count() > 0) {
                $teachersData[] = [
                    'teacher' => $teacher,
                    'students' => $students
                ];
            }
        }

        $settings = \App\Models\InstituteSetting::getSettings();

        return view('staff.award_list', compact('teachersData', 'subject', 'year', 'settings'));
    }
}
