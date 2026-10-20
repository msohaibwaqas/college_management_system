<?php

namespace App\Actions\Staff;

use App\Models\Designation;
use App\Models\Staff;
use App\Models\StaffAcademicQualification;
use App\Models\StaffPromotionHistory;
use App\Models\StaffServiceHistory;
use App\Models\StaffSubjectSpecialization;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateStaffAction
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Execute staff update within a database transaction.
     */
    public function execute(Staff $staff, array $data, ?UploadedFile $picture = null, ?int $userId = null): Staff
    {
        return DB::transaction(function () use ($staff, $data, $picture, $userId) {
            // Auto-resolve designation_id based on designation string
            $designationId = null;
            if (!empty($data['designation'])) {
                $designation = Designation::where('name', $data['designation'])->first();
                if ($designation) {
                    $designationId = $designation->id;
                }
            }

            $updateData = [
                'staff_id'                     => $data['staff_id'],
                'first_name'                   => $data['first_name'],
                'last_name'                    => $data['last_name'] ?? null,
                'father_name'                  => $data['father_name'],
                'cnic'                         => $data['cnic'],
                'contact_number'               => $data['contact_number'] ?? null,
                'whatsapp_number'              => $data['whatsapp_number'] ?? null,
                'address'                      => $data['address'] ?? null,
                'date_of_birth'                => $data['date_of_birth'] ?? $staff->date_of_birth,
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
                'updated_by'                   => $userId,
            ];

            // Handle picture update
            if ($picture) {
                $updateData['picture'] = $this->fileUploadService->update(
                    $picture,
                    $staff->picture,
                    'staff_pictures'
                );
            }

            $staff->update($updateData);

            // Sync user account status
            if ($staff->user) {
                $userStatus = $staff->status === 'active' ? 'active' : 'suspended';
                if ($staff->user->status !== $userStatus) {
                    $staff->user->update(['status' => $userStatus]);
                }
            }

            // Update subject specializations
            if ($data['category'] === 'teaching') {
                StaffSubjectSpecialization::where('staff_id', $staff->id)->forceDelete();

                if (!empty($data['subject_specializations']) && is_array($data['subject_specializations'])) {
                    foreach ($data['subject_specializations'] as $subjectId) {
                        StaffSubjectSpecialization::create([
                            'staff_id'   => $staff->id,
                            'subject_id' => $subjectId,
                            'created_by' => $userId,
                        ]);
                    }
                }

                // Sync BS subject specializations
                $staff->bsSubjects()->sync($data['bs_subject_specializations'] ?? []);
            } else {
                $staff->bsSubjects()->detach();
            }

            // Update Academic Qualifications
            if (isset($data['qualifications']) && is_array($data['qualifications'])) {
                StaffAcademicQualification::where('staff_id', $staff->id)->forceDelete();
                foreach ($data['qualifications'] as $qual) {
                    if (!empty($qual['degree_level'])) {
                        StaffAcademicQualification::create(array_merge($qual, [
                            'staff_id'   => $staff->id,
                            'created_by' => $userId,
                        ]));
                    }
                }
            }

            // Update Promotion History
            if (isset($data['promotions']) && is_array($data['promotions'])) {
                StaffPromotionHistory::where('staff_id', $staff->id)->forceDelete();
                foreach ($data['promotions'] as $index => $promo) {
                    if (!empty($promo['new_designation'])) {
                        StaffPromotionHistory::create(array_merge($promo, [
                            'staff_id'             => $staff->id,
                            'promotion_number'     => $index + 1,
                            'previous_designation' => $promo['previous_designation'] ?: 'N/A',
                            'previous_bps'         => $promo['previous_bps'] ?: 0,
                            'promotion_date'       => $promo['promotion_date'] ?: now(),
                            'created_by'           => $userId,
                        ]));
                    }
                }
            }

            // Update Service History
            if (isset($data['service_history']) && is_array($data['service_history'])) {
                StaffServiceHistory::where('staff_id', $staff->id)->forceDelete();
                foreach ($data['service_history'] as $service) {
                    if (!empty($service['institution_name'])) {
                        StaffServiceHistory::create(array_merge($service, [
                            'staff_id'   => $staff->id,
                            'created_by' => $userId,
                        ]));
                    }
                }
            }

            return $staff;
        });
    }
}
