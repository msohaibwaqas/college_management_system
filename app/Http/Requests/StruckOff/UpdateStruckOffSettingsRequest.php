<?php

namespace App\Http\Requests\StruckOff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStruckOffSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->isAdministrativeStaff();
    }

    public function rules(): array
    {
        return [
            'absent_threshold'          => 'required|integer|min:1|max:100',
            'max_readmissions_per_year' => 'required|integer|min:1|max:10',
            'calculation_period'        => 'required|in:monthly,session',
        ];
    }
}
