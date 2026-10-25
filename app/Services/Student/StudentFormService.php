<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\BiseRecord;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\StudentElectiveSubject;
use App\Models\SscRecord;
use Illuminate\Support\Facades\Cache;

class StudentFormService
{
    /**
     * Reference tables cached for 1 hour.
     */
    public function getCachedReferenceData(): array
    {
        $bloodGroups = Cache::remember('blood_groups', 3600, fn() => BloodGroup::all());
        $quotas = Cache::remember('quotas', 3600, fn() => Quota::all());
        $bises = Cache::remember('bise_records', 3600, fn() => BiseRecord::all());
        $subjects = Cache::remember('subjects_elective', 3600, fn() => Subject::where('type', 'Elective')->orderBy('name')->get());
        $subjectGroups = Cache::remember('subject_groups_all', 3600, fn() => SubjectGroup::with('subjects')->get());

        return compact('bloodGroups', 'quotas', 'bises', 'subjects', 'subjectGroups');
    }

    /**
     * Prepare student model with relationships, name fallbacks, and sensible SSC defaults for edit form.
     */
    public function prepareEditData(Student $student, ?string $redirectTo = null): array
    {
        $refData = $this->getCachedReferenceData();

        $student->load([
            'contact',
            'father',
            'admissions',
            'attribute',
            'sscRecord',
            'compulsorySubjects',
        ]);

        $student->setRelation('electives', StudentElectiveSubject::where('student_id', $student->id)
            ->orderBy('sequence')
            ->with('subject')
            ->get());

        $currentSubjectGroupId = $student->electives->first() ? $student->electives->first()->subject_group_id : null;

        $admission = $student->admissions->first();
        $collegeRollNo = $admission ? $admission->college_roll_no : '';
        $hsscRegNo = $admission ? $admission->hssc_reg_no : '';

        // Auto-split first_name if last_name is empty
        if (empty(trim($student->last_name ?? ''))) {
            $fullName = trim(preg_replace('/\s+/', ' ', $student->first_name ?? ''));
            $parts = explode(' ', $fullName);
            if (count($parts) > 1) {
                $student->last_name = array_pop($parts);
                $student->first_name = implode(' ', $parts);
            } else {
                $student->last_name = $fullName;
            }
        }

        // Auto-default domicile
        if ($student->attribute && empty(trim($student->attribute->domicile ?? ''))) {
            $student->attribute->domicile = 'Bahawalnagar';
        }

        // Auto-default student_contact
        if ($student->contact && empty(trim($student->contact->student_contact ?? ''))) {
            $student->contact->student_contact = $hsscRegNo;
        }

        // Ensure SSC always exists with sensible defaults
        $biseBahawalpur = BiseRecord::where('name', 'like', '%Bahawalpur%')->first();
        $biseId = $biseBahawalpur ? $biseBahawalpur->id : 1;

        if (!$student->sscRecord) {
            $student->sscRecord = new SscRecord([
                'bise_id' => $biseId,
                'roll_no' => $collegeRollNo,
                'reg_no' => $hsscRegNo,
                'school_name' => 'Govt. High School Chishtian',
                'total_mark' => 1200,
                'obtained_mark' => 600,
                'passing_year' => (int)date('Y'),
                'passing_session' => '1st Annual',
                'ssc_group' => 'Science',
            ]);
        } else {
            if (empty($student->sscRecord->bise_id)) $student->sscRecord->bise_id = $biseId;
            if (empty(trim($student->sscRecord->roll_no ?? ''))) $student->sscRecord->roll_no = $collegeRollNo;
            if (empty(trim($student->sscRecord->reg_no ?? ''))) $student->sscRecord->reg_no = $hsscRegNo;
            if (empty(trim($student->sscRecord->school_name ?? ''))) $student->sscRecord->school_name = 'Govt. High School Chishtian';
            if (empty($student->sscRecord->total_mark)) $student->sscRecord->total_mark = 1200;
            if (empty($student->sscRecord->obtained_mark)) $student->sscRecord->obtained_mark = 600;
            if (empty($student->sscRecord->passing_year)) $student->sscRecord->passing_year = (int)date('Y');
            if (empty($student->sscRecord->passing_session)) $student->sscRecord->passing_session = '1st Annual';
            if (empty($student->sscRecord->ssc_group)) $student->sscRecord->ssc_group = 'Science';
        }

        return array_merge($refData, [
            'student' => $student,
            'currentSubjectGroupId' => $currentSubjectGroupId,
            'redirectTo' => $redirectTo,
        ]);
    }

    /**
     * Parse database exceptions into human-readable error messages.
     */
    public function formatDatabaseErrorMessage(\Illuminate\Database\QueryException $e): string
    {
        if ($e->getCode() == 23000) {
            if (str_contains($e->getMessage(), 'uq_ssc_roll_reg')) {
                return 'The SSC Roll Number and Registration Number combination already exists in the system. Please check and try again.';
            } elseif (str_contains($e->getMessage(), 'college_roll_no')) {
                return 'The College Roll Number already exists for this admission year/program. Please use a different roll number.';
            } elseif (str_contains($e->getMessage(), 'hssc_reg_no')) {
                return 'The HSSC Registration Number already exists. Please use a different registration number.';
            } else {
                return 'A duplicate entry was detected: ' . $e->getMessage();
            }
        }

        return 'A database error occurred: ' . $e->getMessage();
    }
}
