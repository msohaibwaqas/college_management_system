<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MarkHeAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && ($user->isTeacher() || $user->isAdministrativeStaff());
    }

    public function rules(): array
    {
        return [
            'subject_id'      => 'required|exists:bs_subjects,id',
            'attendance_date' => 'required|date',
            'attendance'      => 'required|array',
            'attendance.*'    => 'in:present,absent,leave,late',
            'remarks'         => 'nullable|array',
            'remarks.*'       => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.required'      => 'A valid BS subject is required to mark attendance.',
            'attendance_date.required' => 'Attendance date is required.',
            'attendance.required'      => 'No attendance data was submitted.',
            'attendance.*.in'          => 'Invalid attendance status provided.',
        ];
    }
}
