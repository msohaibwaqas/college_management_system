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
use App\Models\SubjectAssignmentRule;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateStudentAction
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Execute student creation with all related models inside a single database transaction.
     */
    public function execute(array $data, ?UploadedFile $picture = null, ?int $userId = null): Student
    {
        return DB::transaction(function () use ($data, $picture, $userId) {
            $picturePath = null;
            if ($picture) {
                $picturePath = $this->fileUploadService->upload($picture, 'student_pictures');
            }

            // 1. Student Master
            $student = Student::create([
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'gender'        => $data['gender'],
                'cnic'          => $data['student_cnic'] ?? null,
                'picture'       => $picturePath,
                'created_by'    => $userId,
            ]);

            // 2. Student Attributes
            StudentAttribute::create([
                'student_id'     => $student->id,
                'blood_group_id' => $data['blood_group_id'] ?? null,
                'quota_id'       => $data['quota_id'] ?? null,
                'religion'       => $data['religion'] ?? null,
                'nationality'    => $data['nationality'] ?? null,
                'domicile'       => $data['domicile'] ?? null,
                'student_cnic'   => $data['student_cnic'] ?? null,
                'created_by'     => $userId,
            ]);

            // 3. Father Information
            Father::create([
                'student_id'     => $student->id,
                'name'           => $data['father_name'],
                'cnic'           => $data['father_cnic'] ?? null,
                'contact'        => $data['father_contact'] ?? null,
                'occupation'     => $data['father_occupation'] ?? null,
                'annual_income'  => $data['father_annual_income'] ?? null,
                'created_by'     => $userId,
            ]);

            // 4. Contact Information
            Contact::create([
                'student_id'      => $student->id,
                'student_contact' => $data['student_contact'] ?? null,
                'whatsapp_no'     => $data['whatsapp_no'] ?? null,
                'email'           => $data['email'] ?? null,
                'address'         => $data['address'] ?? null,
                'picture'         => $picturePath,
                'created_by'      => $userId,
            ]);

            // 5. College Admission Record
            Admissions::create([
                'student_id'      => $student->id,
                'admission_year'  => $data['admission_year'],
                'academic_year'   => $data['academic_year'],
                'college_roll_no' => $data['college_roll_no'],
                'hssc_reg_no'     => $data['hssc_reg_no'],
                'current_class'   => $data['current_class'] ?? 'N/A',
                'program_type'    => $data['program_type'],
                'program_name'    => trim($data['program_name'] ?? 'FSc/FA'),
                'shift'           => $data['shift'] ?? 'Morning',
                'semester'        => $data['semester'] ?? 1,
                'status'          => 'Active',
                'created_by'      => $userId,
            ]);

            // 6. SSC Records
            if (!empty($data['ssc_records']) && is_array($data['ssc_records'])) {
                foreach ($data['ssc_records'] as $record) {
                    SscRecord::create([
                        'student_id'      => $student->id,
                        'bise_id'         => $record['bise_id'],
                        'roll_no'         => $record['roll_no'],
                        'reg_no'          => $record['reg_no'],
                        'school_name'     => $record['school_name'],
                        'total_mark'      => $record['total_mark'],
                        'obtained_mark'   => $record['obtained_mark'],
                        'passing_year'    => $record['passing_year'],
                        'passing_session' => $record['passing_session'],
                        'ssc_group'       => $record['ssc_group'],
                        'created_by'      => $userId,
                    ]);
                }
            }

            // 7. Compulsory Subjects
            $compulsorySubjectIds = $data['compulsory_subjects'] ?? [];
            if (empty($compulsorySubjectIds)) {
                $religion = $data['religion'] ?? 'Islam';
                $nationality = $data['nationality'] ?? 'Pakistani';
                $classLevel = $data['current_class'] ?? '11';
                $ruleSubjects = SubjectAssignmentRule::getSubjectsForStudent($classLevel, $nationality, $religion);
                $compulsorySubjectIds = $ruleSubjects->pluck('id')->toArray();
            }

            foreach ($compulsorySubjectIds as $subjectId) {
                StudentCompulsorySubject::create([
                    'student_id'  => $student->id,
                    'subject_id'  => $subjectId,
                    'class_level' => $data['current_class'] ?? '11',
                    'created_by'  => $userId,
                ]);
            }

            // 8. Elective Subjects
            if (!empty($data['elective_subjects']) && is_array($data['elective_subjects'])) {
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

            return $student;
        });
    }
}
