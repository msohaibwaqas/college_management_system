<?php

namespace App\Services;

use App\Models\HeAdmission;
use App\Models\Admissions;
use App\Models\Student;
use App\Models\Program;
use App\Models\HsscRecord;
use App\Models\InstituteSetting;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class HeAdmissionService
{
    /**
     * Build filtered and sorted HE admission listings.
     */
    public function getIndexData(Request $request): array
    {
        $query = HeAdmission::with(['student.father', 'student.contact', 'program']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%")
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                  });
            });
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('admission_year')) {
            $query->where('admission_year', $request->admission_year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (!$request->boolean('show_graduated')) {
            $query->where('status', '!=', 'Graduated');
        }

        if ($request->filled('semester')) {
            $query->where('current_semester', $request->semester);
        }

        $sortBy = $request->get('sort_by', 'roll_no');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'name') {
            $query->leftJoin('students', 'he_admissions.student_id', '=', 'students.id')
                  ->orderBy('students.first_name', $sortOrder)
                  ->select('he_admissions.*');
        } elseif (in_array($sortBy, ['roll_no', 'admission_year', 'status', 'current_semester'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->get('per_page', 20);
        $heAdmissions = $query->paginate($perPage)->appends($request->all());

        $legacyQuery = Admissions::whereIn('program_type', ['BS', 'ADP', 'DIT'])
            ->with(['student.father', 'student.contact']);

        if ($request->filled('search')) {
            $search = $request->search;
            $legacyQuery->where(function ($q) use ($search) {
                $q->where('college_roll_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                           ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $legacyAdmissions = $legacyQuery->limit(20)->get();

        $programs = Program::whereIn('type', ['BS', 'ADP', 'DIT'])->get();
        $admissionYears = HeAdmission::select('admission_year')->distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');

        return compact('heAdmissions', 'legacyAdmissions', 'programs', 'admissionYears');
    }

    /**
     * Search former Intermediate student or HSSC record for pre-filling admission forms.
     */
    public function searchCandidateByRollNo(string $rollNo): array
    {
        $admission = Admissions::where(function (Builder $query) use ($rollNo) {
                $query->where('bise_roll_11', $rollNo)
                      ->orWhere('bise_roll_12', $rollNo);
            })
            ->with(['student.attribute', 'student.father', 'student.contact', 'student.sscRecord'])
            ->first();

        if ($admission && $admission->student) {
            $student = $admission->student;
            $student->load(['attribute', 'father', 'contact', 'sscRecord']);
            $hsscRecord = HsscRecord::where('student_id', $student->id)->first();

            return [
                'found'  => true,
                'source' => 'Former Intermediate Student',
                'data'   => $this->formatCandidateData($student, $hsscRecord, $admission)
            ];
        }

        $hsscRecord = HsscRecord::where('roll_no', $rollNo)
            ->with(['student.attribute', 'student.father', 'student.contact', 'student.sscRecord'])
            ->first();

        if ($hsscRecord && $hsscRecord->student) {
            $student = $hsscRecord->student;
            return [
                'found'  => true,
                'source' => 'Existing Student Record',
                'data'   => $this->formatCandidateData($student, $hsscRecord)
            ];
        }

        return ['found' => false];
    }

    /**
     * Format student demographic, SSC, and HSSC academic details for JSON response.
     */
    public function formatCandidateData(Student $student, ?HsscRecord $hsscRecord = null, ?Admissions $admission = null): array
    {
        $settings = InstituteSetting::getSettings();

        if (!$hsscRecord && $admission) {
            $finalResults = Result::where('student_id', $student->id)->where('exam_type', 'Final')->get();
            $totalMarks = $finalResults->sum('total_marks');
            $obtainedMarks = $finalResults->sum(function ($r) {
                return ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0);
            });

            return [
                'first_name'           => $student->first_name,
                'last_name'            => $student->last_name,
                'gender'               => $student->gender,
                'date_of_birth'        => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '',
                'student_cnic'         => $student->cnic,
                'blood_group_id'       => $student->attribute->blood_group_id ?? null,
                'quota_id'             => $student->attribute->quota_id ?? null,
                'religion'             => $student->religion ?? 'Islam',
                'nationality'          => $student->nationality ?? 'Pakistani',

                'father_name'          => $student->father->name ?? '',
                'father_cnic'          => $student->father->cnic ?? '',
                'father_contact'       => $student->father->contact ?? '',
                'father_occupation'    => $student->father->occupation ?? '',
                'father_annual_income' => $student->father->annual_income ?? '',

                'student_contact'      => $student->contact->student_contact ?? '',
                'whatsapp_no'          => $student->contact->whatsapp_no ?? '',
                'email'                => $student->contact->email ?? '',
                'address'              => $student->contact->address ?? '',

                'ssc_bise_id'          => $student->sscRecord->bise_id ?? '',
                'ssc_roll_no'          => $student->sscRecord->roll_no ?? '',
                'ssc_reg_no'           => $student->sscRecord->reg_no ?? '',
                'ssc_school_name'      => $student->sscRecord->school_name ?? '',
                'ssc_total_mark'       => $student->sscRecord->total_mark ?? '',
                'ssc_obtained_mark'    => $student->sscRecord->obtained_mark ?? '',
                'ssc_passing_year'     => $student->sscRecord->passing_year ?? '',
                'ssc_passing_session'  => $student->sscRecord->passing_session ?? '',
                'ssc_group'            => $student->sscRecord->ssc_group ?? '',

                'hssc_bise_id'         => $student->sscRecord->bise_id ?? '',
                'hssc_roll_no'         => $admission->bise_roll_12 ?: $admission->bise_roll_11,
                'hssc_reg_no_academic' => $admission->hssc_reg_no ?? '',
                'hssc_college_name'    => $settings->institute_title ?? 'This College',
                'hssc_total_mark'      => $totalMarks ?: '',
                'hssc_obtained_mark'   => $obtainedMarks ?: '',
                'hssc_passing_year'    => $admission->academic_year ?? date('Y'),
                'hssc_passing_session' => '1st Annual',
                'hssc_group'           => $admission->program_name ?? '',
            ];
        }

        return [
            'first_name'           => $student->first_name,
            'last_name'            => $student->last_name,
            'gender'               => $student->gender,
            'date_of_birth'        => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '',
            'student_cnic'         => $student->cnic,
            'blood_group_id'       => $student->attribute->blood_group_id ?? null,
            'quota_id'             => $student->attribute->quota_id ?? null,
            'religion'             => $student->religion ?? 'Islam',
            'nationality'          => $student->nationality ?? 'Pakistani',

            'father_name'          => $student->father->name ?? '',
            'father_cnic'          => $student->father->cnic ?? '',
            'father_contact'       => $student->father->contact ?? '',
            'father_occupation'    => $student->father->occupation ?? '',
            'father_annual_income' => $student->father->annual_income ?? '',

            'student_contact'      => $student->contact->student_contact ?? '',
            'whatsapp_no'          => $student->contact->whatsapp_no ?? '',
            'email'                => $student->contact->email ?? '',
            'address'              => $student->contact->address ?? '',

            'ssc_bise_id'          => $student->sscRecord->bise_id ?? '',
            'ssc_roll_no'          => $student->sscRecord->roll_no ?? '',
            'ssc_reg_no'           => $student->sscRecord->reg_no ?? '',
            'ssc_school_name'      => $student->sscRecord->school_name ?? '',
            'ssc_total_mark'       => $student->sscRecord->total_mark ?? '',
            'ssc_obtained_mark'    => $student->sscRecord->obtained_mark ?? '',
            'ssc_passing_year'     => $student->sscRecord->passing_year ?? '',
            'ssc_passing_session'  => $student->sscRecord->passing_session ?? '',
            'ssc_group'            => $student->sscRecord->ssc_group ?? '',

            'hssc_bise_id'         => $hsscRecord->bise_id ?? '',
            'hssc_roll_no'         => $hsscRecord->roll_no ?? '',
            'hssc_reg_no_academic' => $hsscRecord->reg_no ?? '',
            'hssc_college_name'    => $hsscRecord->college_name ?? '',
            'hssc_total_mark'      => $hsscRecord->total_mark ?? '',
            'hssc_obtained_mark'   => $hsscRecord->obtained_mark ?? '',
            'hssc_passing_year'    => $hsscRecord->passing_year ?? '',
            'hssc_passing_session' => $hsscRecord->passing_session ?? '',
            'hssc_group'           => $hsscRecord->hssc_group ?? '',
        ];
    }
}
