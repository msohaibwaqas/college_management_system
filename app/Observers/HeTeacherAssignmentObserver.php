<?php

namespace App\Observers;

use App\Models\HeTeacherAssignment;

class HeTeacherAssignmentObserver
{
    /**
     * Handle the HeTeacherAssignment "created" event.
     */

    /**
     * Handle the HeTeacherAssignment "created" event.
     */
    public function created(HeTeacherAssignment $heTeacherAssignment): void
    {
        $this->sendNotification($heTeacherAssignment, 'Assigned');
    }

    /**
     * Handle the HeTeacherAssignment "deleted" event.
     */
    public function deleted(HeTeacherAssignment $heTeacherAssignment): void
    {
        $this->sendNotification($heTeacherAssignment, 'Unassigned');
    }

    protected function sendNotification($assignment, $action)
    {
        // Eager load relationships if not loaded, though normally they might be needed for the names
        $assignment->loadMissing(['heAdmission.student', 'staff', 'bsSubject']);

        $studentName = $assignment->heAdmission && $assignment->heAdmission->student ? $assignment->heAdmission->student->full_name : 'Unknown Student';
        $teacherName = $assignment->staff ? $assignment->staff->full_name : 'Unknown Teacher';
        $subjectName = $assignment->bsSubject ? $assignment->bsSubject->name : 'Unknown Subject';
        $performedBy = auth()->user() ? auth()->user()->full_name : 'System';

        $details = [
            'student_name' => $studentName,
            'student_roll_no' => $assignment->heAdmission->roll_no ?? 'N/A',
            'admission_year' => $assignment->heAdmission->admission_year ?? 'N/A',
            'teacher_name' => $teacherName,
            'subject_name' => $subjectName,
            'action' => $action,
            'performed_by' => $performedBy,
            'timestamp' => now()->toDateTimeString(),
            'type' => 'Higher Education (BS/ADP)',
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
