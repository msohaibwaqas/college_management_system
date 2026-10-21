<?php

namespace App\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface PrintableVoucherInterface
 * 
 * Standard contract for any domain entity that can be rendered as a 3-part bank challan voucher
 * (e.g., FeeVoucher, StudentFine, SecurityDeposit, SubjectChange).
 */
interface PrintableVoucherInterface
{
    public function getVoucherNumber(): string;

    public function getTitle(): string;

    public function getRemarks(): ?string;

    public function getAcademicYear(): string;

    public function getIssueDate(): ?Carbon;

    public function getDueDate(): ?Carbon;

    public function getBankName(): string;

    public function getBankBranch(): string;

    public function getAccountNumber(): string;

    public function getStudentName(): string;

    public function getCollegeRollNo(): ?string;

    public function getCnic(): string;

    public function getFatherName(): ?string;

    public function getClassOrProgram(): string;

    /**
     * Get line items for fee / fine breakdown.
     * 
     * @return Collection<int, \App\DTOs\VoucherItemDto>
     */
    public function getItems(): Collection;

    public function getTotalAmount(): float;

    public function getAmountInWords(): string;

    public function getInstructions(): array;

    public function getStatus(): string;

    public function isPaid(): bool;

    public function getStatusStamp(): ?string;
}
