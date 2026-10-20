<?php

namespace App\Actions\Student;

use App\Models\Admissions;
use App\Models\Contact;
use App\Models\Father;
use App\Models\SscRecord;
use App\Models\Student;
use App\Models\StudentAttribute;
use App\Models\StudentCompulsorySubject;
use App\Models\StudentElectiveSubject;
use App\Models\StudentSubjectSection;
use App\Models\StudentTeacherSubject;
use App\Models\SubjectAssignmentRule;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateStudentAction
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Execute student update with all related models inside a single database transaction.
     */
    public function execute(Student $student, array $data, ?UploadedFile $picture = null, ?int $userId = null): Student
    {
        return DB::transaction(function () use ($student, $data, $picture, $userId) {
            // 1. Picture Handling
            $picturePath = null;
            if ($picture) {
                $picturePath = $this->fileUploadService->update(
                    $picture,
                    $student->picture,
                    'student_pictures'
                );
            }

            // 2. Student Master Update
            $studentData = [
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'cnic'          => $data['student_cnic'] ?? null,
            ];

            if (!empty($data['gender'])) {
                $studentData['gender'] = $data['gender'];
            }

            if ($picturePath) {
                $studentData['picture'] = $picturePath;
            }

            $student->update($studentData);

            // 3. Student Attribute Update
            $student->attribute()->updateOrCreate(
                ['student_id' => $student->id],
                [
                    'blood_group_id' => $data['blood_group_id'] ?? null,
                    'quota_id'       => $data['quota_id'] ?? null,
                    'religion'       => $data['religion'] ?? null,
                    'nationality'    => $data['nationality'] ?? null,
                    'domicile'       => $data['domicile'] ?? null,
                    'student_cnic'   => $data['student_cnic'] ?? null,
                ]
            );

            // 4. Father Update
            $student->father()->updateOrCreate(
                ['student_id' => $student->id],
                [
                    'name'           => $data['father_name'],
                    'cnic'           => $data['father_cnic'] ?? null,
                    'contact'        => $data['father_contact'] ?? null,
                    'occupation'     => $data['father_occupation'] ?? null,
                    'annual_income'  => $data['father_annual_income'] ?? null,
                ]
            );

            // 5. Contact Update
            $contactData = [
                'student_contact' => $data['student_contact'] ?? null,
                'whatsapp_no'     => $data['whatsapp_no'] ?? null,
                'email'           => $data['email'] ?? null,
                'address'         => $data['address'] ?? null,
            ];
            if ($picturePath) {
                $contactData['picture'] = $picturePath;
            }

            $student->contact()->updateOrCreate(
                ['student_id' => $student->id],
                $contactData
            );

            // 6. Admission Record Update
            $admission = $student->admissions->first();
            if ($admission) {
                $admission->update([
                    'admission_year'  => $data['admission_year'],
                    'academic_year'   => $data['academic_year'],
                    'college_roll_no' => $data['college_roll_no'],
                    'hssc_reg_no'     => $data['hssc_reg_no'],
                    'current_class'   => $data['current_class'] ?? 'N/A',
                    'program_type'    => $data['program_type'],
                    'program_name'    => trim($data['program_name'] ?? 'FSc/FA'),
                    'shift'           => $data['shift'] ?? 'Morning',
                    'semester'        => 1,
                    'status'          => $data['status'] ?? $admission->status,
                ]);
            }

            // 7. SSC Record Update
            if (!empty($data['ssc']) && is_array($data['ssc'])) {
                SscRecord::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'bise_id'         => $data['ssc']['bise_id'],
                        'roll_no'         => $data['ssc']['roll_no'],
                        'reg_no'          => $data['ssc']['reg_no'],
                        'school_name'     => $data['ssc']['school_name'],
                        'total_mark'      => $data['ssc']['total_mark'],
                        'obtained_mark'   => $data['ssc']['obtained_mark'],
                        'passing_year'    => $data['ssc']['passing_year'],
                        'passing_session' => $data['ssc']['passing_session'],
                        'ssc_group'       => $data['ssc']['ssc_group'],
                    ]
                );
            }

            // 8. Update Elective Subjects
            if (!empty($data['elective_subjects'])) {
                $newElectiveIds = collect($data['elective_subjects'])->filter()->values();
                $compulsoryIds = $student->compulsorySubjects()->pluck('subject_id');
                $validSubjectIds = $compulsoryIds->merge($newElectiveIds)->unique();

                // Clean up dropped subjects from teacher assignments and sections
                StudentTeacherSubject::where('student_id', $student->id)
                    ->whereNotIn('subject_id', $validSubjectIds)
                    ->delete();

                StudentSubjectSection::where('student_id', $student->id)
                    ->whereNotIn('subject_id', $validSubjectIds)
                    ->delete();

                StudentElectiveSubject::where('student_id', $student->id)->forceDelete();

                foreach ($data['elective_subjects'] as $sequence => $subjectId) {
                    if (!empty($subjectId)) {
                        StudentElectiveSubject::create([
                            'student_id'       => $student->id,
                            'subject_id'       => $subjectId,
                            'subject_group_id' => $data['subject_group_id'],
                            'sequence'         => $sequence + 1,
                            'created_by'       => $userId,
                        ]);
                    }
                }
            }

            // 9. Update Compulsory Subjects
            $compulsorySubjectIds = $data['compulsory_subjects'] ?? [];
            if (empty($compulsorySubjectIds)) {
                $religion = $data['religion'] ?? optional($student->attribute)->religion ?? 'Islam';
                $nationality = $data['nationality'] ?? optional($student->attribute)->nationality ?? 'Pakistani';
                $classLevel = $data['current_class'] ?? optional($student->admissions->first())->current_class ?? '11';
                $ruleSubjects = SubjectAssignmentRule::getSubjectsForStudent($classLevel, $nationality, $religion);
                $compulsorySubjectIds = $ruleSubjects->pluck('id')->toArray();
            }

            if (!empty($compulsorySubjectIds)) {
                StudentCompulsorySubject::where('student_id', $student->id)->forceDelete();
                foreach ($compulsorySubjectIds as $subjectId) {
                    StudentCompulsorySubject::create([
                        'student_id'  => $student->id,
                        'subject_id'  => $subjectId,
                        'class_level' => $data['current_class'] ?? optional($student->admissions->first())->current_class ?? '11',
                        'created_by'  => $userId,
                    ]);
                }
            }

            // Final sanity cleanup of dropped subject assignments
            $allActiveSubjectIds = $student->compulsorySubjects()->pluck('subject_id')
                ->merge($student->electives()->pluck('subject_id'))
                ->unique();

            StudentTeacherSubject::where('student_id', $student->id)
                ->whereNotIn('subject_id', $allActiveSubjectIds)
                ->delete();

            StudentSubjectSection::where('student_id', $student->id)
                ->whereNotIn('subject_id', $allActiveSubjectIds)
                ->delete();

            return $student;
        });
    }
}
