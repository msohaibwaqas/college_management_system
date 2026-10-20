<?php

namespace App\Http\Controllers;

use App\Models\FeeVoucher;
use App\Models\FeeVoucherItem;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\InstituteSetting;
use App\Services\FeeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    /**
     * Display a listing of all vouchers with filters.
     */
    public function index(Request $request)
    {
        $query = FeeVoucher::with(['student.admissions', 'student.heAdmissions', 'application', 'issuedBy', 'confirmedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type (remarks)
        if ($request->filled('type')) {
            $query->where('remarks', 'like', '%' . $request->type . '%');
        }

        // Search by student name or voucher number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('applicant_name', 'like', "%{$search}%")
                  ->orWhere('voucher_number', 'like', "%{$search}%");
            });
        }

        $perPage = in_array($request->input('per_page'), [10, 25, 50, 100, 500]) ? (int)$request->input('per_page') : 25;
        $vouchers = $query->paginate($perPage)->withQueryString();

        // Stats
        $totalPending = FeeVoucher::where('status', 'pending')->count();
        $totalPaid = FeeVoucher::where('status', 'paid')->count();
        $pendingAmount = FeeVoucher::where('status', 'pending')->sum('amount');
        $collectedAmount = FeeVoucher::where('status', 'paid')->sum('paid_amount');

        return view('vouchers.index', compact(
            'vouchers', 'totalPending', 'totalPaid', 'pendingAmount', 'collectedAmount', 'perPage'
        ));
    }

    /**
     * Display a single voucher with items.
     */
    public function show(FeeVoucher $voucher)
    {
        $voucher->load(['items', 'student', 'application', 'issuedBy', 'confirmedBy']);
        return view('vouchers.show', compact('voucher'));
    }

    /**
     * Confirm payment for a voucher.
     */
    public function confirmPayment(Request $request, FeeVoucher $voucher)
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        try {
            $voucher->update([
                'paid_amount' => $request->paid_amount,
                'payment_date' => $request->payment_date,
                'status' => 'paid',
                'confirmed_by' => Auth::id(),
            ]);

            return back()->with('success', 'Payment for voucher ' . $voucher->voucher_number . ' confirmed successfully.');
        } catch (\Exception $e) {
            Log::error('Voucher payment confirmation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to confirm payment: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a voucher.
     */
    public function cancelVoucher(FeeVoucher $voucher)
    {
        try {
            $voucher->update([
                'status' => 'cancelled',
            ]);

            return back()->with('success', 'Voucher ' . $voucher->voucher_number . ' has been cancelled.');
        } catch (\Exception $e) {
            Log::error('Voucher cancellation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to cancel voucher: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete pending vouchers.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'voucher_ids' => 'required|array|min:1',
            'voucher_ids.*' => 'required|integer|exists:fee_vouchers,id',
        ]);

        try {
            $deleted = FeeVoucher::whereIn('id', $request->voucher_ids)
                ->where('status', 'pending')
                ->delete();

            return back()->with('success', "Successfully deleted {$deleted} pending voucher(s).");
        } catch (\Exception $e) {
            Log::error('Bulk voucher deletion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete vouchers: ' . $e->getMessage());
        }
    }

    /**
     * Show the bulk Class 12 voucher generation form.
     */
    public function bulkGenerateClass12Form()
    {
        if (Auth::user()?->isBsRole()) {
            abort(403, 'Unauthorized. BS/ADP roles cannot generate HSSC bulk vouchers.');
        }

        // Get all active Class 12 students who don't already have a Class 12 fee voucher
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

        // Calculate estimated fee for preview (yearly only for Class 12)
        $feeService = new FeeCalculationService();
        $studentsData = $students->map(function ($student) use ($feeService) {
            $subjectIds = $student->electives->pluck('subject_id')->toArray();
            $items = $feeService->calculateYearlyItemsForSubjects($subjectIds);
            return [
                'student' => $student,
                'estimated_fee' => $items->sum('amount'),
                'subjects' => $student->electives->pluck('subject.name')->implode(', '),
            ];
        });

        return view('vouchers.bulk_class12', compact('studentsData'));
    }

    /**
     * Process bulk Class 12 voucher generation.
     */
    public function bulkGenerateClass12(Request $request)
    {
        if (Auth::user()?->isBsRole()) {
            abort(403, 'Unauthorized. BS/ADP roles cannot generate HSSC bulk vouchers.');
        }

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        try {
            DB::beginTransaction();

            $feeService = new FeeCalculationService();
            $settings = InstituteSetting::getSettings();
            $generatedCount = 0;

            foreach ($request->student_ids as $studentId) {
                $student = Student::with(['electives.subject', 'father'])->find($studentId);
                if (!$student) continue;

                // Check if already has a Class 12 voucher
                $existing = FeeVoucher::where('student_id', $studentId)
                    ->where('remarks', 'Class 12 Annual Fee')
                    ->whereIn('status', ['pending', 'paid'])
                    ->exists();
                if ($existing) continue;

                $subjectIds = $student->electives->pluck('subject_id')->toArray();
                $items = $feeService->calculateYearlyItemsForSubjects($subjectIds);

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
                    'issued_by' => Auth::id(),
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

            DB::commit();

            return redirect()->route('vouchers.index')
                ->with('success', "Successfully generated {$generatedCount} Class 12 fee voucher(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Class 12 voucher generation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate vouchers: ' . $e->getMessage());
        }
    }

    /**
     * Show the bulk BS/ADP voucher generation form.
     */
    public function bulkGenerateHEForm()
    {
        if (Auth::user()?->isHsscRole()) {
            abort(403, 'Unauthorized. HSSC roles cannot generate BS/ADP bulk vouchers.');
        }

        // Get all active HE students who don't already have a semester fee voucher for current semester
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

        $feeService = new FeeCalculationService();
        $studentsData = $students->map(function ($student) use ($feeService) {
            $heAdmission = $student->heAdmissions->whereIn('status', ['Active', 'Admitted'])->first();
            $items = $feeService->calculateVoucherItemsForHE($heAdmission);
            return (object)[
                'student' => $student,
                'estimated_fee' => $items->sum('amount'),
                'program' => $heAdmission->program->name ?? 'N/A',
                'semester' => $heAdmission->current_semester,
            ];
        });

        return view('vouchers.bulk_he', compact('studentsData'));
    }

    /**
     * Process bulk BS/ADP voucher generation.
     */
    public function bulkGenerateHE(Request $request)
    {
        if (Auth::user()?->isHsscRole()) {
            abort(403, 'Unauthorized. HSSC roles cannot generate BS/ADP bulk vouchers.');
        }

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        try {
            DB::beginTransaction();

            $feeService = new FeeCalculationService();
            $settings = InstituteSetting::getSettings();
            $generatedCount = 0;

            foreach ($request->student_ids as $studentId) {
                $student = Student::with(['heAdmissions.program', 'father'])->find($studentId);
                if (!$student) continue;

                $heAdmission = $student->heAdmissions->whereIn('status', ['Active', 'Admitted'])->first();
                if (!$heAdmission) continue;

                // Check if already has a Semester Fee voucher
                $existing = FeeVoucher::where('student_id', $studentId)
                    ->where('remarks', "Semester Fee - {$heAdmission->program->name} Sem {$heAdmission->current_semester}")
                    ->whereIn('status', ['pending', 'paid'])
                    ->exists();
                if ($existing) continue;

                $items = $feeService->calculateVoucherItemsForHE($heAdmission);

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
                    'issued_by' => Auth::id(),
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

            DB::commit();

            return redirect()->route('vouchers.index')
                ->with('success', "Successfully generated {$generatedCount} BS/ADP fee voucher(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk HE voucher generation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate vouchers: ' . $e->getMessage());
        }
    }

    /**
     * Show the fine voucher creation form.
     */
    public function createFineForm(Student $student)
    {
        $student->load(['admissions', 'father']);
        return view('vouchers.fine_create', compact('student'));
    }

    /**
     * Store a fine voucher.
     */
    public function storeFine(Request $request, Student $student)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50|max:2000',
            'remarks' => 'required|string|max:255',
        ]);

        try {
            $settings = InstituteSetting::getSettings();

            $voucher = FeeVoucher::create([
                'voucher_number' => FeeVoucher::generateVoucherNumber(),
                'student_id' => $student->id,
                'applicant_name' => $student->full_name,
                'father_name' => $student->father ? $student->father->name : '',
                'amount' => $request->amount,
                'paid_amount' => 0,
                'issue_date' => now(),
                'due_date' => now()->addDays(15),
                'status' => 'pending',
                'bank_name' => $settings->bank_name ?? '',
                'account_number' => $settings->bank_account_number ?? '',
                'bank_branch' => $settings->bank_branch ?? '',
                'issued_by' => Auth::id(),
                'remarks' => 'Fine - ' . $request->remarks,
            ]);

            $voucher->items()->create([
                'name' => 'Fine - ' . $request->remarks,
                'amount' => $request->amount,
                'is_refundable' => false,
            ]);

            return redirect()->route('students.show', $student)
                ->with('success', "Fine voucher ({$voucher->voucher_number}) for Rs. {$request->amount} has been generated successfully.");
        } catch (\Exception $e) {
            Log::error('Fine voucher generation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate fine voucher: ' . $e->getMessage());
        }
    }

    /**
     * Bulk download multiple vouchers in a single printable file.
     */
    public function bulkDownload(Request $request)
    {
        $request->validate([
            'voucher_ids' => 'required|array|min:1',
            'voucher_ids.*' => 'required|integer|exists:fee_vouchers,id',
        ]);

        $vouchers = FeeVoucher::with(['items', 'student.admissions', 'student.heAdmissions', 'application'])
            ->whereIn('id', $request->voucher_ids)
            ->get();

        if ($vouchers->isEmpty()) {
            return back()->with('error', 'No vouchers found for the selected IDs.');
        }

        $settings = InstituteSetting::getSettings();

        return view('pdfs.fee-vouchers-bulk', compact('vouchers', 'settings'));
    }
}
