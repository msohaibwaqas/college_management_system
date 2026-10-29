<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdministrativeStaff() || auth()->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'title'           => 'required|string|max:255',
            'body'            => 'required|string',
            'recipient_group' => 'required|array',
            'channels'        => 'required|array',
            'is_holiday'       => 'nullable|boolean',
            'holiday_date'     => 'required_if:is_holiday,1|nullable|date',
            'holiday_end_date' => 'nullable|date|after_or_equal:holiday_date',
            'scheduled_at'     => 'nullable|date|after_or_equal:now',
        ];
    }

    public function messages(): array
    {
        return [
            'holiday_end_date.after_or_equal' => 'The holiday / vacation end date must be on or after the start date.',
            'holiday_date.required_if'        => 'Please provide a start date for the holiday / vacation announcement.',
        ];
    }
}
