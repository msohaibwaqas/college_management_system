@extends('layouts.app')

@section('title', 'Payroll Details')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-file-invoice me-2"></i>
                Payroll: {{ $run->payroll_month->format('F Y') }}
            </h1>
            <span class="badge {{ $run->status == 'draft' ? 'bg-warning text-dark' : 'bg-success' }} fs-6 px-3 py-2 mt-2">
                {{ strtoupper($run->status) }}
            </span>
        </div>
        <div class="d-flex g-2">
            <a href="{{ route('payroll.index') }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            @if($run->status == 'draft')
            <form action="{{ route('payroll.finalize', $run->id) }}" method="POST">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-success" onclick="return confirm('Finalize this payroll? Changes won\'t be possible afterwards.')">
                    <i class="fas fa-check-double me-1"></i> Finalize Run
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 small text-uppercase fw-bold">Total Payout</h6>
                    <h2 class="mb-0">PKR {{ number_format($run->total_amount) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase fw-bold">Staff Processed</h6>
                    <h2 class="mb-0 text-primary">{{ $run->slips->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase fw-bold">Notes</h6>
                    <p class="mb-0 mt-1 italic">{{ $run->notes ?: 'No additional notes provided.' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between">
            <h5 class="mb-0 fw-bold">Detailed Salary Slips</h5>
            <div class="d-print-none">
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i> Print Matrix</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Staff Name</th>
                            <th class="text-end">Base Salary</th>
                            <th class="text-end text-success">Total Earnings</th>
                            <th class="text-end text-danger">Total Deductions</th>
                            <th class="text-end text-danger">Absence Penalty</th>
                            <th class="text-end fw-bold">Net Payable</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($run->slips as $slip)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $slip->staff->first_name }} {{ $slip->staff->last_name }}</div>
                                <div class="small text-muted">{{ $slip->staff->designation }}</div>
                            </td>
                            <td class="text-end">{{ number_format($slip->base_salary) }}</td>
                            <td class="text-end text-success">+{{ number_format($slip->total_earnings) }}</td>
                            <td class="text-end text-danger">-{{ number_format($slip->total_deductions) }}</td>
                            <td class="text-end text-danger">-{{ number_format($slip->absence_deduction) }}</td>
                            <td class="text-end fw-bold">PKR {{ number_format($slip->net_salary) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $slip->status == 'paid' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($slip->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, .btn, .d-print-none, .page-subtitle { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
@endsection
