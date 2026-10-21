<?php

namespace App\Http\Requests\StruckOff;

use App\Models\StudentStruckOffLog;
use Illuminate\Foundation\Http\FormRequest;

class ReadmitStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to readmit this student.
     *
     * Policy Rules:
     * - 1st strike-off: striking teacher or administrative staff (admin / clerk).
     * - 2nd+ strike-off: administrative staff (admin / clerk) only.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $log = $this->route('log');
        if (!$log instanceof StudentStruckOffLog) {
            $log = StudentStruckOffLog::find($log);
        }

        if (!$log) {
            return false;
        }

        return $log->canUserReadmit($user);
    }

    public function rules(): array
    {
        return [
            'readmission_note' => 'nullable|string|max:500',
        ];
    }
}
