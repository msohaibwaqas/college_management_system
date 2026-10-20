<?php

namespace App\Http\Requests\StruckOff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStruckOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:students,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'month'      => 'nullable|string',
            'reason'     => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Student selection is required.',
            'student_id.exists'   => 'Selected student does not exist.',
            'subject_id.required' => 'Subject selection is required.',
            'subject_id.exists'   => 'Selected subject does not exist.',
        ];
    }
}
