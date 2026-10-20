<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && ($user->isTeacher() || $user->isAdministrativeStaff());
    }

    /**
     * Normalize alternate parameter names before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'class' => $this->input('class') ?? $this->input('class_level'),
            'year'  => $this->input('year') ?? $this->input('admission_year'),
        ]);
    }

    public function rules(): array
    {
        return [
            'subject_id'      => 'required|exists:subjects,id',
            'attendance_date' => 'required|date',
            'attendance'      => 'required|array',
            'attendance.*'    => 'in:present,absent,leave,late',
            'remarks'         => 'nullable|array',
            'remarks.*'       => 'nullable|string|max:255',
            'class'           => 'nullable|string|max:50',
            'year'            => 'nullable|integer',
            'section'         => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.required'      => 'A valid subject is required to mark attendance.',
            'attendance_date.required' => 'Attendance date is required.',
            'attendance.required'      => 'No attendance data was submitted.',
            'attendance.*.in'          => 'Invalid attendance status provided.',
        ];
    }
}
