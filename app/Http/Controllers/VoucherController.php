<?php

namespace App\Http\Controllers;

use App\Models\FeeVoucher;
use App\Models\Student;
use App\Services\Voucher\VoucherManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherManagementService $voucherService
    ) {}

    /**
     * Display a listing of all vouchers with filters.
     */
    public function index(Request $request)
    {
        $data = $this->voucherService->getIndexData($request);
        return view('vouchers.index', $data);
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
            $this->voucherService->confirmPayment(
                $voucher,
                (float) $request->paid_amount,
                $request->payment_date,
                Auth::id()
            );

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
            $this->voucherService->cancelVoucher($voucher);
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
            $deleted = $this->voucherService->bulkDeletePending($request->voucher_ids);
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

        $studentsData = $this->voucherService->getClass12BulkData();
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
            $generatedCount = $this->voucherService->generateClass12BulkVouchers(
                $request->student_ids,
                Auth::id()
            );

            return redirect()->route('vouchers.index')
                ->with('success', "Successfully generated {$generatedCount} Class 12 fee voucher(s).");
        } catch (\Exception $e) {
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

        $studentsData = $this->voucherService->getHeBulkData();
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
            $generatedCount = $this->voucherService->generateHeBulkVouchers(
                $request->student_ids,
                Auth::id()
            );

            return redirect()->route('vouchers.index')
                ->with('success', "Successfully generated {$generatedCount} BS/ADP fee voucher(s).");
        } catch (\Exception $e) {
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
            $voucher = $this->voucherService->createFineVoucher(
                $student,
                (float) $request->amount,
                $request->remarks,
                Auth::id()
            );

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

        $data = $this->voucherService->getBulkPrintData($request->voucher_ids);

        if ($data['vouchers']->isEmpty()) {
            return back()->with('error', 'No vouchers found for the selected IDs.');
        }

        return view('pdfs.fee-vouchers-bulk', $data);
    }
}
