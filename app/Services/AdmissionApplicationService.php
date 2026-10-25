<?php

namespace App\Services;

use App\Models\AdmissionApplication;
use App\Models\FeeVoucher;
use App\Services\FeeCalculationService;
use App\Services\FileUploadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdmissionApplicationService
{
    public function __construct(
        protected FileUploadService $fileUploadService,
        protected FeeCalculationService $feeCalculationService
    ) {}

    /**
     * Create a new walk-in application entered by clerk.
     */
    public function createWalkInApplication(array $data, $pictureFile = null, ?int $userId = null): AdmissionApplication
    {
        $userId = $userId ?? Auth::id();
        $programType = $data['program_type'] ?? 'Inter';
        $isHe = in_array($programType, ['ADP', 'BS']);

        $picturePath = null;
        if ($pictureFile) {
            $picturePath = $this->fileUploadService->uploadToDisk(
                $pictureFile,
                'application_pictures',
                'public'
            );
        }

        $application = AdmissionApplication::create(array_merge($data, [
            'application_number' => AdmissionApplication::generateApplicationNumber($data['admission_year']),
            'source' => 'walk_in',
            'status' => 'approved',
            'nationality' => $data['nationality'] ?? 'Pakistani',
            'picture' => $picturePath,
            'program_type' => $programType,
            'created_by' => $userId,
        ]));

        if (!$isHe) {
            $application->updateSubjectGroup();
        }

        return $application;
    }

    /**
     * Update an existing application and manage uploaded picture.
     */
    public function updateApplication(AdmissionApplication $application, array $data, $pictureFile = null): AdmissionApplication
    {
        if ($pictureFile) {
            $data['picture'] = $this->fileUploadService->uploadToDisk(
                $pictureFile,
                'application_pictures',
                'public',
                $application->picture
            );
        }

        $application->update($data);
        $application->updateSubjectGroup();

        return $application;
    }

    /**
     * Review an online application (approve or reject).
     */
    public function reviewApplication(AdmissionApplication $application, string $action, ?string $remarks = null, ?int $userId = null): array
    {
        if (!in_array($application->status, ['submitted', 'under_review'])) {
            return [
                'success' => false,
                'error' => 'This application cannot be reviewed in its current status.',
            ];
        }

        $userId = $userId ?? Auth::id();
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $application->update([
            'status' => $newStatus,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_remarks' => $remarks,
        ]);

        if ($newStatus === 'approved') {
            $application->updateSubjectGroup();
        }

        return [
            'success' => true,
            'status' => $newStatus,
            'label' => $action === 'approve' ? 'approved' : 'rejected',
        ];
    }

    /**
     * Generate standard fee voucher for an approved application.
     */
    public function generateVoucher(AdmissionApplication $application): array
    {
        if (!in_array($application->status, ['approved', 'submitted', 'under_review'])) {
            return [
                'success' => false,
                'error' => 'Voucher can only be generated for approved or submitted applications.',
            ];
        }

        if ($application->voucher) {
            return [
                'success' => false,
                'error' => 'A voucher has already been generated for this application.',
            ];
        }

        $voucher = $this->feeCalculationService->generateVoucherForApplication($application);

        $application->update([
            'fee_voucher_number' => $voucher->voucher_number,
            'status' => 'fee_pending',
        ]);

        return [
            'success' => true,
            'voucher' => $voucher,
        ];
    }

    /**
     * Generate custom fee voucher with specific items.
     */
    public function generateCustomVoucher(AdmissionApplication $application, array $rawItems): array
    {
        if (!in_array($application->status, ['approved', 'submitted', 'under_review'])) {
            return [
                'success' => false,
                'error' => 'Voucher can only be generated for approved or submitted applications.',
            ];
        }

        if ($application->voucher) {
            return [
                'success' => false,
                'error' => 'A voucher has already been generated for this application.',
            ];
        }

        $items = collect($rawItems)->map(function ($item) {
            return [
                'name' => $item['name'],
                'amount' => (float)$item['amount'],
                'fee_head_id' => $item['fee_head_id'] ?? null,
                'is_refundable' => isset($item['is_refundable']) ? (bool)$item['is_refundable'] : false,
            ];
        });

        $voucher = $this->feeCalculationService->createVoucher($application, $items);

        $application->update([
            'fee_voucher_number' => $voucher->voucher_number,
            'status' => 'fee_pending',
        ]);

        return [
            'success' => true,
            'voucher' => $voucher,
        ];
    }

    /**
     * Confirm fee payment for an application.
     */
    public function confirmPayment(AdmissionApplication $application, float $paidAmount, string $paymentDate, ?int $userId = null): array
    {
        if ($application->status !== 'fee_pending') {
            return [
                'success' => false,
                'error' => 'Payment can only be confirmed for fee-pending applications.',
            ];
        }

        $userId = $userId ?? Auth::id();

        DB::transaction(function () use ($application, $paidAmount, $paymentDate, $userId) {
            if ($application->voucher) {
                $application->voucher->update([
                    'paid_amount' => $paidAmount,
                    'payment_date' => $paymentDate,
                    'status' => 'paid',
                    'confirmed_by' => $userId,
                ]);
            }

            $application->update([
                'status' => 'fee_paid',
                'fee_confirmed_at' => now(),
            ]);
        });

        return ['success' => true];
    }

    /**
     * Permanently delete an application and associated uploaded documents and voucher.
     */
    public function deleteApplication(AdmissionApplication $application): array
    {
        if ($application->status === 'admitted') {
            return [
                'success' => false,
                'error' => 'Cannot delete an admitted student application.',
            ];
        }

        if ($application->voucher && $application->voucher->status === 'paid') {
            return [
                'success' => false,
                'error' => 'Cannot delete application with a paid voucher.',
            ];
        }

        DB::transaction(function () use ($application) {
            $files = [
                $application->picture,
                $application->ssc_result_card,
                $application->b_form_pic,
                $application->father_cnic_pic,
                $application->paid_voucher_path,
            ];

            foreach ($files as $file) {
                if ($file && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }

            if ($application->voucher) {
                $application->voucher->items()->delete();
                $application->voucher->forceDelete();
            }

            $application->forceDelete();
        });

        return ['success' => true];
    }
/**
     * Submit a new online application with uploaded documents.
     */
    public function createOnlineApplication(array $validated, array $files, string $program): AdmissionApplication
    {
        $settings = \App\Models\AdmissionSetting::getForProgram($program);
        $admissionYear = $settings->admission_year ?? date('Y');
        $isHe = in_array($program, ['ADP', 'BS']);

        $data = $validated;
        if (isset($files['picture'])) {
            $data['picture'] = $files['picture']->store('application_pictures', 'public');
        }
        if (isset($files['ssc_result_card'])) {
            $data['ssc_result_card'] = $files['ssc_result_card']->store('application_docs', 'public');
        }
        if (isset($files['b_form_pic'])) {
            $data['b_form_pic'] = $files['b_form_pic']->store('application_docs', 'public');
        }
        if (isset($files['father_cnic_pic'])) {
            $data['father_cnic_pic'] = $files['father_cnic_pic']->store('application_docs', 'public');
        }

        $data['application_number'] = AdmissionApplication::generateApplicationNumber($admissionYear);
        $data['source'] = 'online';
        $data['status'] = 'submitted';
        $data['admission_year'] = $admissionYear;
        $data['program_type'] = $program;

        $application = AdmissionApplication::create($data);

        if (!$isHe) {
            $application->updateSubjectGroup();
        }

        return $application;
    }

    /**
     * Correct and resubmit a rejected online application.
     */
    public function correctRejectedApplication(AdmissionApplication $application, array $validated, array $files): AdmissionApplication
    {
        $program = $application->program_type ?? 'Inter';
        $isHe = in_array($program, ['ADP', 'BS']);

        $data = $validated;
        if (isset($files['picture'])) {
            $data['picture'] = $files['picture']->store('application_pictures', 'public');
        }
        if (isset($files['ssc_result_card'])) {
            $data['ssc_result_card'] = $files['ssc_result_card']->store('application_docs', 'public');
        }
        if (isset($files['b_form_pic'])) {
            $data['b_form_pic'] = $files['b_form_pic']->store('application_docs', 'public');
        }
        if (isset($files['father_cnic_pic'])) {
            $data['father_cnic_pic'] = $files['father_cnic_pic']->store('application_docs', 'public');
        }

        $data['status'] = 'submitted';
        $data['review_remarks'] = "--- Application Corrected on " . now()->format('d M Y h:i A') . " ---\n" . ($application->review_remarks ?? '');

        $application->update($data);
        if (!$isHe) {
            $application->updateSubjectGroup();
        }

        return $application;
    }

    /**
     * Upload proof of paid admission voucher.
     */
    public function uploadPaidVoucher(AdmissionApplication $application, $voucherFile): string
    {
        $path = $voucherFile->store('paid_vouchers', 'public');
        $application->update(['paid_voucher_path' => $path]);
        return $path;
    }

    /**
     * Get reference data for the public apply form.
     */
    public function getPublicFormData(string $program, ?AdmissionApplication $application = null, ?string $searchTerm = null): array
    {
        $bises = \App\Models\BiseRecord::all();
        $bloodGroups = \App\Models\BloodGroup::all();
        $quotas = \App\Models\Quota::all();
        $settings = \App\Models\AdmissionSetting::getForProgram($program);

        if (in_array($program, ['ADP', 'BS'])) {
            $programs = \App\Models\Program::where('type', $program)->get();
            $hsscGroups = \App\Models\SubjectGroup::whereNull('deleted_at')->orderBy('name')->pluck('name');
            $subjects = collect();
            $subjectGroups = collect();
        } else {
            $programs = collect();
            $hsscGroups = collect();
            $subjects = \App\Models\Subject::where('type', 'Elective')->orderBy('name')->get();
            $subjectGroups = \App\Models\SubjectGroup::with('subjects')->get();
        }

        return compact(
            'application', 'bises', 'subjects', 'bloodGroups', 'quotas',
            'settings', 'searchTerm', 'subjectGroups', 'program', 'programs', 'hsscGroups'
        );
    }
}
