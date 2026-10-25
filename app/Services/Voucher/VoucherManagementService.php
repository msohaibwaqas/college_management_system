<?php

namespace App\Services\Voucher;

use App\Models\FeeVoucher;
use App\Models\FeeVoucherItem;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\InstituteSetting;
use App\Services\FeeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherManagementService
{
    public function __construct(
        protected FeeCalculationService $feeService
    ) {}

    /**
     * Get paginated vouchers and financial summary aggregates.
     */
    public function getIndexData(Request $request): array
    {
        $query = FeeVoucher::with(['student.admissions', 'student.heAdmissions', 'application', 'issuedBy', 'confirmedBy'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('remarks', 'like', '%' . $request->type . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('applicant_name', 'like', "%{$search}%")
                  ->orWhere('voucher_number', 'like', "%{$search}%");
            });
        }

        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100, 500]) ? (int)$request->input('per_page') : 25;
        $vouchers = $query->paginate($perPage)->withQueryString();

        $totalPending = FeeVoucher::where('status', 'pending')->count();
        $totalPaid = FeeVoucher::where('status', 'paid')->count();
        $pendingAmount = FeeVoucher::where('status', 'pending')->sum('amount');
        $collectedAmount = FeeVoucher::where('status', 'paid')->sum('paid_amount');

        return compact(
            'vouchers',
            'totalPending',
            'totalPaid',
            'pendingAmount',
            'collectedAmount',
            'perPage'
        );
    }

    /**
     * Confirm payment for a voucher.
     */
    public function confirmPayment(FeeVoucher $voucher, float $paidAmount, string $paymentDate, ?int $userId = null): void
    {
        $voucher->update([
            'paid_amount' => $paidAmount,
            'payment_date' => $paymentDate,
            'status' => 'paid',
            'confirmed_by' => $userId,
        ]);
    }

    /**
     * Cancel a voucher.
     */
    public function cancelVoucher(FeeVoucher $voucher): void
    {
        $voucher->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Bulk delete pending vouchers.
     */
    public function bulkDeletePending(array $voucherIds): int
    {
        return FeeVoucher::whereIn('id', $voucherIds)
            ->where('status', 'pending')
            ->delete();
    }

    /**
     * Get preview list of Class 12 students needing fee vouchers.
     */
    public function getClass12BulkData(): Collection
    {
        $studentsWithVouchers = FeeVoucher::where('remarks', 'Class 12 Annual Fee')
            ->whereIn('status', ['pending', 'paid'])
            ->pluck('student_id')
            ->toArray();

        $students = Student::whereHas('admissions', function ($q) {
                $q->where('current_class', '12')->whereIn('status', ['Active', 'Admitted']);
            })
            ->whereNotIn('id', $studentsWithVouchers)
            ->with(['electives.subject', 'admissions' => function ($q) {
                $q->whereIn('status', ['Active', 'Admitted']);
            }])
            ->orderBy('first_name')
            ->get();

        return $students->map(function ($student) {
            $subjectIds = $student->electives->pluck('subject_id')->toArray();
            $items = $this->feeService->calculateYearlyItemsForSubjects($subjectIds);
            return [
                'student' => $student,
                'estimated_fee' => $items->sum('amount'),
                'subjects' => $student->electives->pluck('subject.name')->implode(', '),
            ];
        });
    }

    /**
     * Generate Class 12 fee vouchers in batch.
     */
    public function generateClass12BulkVouchers(array $studentIds, ?int $userId = null): int
    {
        return DB::transaction(function () use ($studentIds, $userId) {
            $settings = InstituteSetting::getSettings();
            $generatedCount = 0;

            foreach ($studentIds as $studentId) {
                $student = Student::with(['electives.subject', 'father'])->find($studentId);
                if (!$student) continue;

                $existing = FeeVoucher::where('student_id', $studentId)
                    ->where('remarks', 'Class 12 Annual Fee')
                    ->whereIn('status', ['pending', 'paid'])
                    ->exists();
                if ($existing) continue;

                $subjectIds = $student->electives->pluck('subject_id')->toArray();
                $items = $this->feeService->calculateYearlyItemsForSubjects($subjectIds);

                $voucher = FeeVoucher::create([
                    'voucher_number' => FeeVoucher::generateVoucherNumber(),
                    'student_id' => $student->id,
                    'applicant_name' => $student->full_name,
                    'father_name' => $student->father ? $student->father->name : '',
                    'amount' => $items->sum('amount'),
                    'paid_amount' => 0,
                    'issue_date' => now(),
                    'due_date' => now()->addDays(15),
                    'status' => 'pending',
                    'bank_name' => $settings->bank_name ?? '',
                    'account_number' => $settings->bank_account_number ?? '',
                    'bank_branch' => $settings->bank_branch ?? '',
                    'issued_by' => $userId,
                    'remarks' => 'Class 12 Annual Fee',
                ]);

                foreach ($items as $item) {
                    $voucher->items()->create([
                        'fee_head_id' => $item['fee_head_id'] ?? null,
                        'name' => $item['name'],
                        'amount' => $item['amount'],
                        'is_refundable' => $item['is_refundable'] ?? false,
                    ]);
                }

                $generatedCount++;
            }

            return $generatedCount;
        });
    }

    /**
     * Get preview list of Higher Education students needing semester fee vouchers.
     */
    public function getHeBulkData(): Collection
    {
        $studentsWithVouchers = FeeVoucher::where('remarks', 'like', 'Semester Fee%')
            ->whereIn('status', ['pending', 'paid'])
            ->pluck('student_id')
            ->toArray();

        $students = Student::whereHas('heAdmissions', function ($q) {
                $q->whereIn('status', ['Active', 'Admitted']);
            })
            ->whereNotIn('id', $studentsWithVouchers)
            ->with(['heAdmissions' => function ($q) {
                $q->whereIn('status', ['Active', 'Admitted'])->with('program');
            }])
            ->orderBy('first_name')
            ->get();

        return $students->map(function ($student) {
            $heAdmission = $student->heAdmissions->whereIn('status', ['Active', 'Admitted'])->first();
            $items = $this->feeService->calculateVoucherItemsForHE($heAdmission);
            return (object)[
                'student' => $student,
                'estimated_fee' => $items->sum('amount'),
                'program' => $heAdmission->program->name ?? 'N/A',
                'semester' => $heAdmission->current_semester,
            ];
        });
    }

    /**
     * Generate Higher Education (BS/ADP) semester fee vouchers in batch.
     */
    public function generateHeBulkVouchers(array $studentIds, ?int $userId = null): int
    {
        return DB::transaction(function () use ($studentIds, $userId) {
            $settings = InstituteSetting::getSettings();
            $generatedCount = 0;

            foreach ($studentIds as $studentId) {
                $student = Student::with(['heAdmissions.program', 'father'])->find($studentId);
                if (!$student) continue;

                $heAdmission = $student->heAdmissions->whereIn('status', ['Active', 'Admitted'])->first();
                if (!$heAdmission) continue;

                $existing = FeeVoucher::where('student_id', $studentId)
                    ->where('remarks', "Semester Fee - {$heAdmission->program->name} Sem {$heAdmission->current_semester}")
                    ->whereIn('status', ['pending', 'paid'])
                    ->exists();
                if ($existing) continue;

                $items = $this->feeService->calculateVoucherItemsForHE($heAdmission);

                $voucher = FeeVoucher::create([
                    'voucher_number' => FeeVoucher::generateVoucherNumber(),
                    'student_id' => $student->id,
                    'applicant_name' => $student->full_name,
                    'father_name' => $student->father ? $student->father->name : '',
                    'amount' => $items->sum('amount'),
                    'paid_amount' => 0,
                    'issue_date' => now(),
                    'due_date' => now()->addDays(15),
                    'status' => 'pending',
                    'bank_name' => $settings->bank_name ?? '',
                    'account_number' => $settings->bank_account_number ?? '',
                    'bank_branch' => $settings->bank_branch ?? '',
                    'issued_by' => $userId,
                    'remarks' => "Semester Fee - {$heAdmission->program->name} Sem {$heAdmission->current_semester}",
                ]);

                foreach ($items as $item) {
                    $voucher->items()->create([
                        'fee_head_id' => $item['fee_head_id'] ?? null,
                        'name' => $item['name'],
                        'amount' => $item['amount'],
                        'is_refundable' => $item['is_refundable'] ?? false,
                    ]);
                }

                $generatedCount++;
            }

            return $generatedCount;
        });
    }

    /**
     * Create a student fine voucher.
     */
    public function createFineVoucher(Student $student, float $amount, string $remarks, ?int $userId = null): FeeVoucher
    {
        $settings = InstituteSetting::getSettings();

        $voucher = FeeVoucher::create([
            'voucher_number' => FeeVoucher::generateVoucherNumber(),
            'student_id' => $student->id,
            'applicant_name' => $student->full_name,
            'father_name' => $student->father ? $student->father->name : '',
            'amount' => $amount,
            'paid_amount' => 0,
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'status' => 'pending',
            'bank_name' => $settings->bank_name ?? '',
            'account_number' => $settings->bank_account_number ?? '',
            'bank_branch' => $settings->bank_branch ?? '',
            'issued_by' => $userId,
            'remarks' => 'Fine - ' . $remarks,
        ]);

        $voucher->items()->create([
            'name' => 'Fine - ' . $remarks,
            'amount' => $amount,
            'is_refundable' => false,
        ]);

        return $voucher;
    }

    /**
     * Get bulk printable vouchers and settings.
     */
    public function getBulkPrintData(array $voucherIds): array
    {
        $vouchers = FeeVoucher::with(['items', 'student.admissions', 'student.heAdmissions', 'application'])
            ->whereIn('id', $voucherIds)
            ->get();

        $settings = InstituteSetting::getSettings();

        return compact('vouchers', 'settings');
    }
}
