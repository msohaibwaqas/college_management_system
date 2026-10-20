<?php

namespace App\Actions\Admission;

use App\Models\AdmissionApplication;
use App\Models\Contact;
use App\Models\Father;
use App\Models\HeAdmission;
use App\Models\HsscRecord;
use App\Models\SscRecord;
use App\Models\Student;
use App\Models\StudentAttribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * AdmitHeApplicantAction encapsulates the multi-table database transaction
 * that transitions a fee-paid ADP/BS applicant into active student and
 * HE admission records.
 */
class AdmitHeApplicantAction
{
    /**
     * Execute the admission of an HE applicant.
     *
     * @param AdmissionApplication $application
     * @return HeAdmission
     */
    public function execute(AdmissionApplication $application): HeAdmission
    {
        return DB::transaction(function () use ($application) {
            $userId = Auth::id();

            // 1. Create or find student
            $student = Student::updateOrCreate(
                ['cnic' => $application->cnic ?? 'APP-' . $application->id],
                [
                    'first_name' => $application->first_name,
                    'last_name' => $application->last_name,
                    'date_of_birth' => $application->date_of_birth,
                    'gender' => $application->gender,
                    'religion' => $application->religion,
                    'nationality' => $application->nationality,
                    'cnic' => $application->cnic,
                    'picture' => $application->picture,
                    'updated_by' => $userId,
                ]
            );

            if ($student->wasRecentlyCreated) {
                $student->created_by = $userId;
                $student->save();
            }

            // 2. Student Attributes
            StudentAttribute::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'blood_group_id' => $application->blood_group_id,
                    'quota_id' => $application->quota_id,
                    'student_cnic' => $application->cnic,
                    'updated_by' => $userId,
                ]
            );

            // 3. Father
            Father::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'name' => $application->father_name,
                    'cnic' => $application->father_cnic,
                    'contact' => $application->father_contact,
                    'occupation' => $application->father_occupation,
                    'annual_income' => $application->father_annual_income,
                    'updated_by' => $userId,
                ]
            );

            // 4. Contact
            Contact::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'student_contact' => $application->student_contact,
                    'whatsapp_no' => $application->whatsapp_no,
                    'email' => $application->email,
                    'address' => $application->address,
                    'picture' => $application->picture,
                    'updated_by' => $userId,
                ]
            );

            // 5. SSC Record
            SscRecord::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'bise_id' => $application->bise_id,
                    'roll_no' => $application->ssc_roll_no,
                    'reg_no' => $application->ssc_reg_no,
                    'school_name' => $application->ssc_school_name,
                    'total_mark' => $application->ssc_total_marks,
                    'obtained_mark' => $application->ssc_obtained_marks,
                    'passing_year' => $application->ssc_passing_year,
                    'passing_session' => $application->ssc_passing_session,
                    'ssc_group' => $application->ssc_group,
                    'updated_by' => $userId,
                ]
            );

            // 6. HSSC Record
            HsscRecord::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'bise_id' => $application->hssc_bise_id,
                    'roll_no' => $application->hssc_roll_no,
                    'reg_no' => $application->hssc_reg_no,
                    'college_name' => $application->hssc_college_name,
                    'total_mark' => $application->hssc_total_marks,
                    'obtained_mark' => $application->hssc_obtained_marks,
                    'passing_year' => $application->hssc_passing_year,
                    'passing_session' => $application->hssc_passing_session,
                    'hssc_group' => $application->hssc_group,
                    'updated_by' => $userId,
                ]
            );

            // 7. HE Admission (Semester 1)
            $heAdmission = HeAdmission::create([
                'student_id' => $student->id,
                'program_id' => $application->program_id,
                'roll_no' => null,
                'admission_year' => $application->admission_year,
                'current_semester' => 1,
                'status' => 'Admitted',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // 8. Mark application as admitted
            $application->update([
                'status' => 'admitted',
                'assigned_roll_no' => null,
            ]);

            return $heAdmission;
        });
    }
}
