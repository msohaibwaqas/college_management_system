<?php

namespace App\Http\Requests\StudentLeave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdministrativeStaff() || $user->hasAnyRole(['admin', 'principal', 'clerk', 'hssc_clerk', 'bs_clerk', 'hssc_admin', 'bs_admin', 'super_admin']));
    }

    public function rules(): array
    {
        return [
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'leave_type'   => 'required|string|in:casual,medical,family_visit,urgent_affair,official,other',
            'reason'       => 'required|string|min:3|max:1000',
            'approved_by'  => 'required|string|max:255',
            'document'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'remarks'      => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required'         => 'Please specify the start date of the leave.',
            'end_date.required'           => 'Please specify the end date of the leave.',
            'end_date.after_or_equal'     => 'End date must be on or after the start date.',
            'reason.required'             => 'Please provide a clear reason for the leave.',
            'approved_by.required'        => 'Please indicate who approved this leave.',
            'document.max'                => 'Attached document cannot exceed 5MB.',
        ];
    }
}
