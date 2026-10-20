<?php

namespace App\Actions\Staff;

use App\Models\Designation;
use App\Models\Staff;
use App\Models\StaffSubjectSpecialization;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateStaffAction
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Execute staff creation within a database transaction.
     */
    public function execute(array $data, ?UploadedFile $picture = null, ?int $userId = null): Staff
    {
        return DB::transaction(function () use ($data, $picture, $userId) {
            // Auto-resolve designation_id based on designation string
            $designationId = null;
            if (!empty($data['designation'])) {
                $designation = Designation::where('name', $data['designation'])->first();
                if ($designation) {
                    $designationId = $designation->id;
                }
            }

            $picturePath = null;
            if ($picture) {
                $picturePath = $this->fileUploadService->upload($picture, 'staff_pictures');
            }

            $staff = Staff::create([
                'staff_id'                     => $data['staff_id'],
                'first_name'                   => $data['first_name'],
                'last_name'                    => $data['last_name'] ?? null,
                'father_name'                  => $data['father_name'],
                'cnic'                         => $data['cnic'],
                'contact_number'               => $data['contact_number'] ?? null,
                'whatsapp_number'              => $data['whatsapp_number'] ?? null,
                'address'                      => $data['address'] ?? null,
                'date_of_birth'                => $data['date_of_birth'],
                'blood_group_id'               => $data['blood_group_id'] ?? null,
                'email'                        => $data['email'] ?? null,
                'category'                     => $data['category'],
                'highest_qualification'        => $data['highest_qualification'] ?? null,
                'designation'                  => $data['designation'],
                'designation_id'               => $designationId,
                'bps'                          => $data['bps'],
                'entry_date_govt_service'      => $data['entry_date_govt_service'] ?? null,
                'hed_joining_date'             => $data['hed_joining_date'] ?? null,
                'current_college_joining_date' => $data['current_college_joining_date'] ?? null,
                'status'                       => $data['status'],
                'gender'                       => $data['gender'],
                'picture'                      => $picturePath,
                'created_by'                   => $userId,
            ]);

            // Add subject specializations if teaching staff
            if ($data['category'] === 'teaching') {
                if (!empty($data['subject_specializations']) && is_array($data['subject_specializations'])) {
                    foreach ($data['subject_specializations'] as $subjectId) {
                        StaffSubjectSpecialization::create([
                            'staff_id'   => $staff->id,
                            'subject_id' => $subjectId,
                            'created_by' => $userId,
                        ]);
                    }
                }

                // Add BS subject specializations
                if (!empty($data['bs_subject_specializations']) && is_array($data['bs_subject_specializations'])) {
                    $staff->bsSubjects()->sync($data['bs_subject_specializations']);
                }
            }

            return $staff;
        });
    }
}
