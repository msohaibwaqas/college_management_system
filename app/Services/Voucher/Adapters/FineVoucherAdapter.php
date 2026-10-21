<?php

namespace App\Services\Voucher\Adapters;

use App\Contracts\PrintableVoucherInterface;
use App\DTOs\VoucherItemDto;
use App\Models\StudentFine;
use App\Models\InstituteSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class FineVoucherAdapter
 * 
 * Adapts StudentFine Eloquent model to the standard PrintableVoucherInterface,
 * guaranteeing 100% visual and structural identity with fee vouchers.
 */
class FineVoucherAdapter implements PrintableVoucherInterface
{
    protected StudentFine $fine;
    protected InstituteSetting $settings;

    public function __construct(StudentFine $fine, ?InstituteSetting $settings = null)
    {
        $this->fine = $fine;
        $this->settings = $settings ?: InstituteSetting::getSettings();
    }

    public function getModel(): StudentFine
    {
        return $this->fine;
    }

    public function getVoucherNumber(): string
    {
        return 'FIN-' . str_pad($this->fine->id, 5, '0', STR_PAD_LEFT);
    }

    public function getTitle(): string
    {
        return $this->settings->institute_title ?? 'Government College';
    }

    public function getRemarks(): ?string
    {
        return $this->fine->formatted_type . ' Voucher';
    }

    public function getAcademicYear(): string
    {
        return (string) ($this->fine->academic_year ?? date('Y'));
    }

    public function getIssueDate(): ?Carbon
    {
        return $this->fine->created_at ? Carbon::parse($this->fine->created_at) : now();
    }

    public function getDueDate(): ?Carbon
    {
        return $this->fine->created_at ? Carbon::parse($this->fine->created_at)->copy()->addDays(15) : now()->addDays(15);
    }

    public function getBankName(): string
    {
        return $this->settings->bank_name ?: 'Bank of Punjab Chishtian';
    }

    public function getBankBranch(): string
    {
        return $this->settings->bank_branch ?: '0095';
    }

    public function getAccountNumber(): string
    {
        return $this->settings->bank_account_number ?: '6510163948000028';
    }

    public function getStudentName(): string
    {
        return $this->fine->student?->full_name 
            ?: ($this->fine->admission?->student?->full_name ?? '—');
    }

    public function getCollegeRollNo(): ?string
    {
        return $this->fine->admission?->college_roll_no 
            ?: ($this->fine->student?->admissions?->first()?->college_roll_no 
            ?: ($this->fine->student?->heAdmissions?->first()?->roll_no ?? null));
    }

    public function getCnic(): string
    {
        $student = $this->fine->student ?: $this->fine->admission?->student;
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
            // Check previous vouchers as fallback
            $voucherCnic = $student->vouchers()->whereNotNull('cnic')->where('cnic', '!=', '')->value('cnic');
            if (!empty($voucherCnic)) {
                return $voucherCnic;
            }
        }

        return '—';
    }

    public function getFatherName(): ?string
    {
        return $this->fine->student?->father?->name 
            ?: ($this->fine->admission?->student?->father?->name ?? null);
    }

    public function getClassOrProgram(): string
    {
        $student = $this->fine->student ?: $this->fine->admission?->student;
        if ($student && !empty($student->subject_group)) {
            return $student->subject_group;
        }

        if ($this->fine->admission && !empty($this->fine->admission->current_class)) {
            return $this->fine->admission->current_class . 'th Class';
        }

        $firstAdm = $student?->admissions?->first();
        if ($firstAdm && !empty($firstAdm->current_class)) {
            return $firstAdm->current_class . 'th Class';
        }

        $firstHe = $student?->heAdmissions?->first();
        if ($firstHe && $firstHe->program) {
            return $firstHe->program->name;
        }

        return '—';
    }

    public function getItems(): Collection
    {
        $items = collect();

        // Base fine charge item
        $items->push(new VoucherItemDto(
            name: $this->fine->formatted_type,
            amount: (float) $this->fine->total_amount,
            detail: $this->fine->reason
        ));

        // Waiver adjustment if present
        if ($this->fine->waived_amount > 0) {
            $items->push(new VoucherItemDto(
                name: 'Authorized Concession / Waiver',
                amount: -(float) $this->fine->waived_amount,
                detail: $this->fine->waiver_reason ?: null
            ));
        }

        // Partial payment adjustment if present
        if ($this->fine->paid_amount > 0) {
            $items->push(new VoucherItemDto(
                name: 'Amount Paid to Date',
                amount: -(float) $this->fine->paid_amount,
                detail: $this->fine->payment_reference ? 'Ref: ' . $this->fine->payment_reference : null
            ));
        }

        return $items;
    }

    public function getTotalAmount(): float
    {
        if ($this->fine->balance_amount > 0) {
            return (float) $this->fine->balance_amount;
        }

        if ($this->isPaid()) {
            return 0.0;
        }

        return (float) $this->fine->total_amount;
    }

    public function getAmountInWords(): string
    {
        $amount = $this->getTotalAmount();
        if ($amount <= 0 && $this->isPaid()) {
            return 'Zero (Paid in Full)';
        }

        return numberToWords($amount) . ' Rupees Only';
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
        return (string) ($this->fine->status ?? 'unpaid');
    }

    public function isPaid(): bool
    {
        return in_array(strtolower($this->getStatus()), ['paid']);
    }

    public function getStatusStamp(): ?string
    {
        if ($this->isPaid()) {
            return 'PAID IN FULL';
        }

        if ($this->getStatus() === 'waived') {
            return 'WAIVED BY PRINCIPAL';
        }

        return null;
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
            default            => $this->fine->{$name} ?? null,
        };
    }
}
