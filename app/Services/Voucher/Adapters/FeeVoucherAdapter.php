<?php

namespace App\Services\Voucher\Adapters;

use App\Contracts\PrintableVoucherInterface;
use App\DTOs\VoucherItemDto;
use App\Models\FeeVoucher;
use App\Models\InstituteSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class FeeVoucherAdapter
 * 
 * Adapts FeeVoucher Eloquent model to the PrintableVoucherInterface.
 */
class FeeVoucherAdapter implements PrintableVoucherInterface
{
    protected FeeVoucher $voucher;
    protected InstituteSetting $settings;

    public function __construct(FeeVoucher $voucher, ?InstituteSetting $settings = null)
    {
        $this->voucher = $voucher;
        $this->settings = $settings ?: InstituteSetting::getSettings();
    }

    public function getModel(): FeeVoucher
    {
        return $this->voucher;
    }

    public function getVoucherNumber(): string
    {
        return (string) $this->voucher->voucher_number;
    }

    public function getTitle(): string
    {
        return $this->settings->institute_title ?? 'Government College';
    }

    public function getRemarks(): ?string
    {
        return $this->voucher->remarks ?? 'Admission Fee Voucher';
    }

    public function getAcademicYear(): string
    {
        return (string) ($this->voucher->admission_year ?? date('Y'));
    }

    public function getIssueDate(): ?Carbon
    {
        return $this->voucher->issue_date ? Carbon::parse($this->voucher->issue_date) : now();
    }

    public function getDueDate(): ?Carbon
    {
        return $this->voucher->due_date ? Carbon::parse($this->voucher->due_date) : now()->addDays(15);
    }

    public function getBankName(): string
    {
        return $this->voucher->bank_name ?: ($this->settings->bank_name ?: 'National Bank of Pakistan');
    }

    public function getBankBranch(): string
    {
        return $this->voucher->bank_branch ?: ($this->settings->bank_branch ?: 'College Branch');
    }

    public function getAccountNumber(): string
    {
        return $this->voucher->account_number ?: ($this->settings->bank_account_number ?: '10492-8');
    }

    public function getStudentName(): string
    {
        return $this->voucher->applicant_name ?: ($this->voucher->student?->full_name ?? '—');
    }

    public function getCollegeRollNo(): ?string
    {
        return $this->voucher->college_roll_no 
            ?: ($this->voucher->student?->admissions?->first()?->college_roll_no 
            ?: ($this->voucher->student?->heAdmissions?->first()?->roll_no ?? null));
    }

    public function getCnic(): string
    {
        if (!empty($this->voucher->cnic)) {
            return $this->voucher->cnic;
        }

        $student = $this->voucher->student;
        if ($student) {
            if (!empty($student->cnic)) {
                return $student->cnic;
            }
            if (!empty($student->student_cnic)) {
                return $student->student_cnic;
            }
            if ($student->attribute && !empty($student->attribute->student_cnic)) {
                return $student->attribute->student_cnic;
            }
            if ($student->contact && !empty($student->contact->student_cnic)) {
                return $student->contact->student_cnic;
            }
        }

        return '—';
    }

    public function getFatherName(): ?string
    {
        return $this->voucher->student?->father?->name;
    }

    public function getClassOrProgram(): string
    {
        if (!empty($this->voucher->subject_group)) {
            return $this->voucher->subject_group;
        }

        $student = $this->voucher->student;
        if ($student) {
            if (!empty($student->subject_group)) {
                return $student->subject_group;
            }
            $firstAdm = $student->admissions?->first();
            if ($firstAdm && !empty($firstAdm->current_class)) {
                return $firstAdm->current_class . 'th Class';
            }
            $firstHe = $student->heAdmissions?->first();
            if ($firstHe && $firstHe->program) {
                return $firstHe->program->name;
            }
        }

        return '—';
    }

    public function getItems(): Collection
    {
        return $this->voucher->items->map(function ($item) {
            return new VoucherItemDto(
                name: $item->name,
                amount: (float) $item->amount,
                detail: null,
                isRefundable: (bool) $item->is_refundable
            );
        });
    }

    public function getTotalAmount(): float
    {
        return (float) $this->voucher->amount;
    }

    public function getAmountInWords(): string
    {
        return numberToWords($this->getTotalAmount()) . ' Rupees Only';
    }

    public function getInstructions(): array
    {
        return [
            'Pay fee on or before due date.',
            'Fee once paid is non-refundable.',
            'Please keep this copy for your record.',
        ];
    }

    public function getStatus(): string
    {
        return (string) ($this->voucher->status ?? 'unpaid');
    }

    public function isPaid(): bool
    {
        return in_array(strtolower($this->getStatus()), ['paid', 'confirmed']);
    }

    public function getStatusStamp(): ?string
    {
        return $this->isPaid() ? 'PAID IN FULL' : null;
    }

    /**
     * Magic getter for backwards compatibility with legacy Blade views
     */
    public function __get(string $name)
    {
        return match ($name) {
            'voucher_number'   => $this->getVoucherNumber(),
            'remarks'          => $this->getRemarks(),
            'admission_year'   => $this->getAcademicYear(),
            'issue_date'       => $this->getIssueDate(),
            'due_date'         => $this->getDueDate(),
            'bank_name'        => $this->getBankName(),
            'bank_branch'      => $this->getBankBranch(),
            'account_number'   => $this->getAccountNumber(),
            'applicant_name'   => $this->getStudentName(),
            'college_roll_no'  => $this->getCollegeRollNo(),
            'cnic'             => $this->getCnic(),
            'subject_group'    => $this->getClassOrProgram(),
            'items'            => $this->getItems(),
            'amount'           => $this->getTotalAmount(),
            'status'           => $this->getStatus(),
            default            => $this->voucher->{$name} ?? null,
        };
    }
}
