<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BiseRollNumberController extends Controller
{
    public function index(Request $request)
    {
        $admissionYear = $request->input('admission_year');
        $searchTerm = $request->input('search_term');
        $students = [];
        $targetField = null;
        $currentYear = date('Y');

        if ($admissionYear) {
            if ($admissionYear == $currentYear - 1) {
                $targetField = 'bise_roll_11';
            } elseif ($admissionYear == $currentYear - 2) {
                $targetField = 'bise_roll_12';
            }

            if ($targetField && $searchTerm) {
                $students = Admissions::with(['student.father', 'student.electives.subject'])
                    ->where('admission_year', $admissionYear)
                    ->whereHas('student') // Only load admissions for non-deleted students
                    ->where(function($query) use ($searchTerm) {
                        $query->where('college_roll_no', 'like', "%{$searchTerm}%")
                              ->orWhereHas('student', function($q) use ($searchTerm) {
                                  $q->where('first_name', 'like', "%{$searchTerm}%")
                                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                                    ->orWhereHas('father', function($fq) use ($searchTerm) {
                                        $fq->where('name', 'like', "%{$searchTerm}%");
                                    });
                              });
                    })
                    ->orderByRaw('CAST(college_roll_no AS UNSIGNED) ASC')
                    ->get();
            }
        }

        $admissionYears = Admissions::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');

        return view('students.bise_roll_numbers', compact('students', 'admissionYear', 'searchTerm', 'targetField', 'currentYear', 'admissionYears'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'admission_id' => 'required|exists:admissions,id',
            'target_field' => 'required|in:bise_roll_11,bise_roll_12',
        ]);

        $admission = Admissions::findOrFail($request->admission_id);
        $field = $request->target_field;

        $request->validate([
            'bise_roll_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('admissions', $field)
                    ->where('admission_year', $admission->admission_year)
                    ->ignore($admission->id),
            ],
        ]);
        
        $admission->$field = $request->bise_roll_no;
        $admission->save();

        return back()->with('success', 'BISE Roll Number updated successfully.');
    }
}
