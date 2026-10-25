@extends('layouts.app')

@section('title', 'Fee Vouchers')

@section('content')
<div class="card">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Fee Vouchers</h4>
        <a href="{{ route('vouchers.bulk-class12') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Bulk Class 12 Vouchers
        </a>
    </div>
    <div class="card-body">
        {{-- Unified Executive KPI Metrics Row --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 report-kpi-card secondary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.72rem;">Pending Vouchers</span>
                                <h4 class="fw-bold mb-0 mt-1 text-dark">{{ number_format($totalPending) }}</h4>
                            </div>
                            <div class="badge bg-secondary-subtle text-secondary p-2 rounded-circle">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 report-kpi-card success">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.72rem;">Paid Vouchers</span>
                                <h4 class="fw-bold mb-0 mt-1 text-success">{{ number_format($totalPaid) }}</h4>
                            </div>
                            <div class="badge bg-success-subtle text-success p-2 rounded-circle">
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 report-kpi-card highlight">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.72rem;">Pending Amount</span>
                                <h4 class="fw-bold mb-0 mt-1 text-dark">Rs. {{ number_format($pendingAmount) }}</h4>
                            </div>
                            <div class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                                <i class="fas fa-exclamation-circle fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 report-kpi-card">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.72rem;">Collected Amount</span>
                                <h4 class="fw-bold mb-0 mt-1 text-primary">Rs. {{ number_format($collectedAmount) }}</h4>
                            </div>
                            <div class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                                <i class="fas fa-coins fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('vouchers.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search name or voucher #" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Initial Admission Fee" {{ request('type') == 'Initial Admission Fee' ? 'selected' : '' }}>Admission Fee</option>
                    <option value="Class 12 Annual Fee" {{ request('type') == 'Class 12 Annual Fee' ? 'selected' : '' }}>Class 12 Annual Fee</option>
                    <option value="Subject Change" {{ request('type') == 'Subject Change' ? 'selected' : '' }}>Subject Change</option>
                    <option value="Fine" {{ request('type') == 'Fine' ? 'selected' : '' }}>Fine</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="per_page" class="form-select">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 per page</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 per page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 per page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 per page</option>
                    <option value="500" {{ $perPage == 500 ? 'selected' : '' }}>500 per page</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-filter"></i></button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary w-100"><i class="fas fa-times"></i></a>
            </div>
        </form>

        {{-- Bulk Actions Form --}}
        <form id="bulkActionsForm" method="POST">
            @csrf
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <span id="selectedCount" class="text-muted">0 selected</span>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-primary" id="bulkDownloadBtn" disabled onclick="submitBulkAction('{{ route('vouchers.bulk-download') }}')">
                        <i class="fas fa-download me-1"></i>Download Selected (<span class="action-count">0</span>)
                    </button>
                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" disabled onclick="submitBulkDelete('{{ route('vouchers.bulk-delete') }}')">
                        <i class="fas fa-trash me-1"></i>Delete Selected (<span class="action-count">0</span>)
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Voucher #</th>
                            <th>Student</th>
                            <th>College Roll No</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th>Status</th>
                            <th>Issue Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vouchers as $voucher)
                        <tr>
                            <td>
                                <input type="checkbox" name="voucher_ids[]" value="{{ $voucher->id }}" class="voucher-checkbox">
                            </td>
                            <td><strong>{{ $voucher->voucher_number }}</strong></td>
                            <td>
                                @if($voucher->student)
                                    <a href="{{ route('students.show', $voucher->student) }}">{{ $voucher->applicant_name }}</a>
                                @else
                                    {{ $voucher->applicant_name }}
                                @endif
                                <br><small class="text-muted">{{ $voucher->father_name }}</small>
                            </td>
                            <td>
                                @if($voucher->college_roll_no)
                                    <strong>{{ $voucher->college_roll_no }}</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(str_contains($voucher->remarks ?? '', 'Fine'))
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Fine</span>
                                @elseif(str_contains($voucher->remarks ?? '', 'Class 12'))
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Class 12</span>
                                @elseif(str_contains($voucher->remarks ?? '', 'Subject Change'))
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Subject Change</span>
                                @else
                                    <span class="badge bg-light text-dark border">Admission</span>
                                @endif
                            </td>
                            <td class="text-end">Rs. {{ number_format($voucher->amount) }}</td>
                            <td class="text-end">Rs. {{ number_format($voucher->paid_amount) }}</td>
                            <td>
                                <span class="badge bg-{{ $voucher->status_badge }}">
                                    {{ ucfirst($voucher->status) }}
                                    @if($voucher->is_overdue) (Overdue) @endif
                                </span>
                            </td>
                            <td>{{ $voucher->issue_date?->format('d M Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('vouchers.show', $voucher) }}" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('students.vouchers.download', $voucher) }}" class="btn btn-outline-secondary" title="Download" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    @if($voucher->status === 'pending')
                                    <button type="button" class="btn btn-outline-success" title="Confirm Payment"
                                        data-bs-toggle="modal" data-bs-target="#confirmModal{{ $voucher->id }}">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No vouchers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        {{-- Payment Confirmation Modals (outside the bulk form) --}}
        @foreach ($vouchers as $voucher)
            @if($voucher->status === 'pending')
            <div class="modal fade" id="confirmModal{{ $voucher->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('vouchers.confirm', $voucher) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Payment - {{ $voucher->voucher_number }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Student:</strong> {{ $voucher->applicant_name }}</p>
                                <p><strong>Amount:</strong> Rs. {{ number_format($voucher->amount) }}</p>
                                <div class="mb-3">
                                    <label class="form-label">Paid Amount</label>
                                    <input type="number" name="paid_amount" class="form-control" value="{{ $voucher->amount }}" required min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Confirm Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        @endforeach

        {{ $vouchers->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.voucher-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const actionCounts = document.querySelectorAll('.action-count');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkDownloadBtn = document.getElementById('bulkDownloadBtn');

    function updateCount() {
        const checked = document.querySelectorAll('.voucher-checkbox:checked').length;
        selectedCount.textContent = checked + ' selected';
        actionCounts.forEach(el => el.textContent = checked);
        bulkDeleteBtn.disabled = checked === 0;
        bulkDownloadBtn.disabled = checked === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateCount();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (selectAll) selectAll.checked = document.querySelectorAll('.voucher-checkbox:checked').length === checkboxes.length;
            updateCount();
        });
    });
});

function submitBulkAction(url) {
    const form = document.getElementById('bulkActionsForm');
    form.action = url;
    form.target = '_blank';
    form.submit();
}

function submitBulkDelete(url) {
    if (!confirm('Are you sure? Only PENDING vouchers will be deleted.')) return;
    const form = document.getElementById('bulkActionsForm');
    form.action = url;
    form.target = '_self';
    form.submit();
}
</script>
@endsection
