<?php

namespace App\Actions\Student;

use App\Models\AdmissionSetting;
use App\Models\Contact;
use App\Models\Father;
use App\Models\HeAdmission;
use App\Models\HsscRecord;
use App\Models\Program;
use App\Models\SscRecord;
use App\Models\Student;
use App\Models\StudentAttribute;
use App\Services\FeeCalculationService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateHeStudentAction
{
    public function __construct(
        protected FileUploadService $fileUploadService,
        protected FeeCalculationService $feeService
    ) {}

    /**
     * Execute full HE student admission, sub-records creation, and fee voucher generation.
     */
    public function execute(array $data, ?UploadedFile $picture = null, ?int $userId = null): HeAdmission
    {
        return DB::transaction(function () use ($data, $picture, $userId) {
            $picturePath = null;
            if ($picture) {
                $picturePath = $this->fileUploadService->upload($picture, 'student_pictures');
            }

            // 1. Student Master
            $studentData = [
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'gender'        => $data['gender'],
                'religion'      => $data['religion'],
                'nationality'   => $data['nationality'],
                'cnic'          => $data['student_cnic'] ?? null,
            ];

            if ($picturePath) {
                $studentData['picture'] = $picturePath;
            }

            $student = Student::updateOrCreate(
                ['cnic' => $data['student_cnic'] ?? 'NONE-' . uniqid()],
                array_merge($studentData, ['updated_by' => $userId])
            );

            if ($student->wasRecentlyCreated) {
                $student->created_by = $userId;
                $student->save();
            }

            // 2. Student Attributes
            StudentAttribute::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'blood_group_id' => $data['blood_group_id'] ?? null,
                    'quota_id'       => $data['quota_id'] ?? null,
                    'student_cnic'   => $data['student_cnic'] ?? null,
                    'updated_by'     => $userId,
                    'created_by'     => $student->wasRecentlyCreated ? $userId : null,
                ]
            );

            // 3. Father Information
            Father::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'name'           => $data['father_name'],
                    'cnic'           => $data['father_cnic'] ?? null,
                    'contact'        => $data['father_contact'] ?? null,
                    'occupation'     => $data['father_occupation'] ?? null,
                    'annual_income'  => $data['father_annual_income'] ?? null,
                    'updated_by'     => $userId,
                    'created_by'     => $student->wasRecentlyCreated ? $userId : null,
                ]
            );

            // 4. Contact Information
            Contact::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'student_contact' => $data['student_contact'] ?? null,
                    'whatsapp_no'     => $data['whatsapp_no'] ?? null,
                    'email'           => $data['email'] ?? null,
                    'address'         => $data['address'] ?? null,
                    'picture'         => $picturePath ?? $student->picture,
                    'updated_by'      => $userId,
                    'created_by'      => $student->wasRecentlyCreated ? $userId : null,
                ]
            );

            // 5. SSC Record
            SscRecord::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'bise_id'         => $data['ssc_bise_id'],
                    'roll_no'         => $data['ssc_roll_no'],
                    'reg_no'          => $data['ssc_reg_no'],
                    'school_name'     => $data['ssc_school_name'],
                    'total_mark'      => $data['ssc_total_mark'],
                    'obtained_mark'   => $data['ssc_obtained_mark'],
                    'passing_year'    => $data['ssc_passing_year'],
                    'passing_session' => $data['ssc_passing_session'],
                    'ssc_group'       => $data['ssc_group'],
                    'updated_by'      => $userId,
                    'created_by'      => $student->wasRecentlyCreated ? $userId : null,
                ]
            );

            // 6. HSSC Record
            HsscRecord::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'bise_id'         => $data['hssc_bise_id'],
                    'roll_no'         => $data['hssc_roll_no'],
                    'reg_no'          => $data['hssc_reg_no_academic'],
                    'college_name'    => $data['hssc_college_name'],
                    'total_mark'      => $data['hssc_total_mark'],
                    'obtained_mark'   => $data['hssc_obtained_mark'],
                    'passing_year'    => $data['hssc_passing_year'],
                    'passing_session' => $data['hssc_passing_session'],
                    'hssc_group'      => $data['hssc_group'],
                    'updated_by'      => $userId,
                    'created_by'      => $student->wasRecentlyCreated ? $userId : null,
                ]
            );

            // 7. HE Admission Record
            $program = Program::find($data['program_id']);
            $settings = $program ? AdmissionSetting::getForProgram($program->type) : null;

            $heAdmission = HeAdmission::create([
                'student_id'       => $student->id,
                'program_id'       => $data['program_id'],
                'roll_no'          => null,
                'admission_year'   => $settings->admission_year ?? date('Y'),
                'current_semester' => 1,
                'status'           => 'Active',
                'created_by'       => $userId,
                'updated_by'       => $userId,
            ]);

            // 8. Generate Fee Voucher
            $voucherItems = $this->feeService->calculateVoucherItemsForHE($heAdmission);
            $this->feeService->createVoucher($student, $voucherItems);

            return $heAdmission;
        });
    }
}
