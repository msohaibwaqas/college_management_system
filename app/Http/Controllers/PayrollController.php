<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\PayrollRun;
use App\Models\SalarySlip;
use App\Models\SalaryStructure;
use App\Models\StaffDailyAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        $runs = PayrollRun::with('creator')->orderBy('payroll_month', 'desc')->paginate(12);
        return view('admin.payroll.runs.index', compact('runs'));
    }

    public function create()
    {
        return view('admin.payroll.runs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'notes' => 'nullable|string'
        ]);

        $payrollMonth = Carbon::parse($request->input('month') . '-01');
        
        // Check if run already exists
        if (PayrollRun::where('payroll_month', $payrollMonth->toDateString())->exists()) {
            return back()->with('error', 'A payroll run already exists for this month.');
        }

        DB::beginTransaction();

        try {
            $run = PayrollRun::create([
                'payroll_month' => $payrollMonth->toDateString(),
                'generated_by' => Auth::id(),
                'status' => 'draft',
                'notes' => $request->input('notes')
            ]);

            $staffMembers = Staff::where('status', 'active')
                ->whereNotNull('salary_structure_id')
                ->with('salaryStructure.components')
                ->get();

            $totalRunAmount = 0;

            foreach ($staffMembers as $staff) {
                $structure = $staff->salaryStructure;
                $baseSalary = $structure->base_salary;
                
                $earnings = 0;
                $deductions = 0;
                $componentsData = ['earnings' => [], 'deductions' => []];

                foreach ($structure->components as $comp) {
                    $val = ($comp->amount_type == 'percentage') ? ($baseSalary * $comp->amount / 100) : $comp->amount;
                    if ($comp->type == 'earning') {
                        $earnings += $val;
                        $componentsData['earnings'][] = ['name' => $comp->name, 'amount' => $val];
                    } else {
                        $deductions += $val;
                        $componentsData['deductions'][] = ['name' => $comp->name, 'amount' => $val];
                    }
                }

                // Absence Deduction Logic
                // Rule: (Base Salary / 30) * Unapproved Absence Count
                $absentDays = StaffDailyAttendance::where('staff_id', $staff->id)
                    ->whereBetween('date', [$payrollMonth->startOfMonth()->toDateString(), $payrollMonth->endOfMonth()->toDateString()])
                    ->where('status', 'absent')
                    ->count();
                
                $absenceDeduction = ($baseSalary / 30) * $absentDays;
                $netSalary = ($baseSalary + $earnings) - ($deductions + $absenceDeduction);

                SalarySlip::create([
                    'payroll_run_id' => $run->id,
                    'staff_id' => $staff->id,
                    'base_salary' => $baseSalary,
                    'total_earnings' => $earnings,
                    'total_deductions' => $deductions,
                    'absence_deduction' => $absenceDeduction,
                    'net_salary' => $netSalary,
                    'components_breakdown' => $componentsData,
                    'status' => 'pending'
                ]);

                $totalRunAmount += $netSalary;
            }

            $run->update(['total_amount' => $totalRunAmount]);

            DB::commit();
            return redirect()->route('payroll.show', $run->id)->with('success', 'Monthly payroll draft generated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Critial Error generating payroll: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $run = PayrollRun::with(['slips.staff', 'creator'])->findOrFail($id);
        return view('admin.payroll.runs.show', compact('run'));
    }

    public function finalize($id)
    {
        $run = PayrollRun::findOrFail($id);
        $run->update(['status' => 'finalized']);
        
        // In a real system, this might trigger bank transfers or email notifications
        return back()->with('success', 'Payroll has been finalized for the month.');
    }
}
