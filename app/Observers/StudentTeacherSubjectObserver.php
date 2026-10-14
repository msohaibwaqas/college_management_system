<?php

namespace App\Observers;

use App\Models\StudentTeacherSubject;

class StudentTeacherSubjectObserver
{
    /**
     * Handle the StudentTeacherSubject "created" event.
     */

    /**
     * Handle the StudentTeacherSubject "created" event.
     */
    public function created(StudentTeacherSubject $assignment): void
    {
        $this->sendNotification($assignment, 'Assigned');
    }

    /**
     * Handle the StudentTeacherSubject "deleted" event.
     */
    public function deleted(StudentTeacherSubject $assignment): void
    {
        $this->sendNotification($assignment, 'Unassigned');
    }

    /**
     * Handle the StudentTeacherSubject "restored" event.
     */
    public function restored(StudentTeacherSubject $assignment): void
    {
        $this->sendNotification($assignment, 'Assigned');
    }

    protected function sendNotification($assignment, $action)
    {
        // Load relationships if not loaded
        $assignment->loadMissing(['student', 'staff', 'subject']);

        $studentName = $assignment->student ? $assignment->student->full_name : 'Unknown Student';
        $teacherName = $assignment->staff ? $assignment->staff->full_name : 'Unknown Teacher';
        $subjectName = $assignment->subject ? $assignment->subject->name : 'Unknown Subject';
        $performedBy = auth()->user() ? auth()->user()->full_name : 'System';

        // Get student admission details
        $rollNo = 'N/A';
        $admissionYear = 'N/A';
        if ($assignment->student) {
            $latestAdmission = $assignment->student->admissions()->latest()->first();
            if ($latestAdmission) {
                $rollNo = $latestAdmission->college_roll_no ?? 'N/A';
                $admissionYear = $latestAdmission->admission_year ?? 'N/A';
            }
        }

        $details = [
            'student_name' => $studentName,
            'student_roll_no' => $rollNo,
            'admission_year' => $admissionYear,
            'teacher_name' => $teacherName,
            'subject_name' => $subjectName,
            'action' => $action,
            'performed_by' => $performedBy,
            'timestamp' => now()->toDateTimeString(),
            'type' => 'Intermediate/Secondary',
        ];

        // Find Principals
        $principals = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'principal');
        })->get();

        foreach ($principals as $principal) {
            $principal->notify(new \App\Notifications\StudentTeacherChanged($details));
        }
    }
}
