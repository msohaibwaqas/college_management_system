<?php

namespace App\Http\Controllers;

use App\Models\HeAdmission;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HeRollNumberController extends Controller
{
    public function index(Request $request)
    {
        $programId = $request->input('program_id');
        $admissionYear = $request->input('admission_year');
        $search = $request->input('search');

        $students = [];

        if ($programId && $admissionYear) {
            $query = HeAdmission::with('student', 'program')
                ->where('program_id', $programId)
                ->where('admission_year', $admissionYear);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('roll_no', 'like', "%{$search}%")
                      ->orWhere('uni_roll_no', 'like', "%{$search}%")
                      ->orWhereHas('student', function($sq) use ($search) {
                          $sq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhereHas('father', function($fq) use ($search) {
                                 $fq->where('name', 'like', "%{$search}%");
                             });
                      });
                });
            }

            // Order by College Roll No (roll_no) primarily as it exists first
            $students = $query->orderBy('roll_no')
                              ->orderBy('program_id')
                              ->paginate(50)
                              ->withQueryString();
        }

        $programs = Program::orderBy('name')->get();
        // Get admission years from HeAdmission
        $admissionYears = HeAdmission::select('admission_year')
                                     ->distinct()
                                     ->orderBy('admission_year', 'desc')
                                     ->pluck('admission_year');

        return view('he.roll_numbers.index', compact('students', 'programs', 'admissionYears', 'programId', 'admissionYear', 'search'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'he_admission_id' => 'required|exists:he_admissions,id',
            'uni_roll_no' => [
                'required',
                'string',
                'max:50',
            ],
        ]);

        $admission = HeAdmission::findOrFail($request->he_admission_id);

        // Validate uniqueness: University Roll No must be unique within the Admission Year
        // Across ALL programs (as per user request: "university roll number can not be duplicate with in an admission year")
        $request->validate([
            'uni_roll_no' => Rule::unique('he_admissions', 'uni_roll_no')
                        ->where('admission_year', $admission->admission_year)
                        ->ignore($admission->id),
        ]);

        $admission->uni_roll_no = $request->uni_roll_no;
        $admission->save();

        return back()->with('success', 'University Roll Number updated successfully.');
    }
}
