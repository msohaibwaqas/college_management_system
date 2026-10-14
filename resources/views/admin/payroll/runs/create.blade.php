@extends('layouts.app')

@section('title', 'Generate Monthly Payroll')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-calculator me-2"></i>Generate Monthly Payroll</h1>
        <p class="page-subtitle">Batch process salaries for all active staff for a specific period.</p>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('payroll.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-bold">Target Month</label>
                            <input type="month" name="month" class="form-control form-control-lg" value="{{ date('Y-m') }}" required>
                            <div class="form-text mt-2 text-info">
                                <i class="fas fa-info-circle me-1"></i> 
                                The system will automatically calculate:
                                <ul class="small mb-0 mt-1">
                                    <li>Pre-defined Allowances & Deductions</li>
                                    <li>Absence Penalties based on Attendance Records</li>
                                    <li>Net Payable for all assigned structures</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Internal Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Reference or reason for this run..."></textarea>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-4" onclick="return confirm('Start batch processing for the selected month?')">
                                <i class="fas fa-play me-2"></i>Initiate Calculation
                            </button>
                            <a href="{{ route('payroll.index') }}" class="btn btn-light btn-lg ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card bg-primary bg-opacity-10 border-0 h-100">
                <div class="card-body">
                    <h5 class="text-primary fw-bold"><i class="fas fa-lightbulb me-2"></i>Pre-Run Checklist</h5>
                    <ul class="text-muted mt-3">
                        <li class="mb-3">Ensure all <strong>Attendance</strong> for the month has been marked correctly.</li>
                        <li class="mb-3">Verify that new staff have been assigned a <strong>Salary Structure</strong>.</li>
                        <li class="mb-3">Approved <strong>Leaves</strong> are ignored from absence deductions; only "Absent" status triggers a penalty.</li>
                        <li class="mb-3">Gross salary = Base + All fixed/percentage Earnings.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
