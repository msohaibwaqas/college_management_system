<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdmissionApplication;
use App\Models\Admissions;
use App\Models\FeeVoucher;
use App\Models\FeeVoucherItem;
use Illuminate\Support\Facades\DB;

class CashRegisterReportController extends Controller
{
    /**
     * Show the selection form
     */
    public function index()
    {
        $appYears = AdmissionApplication::select('admission_year')
            ->distinct()
            ->pluck('admission_year')
            ->toArray();

        $admYears = Admissions::select('admission_year')
            ->distinct()
            ->pluck('admission_year')
            ->toArray();

        $admissionYears = collect(array_merge($appYears, $admYears))
            ->unique()
            ->sortDesc()
            ->values();

        return view('reports.cash-register.index', compact('admissionYears'));
    }

    /**
     * Show the report
     */
    public function show(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
        ]);

        $admissionYear = $request->admission_year;

        // Get vouchers for this admission year
        // Include vouchers linked to an application of this year
        // OR vouchers linked to a student who entered in this year
        $vouchers = FeeVoucher::where(function($q) use ($admissionYear) {
                $q->whereHas('application', function($aq) use ($admissionYear) {
                    $aq->where('admission_year', $admissionYear);
                })->orWhereHas('student.admissions', function($sq) use ($admissionYear) {
                    $sq->where('admission_year', $admissionYear);
                });
            })
            ->with(['items', 'application', 'student.admissions'])
            ->get();

        if ($vouchers->isEmpty()) {
            return back()->with('error', 'No vouchers found for the selected admission year.');
        }

        // Identify all unique fee head names across all vouchers to create columns
        $rawFeeHeadNames = FeeVoucherItem::whereIn('fee_voucher_id', $vouchers->pluck('id'))
            ->distinct()
            ->pluck('name')
            ->toArray();

        $feeHeadNames = [];
        $hasBreakageFund = false;

        foreach ($rawFeeHeadNames as $name) {
            if (str_contains($name, 'Breakage Fund')) {
                $hasBreakageFund = true;
            } else {
                $feeHeadNames[] = $name;
            }
        }

        if ($hasBreakageFund) {
            $feeHeadNames[] = 'Breakage Fund';
        }

        // Sort names alphabetically
        sort($feeHeadNames);

        // Prepare data for the table
        $reportData = [];
        $grandTotals = array_fill_keys($feeHeadNames, 0);
        $finalGrandTotal = 0;

        foreach ($vouchers as $voucher) {
            // Determine Roll Number
            $rollNo = '—';
            if ($voucher->application && $voucher->application->assigned_roll_no) {
                $rollNo = $voucher->application->assigned_roll_no;
            } elseif ($voucher->student) {
                $relevantAdmission = $voucher->student->admissions
                    ->where('admission_year', $admissionYear)
                    ->sortByDesc('academic_year')
                    ->first();
                $rollNo = $relevantAdmission->college_roll_no ?? '—';
            }

            $row = [
                'roll_no' => $rollNo,
                'name' => $voucher->applicant_name,
                'items' => array_fill_keys($feeHeadNames, 0),
                'total' => $voucher->amount,
                'status' => $voucher->status,
            ];

            foreach ($voucher->items as $item) {
                $displayName = str_contains($item->name, 'Breakage Fund') ? 'Breakage Fund' : $item->name;
                
                if (isset($row['items'][$displayName])) {
                    $row['items'][$displayName] += $item->amount;
                    $grandTotals[$displayName] += $item->amount;
                }
            }

            $reportData[] = $row;
            $finalGrandTotal += $voucher->amount;
        }

        return view('reports.cash-register.report', compact(
            'admissionYear', 
            'feeHeadNames', 
            'reportData', 
            'grandTotals', 
            'finalGrandTotal'
        ));
    }
}
