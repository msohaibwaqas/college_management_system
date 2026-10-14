<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LmsController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        if ($user->student_id) {
            return $this->studentDashboard($user);
        }
        
        if ($user->staff_id || $user->hasAnyRole(['admin', 'principal'])) {
            return $this->teacherDashboard($user);
        }

        return redirect()->route('profile.edit')->with('error', 'LMS access requires a linked Student or Staff profile. Please contact the administrator.');
    }

    private function studentDashboard($user)
    {
        $student = $user->student;
        $activeAdmission = $student->admissions()->whereIn('status', ['Active', 'Admitted'])->first() 
                         ?? $student->heAdmissions()->whereIn('status', ['Active', 'Admitted'])->first();

        // Inter Subjects
        $subjectIds = $student->studentTeacherSubjects->pluck('subject_id')
            ->merge($student->electives->pluck('subject_id'))
            ->merge($student->compulsorySubjects->pluck('subject_id'))
            ->filter()->unique();
        
        // HE Subjects
        $bsSubjectIds = $student->heAdmissions->flatMap(function($adm) {
            return $adm->subjects->pluck('bs_subject_id');
        })->filter()->unique();

        $assignments = \App\Models\Assignment::where(function($q) use ($subjectIds, $bsSubjectIds) {
                $q->whereIn('subject_id', $subjectIds)
                  ->orWhereIn('bs_subject_id', $bsSubjectIds);
            })
            ->where(function($q) use ($student) {
                $q->whereNull('target_group')->orWhere('target_group', $student->current_class);
            })
            ->with(['teacher', 'subject', 'bsSubject'])
            ->latest()
            ->limit(5)
            ->get();

        $recentSubmissions = $student->assignmentSubmissions()->with('assignment')->latest()->limit(5)->get();
        
        return view('lms.student_dashboard', compact('student', 'activeAdmission', 'assignments', 'recentSubmissions'));
    }

    private function teacherDashboard($user)
    {
        $staff = $user->staff;
        $isManager = $user->hasAnyRole(['admin', 'principal']);
        
        $query = \App\Models\Assignment::with(['subject', 'bsSubject']);
        
        if (!$isManager && $staff) {
            $query->where('staff_id', $staff->id);
        }
        
        $assignments = $query->latest()->limit(10)->get();
        
        // Count pending submissions across all assignments for this teacher/manager
        $assignmentIdsQuery = \App\Models\Assignment::query();
        if (!$isManager && $staff) {
            $assignmentIdsQuery->where('staff_id', $staff->id);
        }
        $assignmentIds = $assignmentIdsQuery->pluck('id');

        $pendingSubmissionsCount = \App\Models\AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
            ->where('status', 'submitted')
            ->count();

        return view('lms.teacher_dashboard', compact('staff', 'assignments', 'pendingSubmissionsCount', 'isManager'));
    }

    public function performance()
    {
        $user = auth()->user();
        $student = $user->student;
        
        if (!$student) {
            return back()->with('error', 'Only students have a performance record.');
        }

        $results = $student->results()->with('subject')->get();
        return view('lms.performance', compact('student', 'results'));
    }
}
