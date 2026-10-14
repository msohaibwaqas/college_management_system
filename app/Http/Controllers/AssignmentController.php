<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->student_id) {
            $student = $user->student;
            
            // Inter Subjects
            $subjectIds = $student->studentTeacherSubjects->pluck('subject_id')
                ->merge($student->electives->pluck('subject_id'))
                ->merge($student->compulsorySubjects->pluck('subject_id'))
                ->filter()->unique();
            
            // HE Subjects
            $bsSubjectIds = $student->heAdmissions->flatMap(function($adm) {
                return $adm->subjects->pluck('bs_subject_id');
            })->filter()->unique();
            
            $currentClass = $student->current_class;

            $assignments = \App\Models\Assignment::where(function($q) use ($subjectIds, $bsSubjectIds) {
                    $q->whereIn('subject_id', $subjectIds)
                      ->orWhereIn('bs_subject_id', $bsSubjectIds);
                })
                ->where(function($q) use ($currentClass) {
                    $q->whereNull('target_group')->orWhere('target_group', $currentClass);
                })
                ->with(['teacher', 'subject', 'bsSubject'])
                ->latest()
                ->paginate(10);
            return view('lms.assignments.index', compact('assignments'));
        }

        if ($user->staff_id || $user->hasAnyRole(['admin', 'principal'])) {
            $query = \App\Models\Assignment::with(['subject', 'bsSubject']);
            if ($user->staff_id) {
                $query->where('staff_id', $user->staff_id);
            }
            $assignments = $query->latest()->paginate(10);
            return view('lms.assignments.teacher_index', compact('assignments'));
        }

        return back()->with('error', 'Unauthorized access.');
    }

    public function create()
    {
        $user = auth()->user();
        $staff = $user->staff;
        $isManager = $user->hasAnyRole(['admin', 'principal']);
        
        if (!$staff && !$isManager) return back()->with('error', 'Unauthorized.');

        $subjects = collect();
        $targetGroups = collect();

        if ($staff) {
            // Get Inter subjects
            $interSubjects = \App\Models\Subject::whereIn('id', function($query) use ($staff) {
                $query->select('subject_id')->from('student_teacher_subjects')->where('staff_id', $staff->id);
            })->get()->map(function($s) {
                $s->is_he = false;
                return $s;
            });
            
            // Get HE subjects
            $heSubjects = \App\Models\BsSubject::whereIn('id', function($query) use ($staff) {
                $query->select('bs_subject_id')->from('he_teacher_assignments')->where('staff_id', $staff->id);
            })->get()->map(function($s) {
                $s->is_he = true;
                return $s;
            });

            $subjects = $interSubjects->concat($heSubjects);
            
            // Get Target Groups (Inter Classes)
            $interGroups = \App\Models\StudentTeacherSubject::where('staff_id', $staff->id)
                ->whereNotNull('class_level')
                ->distinct()
                ->pluck('class_level');
                
            // Get Target Groups (HE Semesters)
            $heGroups = \App\Models\HeAdmission::whereIn('id', function($q) use ($staff) {
                $q->select('he_admission_id')->from('he_teacher_assignments')->where('staff_id', $staff->id);
            })->distinct()->pluck('current_semester')->map(function($s) {
                return "Semester $s";
            });

            $targetGroups = $interGroups->concat($heGroups)->unique();
        } else {
            // Manager logic
            $interSubjects = \App\Models\Subject::orderBy('name')->get()->map(function($s) { $s->is_he = false; return $s; });
            $heSubjects = \App\Models\BsSubject::orderBy('name')->get()->map(function($s) { $s->is_he = true; return $s; });
            $subjects = $interSubjects->concat($heSubjects);
            $targetGroups = collect(['11', '12', 'Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6', 'Semester 7', 'Semester 8']);
        }

        return view('lms.assignments.create', compact('subjects', 'targetGroups'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'subject_type' => 'required|in:inter,he',
            'subject_id' => 'nullable|required_if:subject_type,inter|exists:subjects,id',
            'bs_subject_id' => 'nullable|required_if:subject_type,he|exists:bs_subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'target_group' => 'nullable|string',
            'due_date' => 'required|date|after_or_equal:today',
            'total_marks' => 'required|integer|min:1',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        if (!$user->staff_id) {
            return back()->withInput()->withErrors(['staff_id' => 'You must have a linked Staff profile to create assignments.']);
        }

        $data = $request->only(['title', 'description', 'due_date', 'total_marks', 'target_group']);
        $data['staff_id'] = $user->staff_id;
        $data['created_by'] = $user->id;
        
        if ($request->subject_type === 'inter') {
            $data['subject_id'] = $request->subject_id;
        } else {
            $data['bs_subject_id'] = $request->bs_subject_id;
        }

        if ($request->hasFile('attachment')) {
            $data['file_path'] = $request->file('attachment')->store('assignments', 'public');
        }

        \App\Models\Assignment::create($data);

        return redirect()->route('lms.assignments.index')->with('success', 'Assignment created successfully.');
    }

    public function show(\App\Models\Assignment $assignment)
    {
        $user = auth()->user();
        if ($user->student_id) {
            $submission = $user->student->assignmentSubmissions()->where('assignment_id', $assignment->id)->first();
            return view('lms.assignments.show', compact('assignment', 'submission'));
        }

        $isManager = $user->hasAnyRole(['admin', 'principal']);

        if ($isManager || ($user->staff_id && $assignment->staff_id == $user->staff_id)) {
            $submissions = $assignment->submissions()->with(['student.admissions', 'student.heAdmissions'])->get();
            return view('lms.assignments.teacher_show', compact('assignment', 'submissions'));
        }

        return back()->with('error', 'Unauthorized access.');
    }

    public function submit(Request $request, \App\Models\Assignment $assignment)
    {
        $request->validate([
            'submission_file' => 'required|file|mimes:pdf,doc,docx,zip,jpg,png|max:10240',
            'student_notes' => 'nullable|string',
        ]);

        $student = auth()->user()->student;
        if (!$student) return back()->with('error', 'Only students can submit assignments.');

        $filePath = $request->file('submission_file')->store('submissions', 'public');

        \App\Models\AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            [
                'file_path' => $filePath,
                'student_notes' => $request->student_notes,
                'submitted_at' => now(),
                'status' => 'submitted'
            ]
        );

        return back()->with('success', 'Assignment submitted successfully.');
    }

    public function grade(Request $request, \App\Models\AssignmentSubmission $submission)
    {
        $request->validate([
            'obtained_marks' => 'required|numeric|min:0|max:' . $submission->assignment->total_marks,
            'teacher_feedback' => 'nullable|string',
        ]);

        $submission->update([
            'obtained_marks' => $request->obtained_marks,
            'teacher_feedback' => $request->teacher_feedback,
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by' => auth()->id()
        ]);

        return back()->with('success', 'Submission graded successfully.');
    }

    public function edit(\App\Models\Assignment $assignment)
    {
        $user = auth()->user();
        if (!$user->staff_id && !$user->hasAnyRole(['admin', 'principal'])) return back()->with('error', 'Unauthorized.');
        if (!$user->hasAnyRole(['admin', 'principal']) && $assignment->staff_id != $user->staff_id) {
            return back()->with('error', 'You can only edit your own assignments.');
        }

        $staff = $user->staff;
        $subjects = collect();
        $targetGroups = collect();

        if ($staff) {
            $interSubjects = \App\Models\Subject::whereIn('id', function($query) use ($staff) {
                $query->select('subject_id')->from('student_teacher_subjects')->where('staff_id', $staff->id);
            })->get()->map(function($s) { $s->is_he = false; return $s; });
            
            $heSubjects = \App\Models\BsSubject::whereIn('id', function($query) use ($staff) {
                $query->select('bs_subject_id')->from('he_teacher_assignments')->where('staff_id', $staff->id);
            })->get()->map(function($s) { $s->is_he = true; return $s; });

            $subjects = $interSubjects->concat($heSubjects);
            
            $interGroups = \App\Models\StudentTeacherSubject::where('staff_id', $staff->id)->whereNotNull('class_level')->distinct()->pluck('class_level');
            $heGroups = \App\Models\HeAdmission::whereIn('id', function($q) use ($staff) {
                $q->select('he_admission_id')->from('he_teacher_assignments')->where('staff_id', $staff->id);
            })->distinct()->pluck('current_semester')->map(function($s) { return "Semester $s"; });

            $targetGroups = $interGroups->concat($heGroups)->unique();
        } else {
            $interSubjects = \App\Models\Subject::orderBy('name')->get()->map(function($s) { $s->is_he = false; return $s; });
            $heSubjects = \App\Models\BsSubject::orderBy('name')->get()->map(function($s) { $s->is_he = true; return $s; });
            $subjects = $interSubjects->concat($heSubjects);
            $targetGroups = collect(['11', '12', 'Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6', 'Semester 7', 'Semester 8']);
        }

        return view('lms.assignments.edit', compact('assignment', 'subjects', 'targetGroups'));
    }

    public function update(Request $request, \App\Models\Assignment $assignment)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'principal']) && $assignment->staff_id != $user->staff_id) {
            return back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'subject_type' => 'required|in:inter,he',
            'subject_id' => 'nullable|required_if:subject_type,inter|exists:subjects,id',
            'bs_subject_id' => 'nullable|required_if:subject_type,he|exists:bs_subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'target_group' => 'nullable|string',
            'due_date' => 'required|date|after_or_equal:today',
            'total_marks' => 'required|integer|min:1',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        $data = $request->only(['title', 'description', 'due_date', 'total_marks', 'target_group']);
        $data['subject_id'] = ($request->subject_type === 'inter') ? $request->subject_id : null;
        $data['bs_subject_id'] = ($request->subject_type === 'he') ? $request->bs_subject_id : null;
        $data['updated_by'] = $user->id;

        if ($request->hasFile('attachment')) {
            if ($assignment->file_path) \Storage::disk('public')->delete($assignment->file_path);
            $data['file_path'] = $request->file('attachment')->store('assignments', 'public');
        }

        $assignment->update($data);

        return redirect()->route('lms.assignments.index')->with('success', 'Assignment updated successfully.');
    }

    public function destroy(\App\Models\Assignment $assignment)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'principal']) && $assignment->staff_id != $user->staff_id) {
            return back()->with('error', 'Unauthorized.');
        }

        if ($assignment->file_path) \Storage::disk('public')->delete($assignment->file_path);
        $assignment->delete();

        return redirect()->route('lms.assignments.index')->with('success', 'Assignment deleted successfully.');
    }
}
