@extends('layouts.app')
@section('title', 'Student Fines & Dues — Accounts Panel')

@section('content')
<div class="container-fluid">
    @php
        $authUser = Auth::user();
        $isPrincipalOrAdmin = $authUser && ($authUser->is_super_admin || in_array($authUser->getActiveRole(), ['admin', 'principal', 'super_admin']));
    @endphp

    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-invoice-dollar text-warning me-2"></i>Student Fine Management
            </h1>
            <p class="text-muted small mb-0 mt-1">
                Automated fines for lecture absences (e.g. Rs. 1/absent), test absences (e.g. Oct: Rs. 100, Dec: Rs. 150), and test failures (e.g. Oct: Rs. 50, Dec: Rs. 100).
            </p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            @if($canManage)
                <a href="{{ route('fines.generate') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fas fa-calculator me-1"></i> Calculate &amp; Generate
                </a>
                <a href="{{ route('fines.rules') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="fas fa-sliders-h me-1"></i> Fine Rules &amp; Rates
                </a>
            @endif
            <a href="{{ route('fines.print-summary', request()->all()) }}" target="_blank" class="btn btn-outline-info btn-sm shadow-sm">
                <i class="fas fa-print me-1"></i> Print Summary
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KPI Metric Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-primary border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Fined Amount</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Rs. {{ number_format($stats['total_amount'], 2) }}</div>
                            <small class="text-muted">{{ number_format($stats['total_fines']) }} record(s)</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-danger border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Outstanding Balance</div>
                            <div class="h5 mb-0 fw-bold text-danger">Rs. {{ number_format($stats['balance_amount'], 2) }}</div>
                            <small class="text-muted">{{ number_format($stats['unpaid_fines']) }} pending dues</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Collected</div>
                            <div class="h5 mb-0 fw-bold text-success">Rs. {{ number_format($stats['paid_amount'], 2) }}</div>
                            <small class="text-muted">{{ number_format($stats['paid_fines']) }} cleared fines</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-double fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-warning border-4 shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Total Waived / Concessions</div>
                            <div class="h5 mb-0 fw-bold text-warning">Rs. {{ number_format($stats['waived_amount'], 2) }}</div>
                            <small class="text-muted">{{ number_format($stats['waived_fines']) }} waiver(s)</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('fines.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-search me-1"></i>Search Student</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Name, Roll No, Receipt #, Reason..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-tag me-1"></i>Fine Type</label>
                    <select name="fine_type" class="form-select form-select-sm">
                        <option value="all" {{ request('fine_type') === 'all' ? 'selected' : '' }}>All Types</option>
                        <option value="lecture_absent" {{ request('fine_type') === 'lecture_absent' ? 'selected' : '' }}>Lecture Absence</option>
                        <option value="test_absent" {{ request('fine_type') === 'test_absent' ? 'selected' : '' }}>Test Absence</option>
                        <option value="test_fail" {{ request('fine_type') === 'test_fail' ? 'selected' : '' }}>Test Failure</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-filter me-1"></i>Payment Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="unpaid" {{ $statusFilter === 'unpaid' ? 'selected' : '' }}>Unpaid / Pending</option>
                        <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="waived" {{ $statusFilter === 'waived' ? 'selected' : '' }}>Waived</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-4">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-layer-group me-1"></i>Class Level</label>
                    <select name="class_level" class="form-select form-select-sm">
                        <option value="">All Classes</option>
                        <option value="11" {{ request('class_level') == '11' ? 'selected' : '' }}>11th Class</option>
                        <option value="12" {{ request('class_level') == '12' ? 'selected' : '' }}>12th Class</option>
                    </select>
                </div>

                <div class="col-md-1 col-sm-4">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i>Year</label>
                    <select name="year" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ request('year', date('Y')) == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    @if(request()->anyFilled(['search', 'fine_type', 'status', 'class_level', 'year']))
                        <a href="{{ route('fines.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Fines Table Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-list me-2"></i>Student Fine Ledger
                <span class="badge bg-secondary ms-2">{{ $fines->total() }}</span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($fines->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-info-circle fa-2x mb-2 text-secondary"></i>
                    <p class="mb-0">No fine records found matching the criteria.</p>
                    @if($canManage)
                        <div class="mt-2">
                            <a href="{{ route('fines.generate') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Calculate &amp; Generate Fines
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Roll No</th>
                                <th>Class</th>
                                <th>Type &amp; Period</th>
                                <th>Reason / Breakdown</th>
                                <th class="text-center">Units &times; Rate</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="min-width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fines as $i => $fine)
                            <tr>
                                <td class="text-muted small">{{ $fines->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-bold">{{ $fine->student?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $fine->student?->father_name ?? '' }}</small>
                                </td>
                                <td><code>{{ $fine->admission?->college_roll_no ?? '—' }}</code></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $fine->admission?->current_class ?? '—' }}th ({{ $fine->academic_year }})
                                    </span>
                                </td>
                                <td>
                                    @if($fine->fine_type === 'lecture_absent')
                                        <span class="badge bg-primary">Lecture Absent</span>
                                        <div class="small text-muted mt-1">{{ $fine->period ?? 'Monthly' }}</div>
                                    @elseif($fine->fine_type === 'test_absent')
                                        <span class="badge bg-danger">Test Absent</span>
                                        <div class="small fw-semibold mt-1">{{ $fine->exam_name }}</div>
                                    @else
                                        <span class="badge bg-warning text-dark">Test Fail</span>
                                        <div class="small fw-semibold mt-1">{{ $fine->exam_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 240px;" title="{{ $fine->reason }}">
                                        {{ $fine->reason }}
                                    </div>
                                    @if($fine->payment_reference)
                                        <small class="text-success d-block">
                                            <i class="fas fa-receipt me-1"></i>Ref: {{ $fine->payment_reference }}
                                        </small>
                                    @endif
                                    @if($fine->waiver_reason)
                                        <small class="text-warning d-block">
                                            <i class="fas fa-hand-holding-usd me-1"></i>Waived: {{ $fine->waiver_reason }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold">{{ $fine->units_count }}</span> &times; Rs. {{ number_format($fine->amount_per_unit, 2) }}
                                </td>
                                <td class="text-end fw-bold">
                                    Rs. {{ number_format($fine->total_amount, 2) }}
                                </td>
                                <td class="text-end text-success">
                                    Rs. {{ number_format($fine->paid_amount, 2) }}
                                </td>
                                <td class="text-end fw-bold {{ $fine->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                    Rs. {{ number_format($fine->balance_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($fine->status === 'paid')
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Paid</span>
                                    @elseif($fine->status === 'partially_paid')
                                        <span class="badge bg-info text-dark"><i class="fas fa-adjust me-1"></i>Partial</span>
                                    @elseif($fine->status === 'waived')
                                        <span class="badge bg-warning text-dark"><i class="fas fa-tag me-1"></i>Waived</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-clock me-1"></i>Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        {{-- Print Slip --}}
                                        <a href="{{ route('fines.slip', $fine->id) }}" target="_blank" class="btn btn-outline-info" title="Print Challan Slip">
                                            <i class="fas fa-print"></i>
                                        </a>

                                        @if($canManage && $fine->balance_amount > 0)
                                            {{-- Collect Payment Button --}}
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal{{ $fine->id }}" title="Collect Payment">
                                                <i class="fas fa-cash-register"></i>
                                            </button>

                                            @if($isPrincipalOrAdmin)
                                                {{-- Waive Fine Button --}}
                                                <button type="button" class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#waiveModal{{ $fine->id }}" title="Grant Waiver">
                                                    <i class="fas fa-hand-holding-usd"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>

                                    @if($canManage && $fine->balance_amount > 0)
                                        {{-- Payment Modal --}}
                                        <div class="modal fade" id="payModal{{ $fine->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form action="{{ route('fines.collect-payment', $fine->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header bg-success text-white">
                                                            <h5 class="modal-title"><i class="fas fa-cash-register me-2"></i>Collect Fine Payment</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <div class="alert alert-light border mb-3">
                                                                <div>Student: <strong>{{ $fine->student?->full_name }}</strong> (Roll: {{ $fine->admission?->college_roll_no }})</div>
                                                                <div>Total Fine: <strong>Rs. {{ number_format($fine->total_amount, 2) }}</strong></div>
                                                                <div>Already Paid: <strong class="text-success">Rs. {{ number_format($fine->paid_amount, 2) }}</strong></div>
                                                                <div>Outstanding Balance: <strong class="text-danger">Rs. {{ number_format($fine->balance_amount, 2) }}</strong></div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small">Payment Amount (Rs.) <span class="text-danger">*</span></label>
                                                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ $fine->balance_amount }}" max="{{ $fine->balance_amount }}" min="0.01" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small">Receipt # / Bank Scroll #</label>
                                                                <input type="text" name="payment_reference" class="form-control" placeholder="e.g. REC-10492">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small">Note <span class="text-muted">(optional)</span></label>
                                                                <textarea name="payment_note" class="form-control" rows="2" placeholder="e.g. Paid in full via fee counter..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check-circle me-1"></i>Confirm Payment</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        @if($isPrincipalOrAdmin)
                                            {{-- Waiver Modal --}}
                                            <div class="modal fade" id="waiveModal{{ $fine->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="{{ route('fines.waive', $fine->id) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-header bg-warning text-dark">
                                                                <h5 class="modal-title"><i class="fas fa-hand-holding-usd me-2"></i>Authorize Fine Waiver</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="alert alert-light border mb-3">
                                                                    <div>Student: <strong>{{ $fine->student?->full_name }}</strong> (Roll: {{ $fine->admission?->college_roll_no }})</div>
                                                                    <div>Outstanding Balance: <strong class="text-danger">Rs. {{ number_format($fine->balance_amount, 2) }}</strong></div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small">Waiver Amount (Rs.) <span class="text-danger">*</span></label>
                                                                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ $fine->balance_amount }}" max="{{ $fine->balance_amount }}" min="0.01" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small">Reason for Waiver / Concession <span class="text-danger">*</span></label>
                                                                    <textarea name="waiver_reason" class="form-control" rows="3" placeholder="e.g. Approved medical concession by Principal..." required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-check-circle me-1"></i>Authorize Waiver</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-3">
                    {{ $fines->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
