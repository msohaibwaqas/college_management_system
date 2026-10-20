<?php

namespace App\Http\Requests\StruckOff;

use Illuminate\Foundation\Http\FormRequest;

class ReadmitStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->isAdministrativeStaff();
    }

    public function rules(): array
    {
        return [
            'readmission_note' => 'nullable|string|max:500',
        ];
    }
}
