@extends('layouts.app')

@section('title', 'My Leaves')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-suitcase-rolling me-2"></i>My Leave Dashboard</h1>
            <p class="page-subtitle">View your annual leave balances and track application statuses</p>
        </div>
        <div>
            @if(Auth::user()->is_super_admin || Auth::user()->hasRole('admin') || Auth::user()->hasRole('principal'))
                <a href="{{ route('leave-types.index') }}" class="btn btn-outline-primary me-2 shadow-sm">
                    <i class="fas fa-sliders-h me-1"></i> Manage Leave Types & Limits
                </a>
            @endif
            <a href="{{ route('my-leaves.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Apply for Leave
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3 text-muted">Annual Balances & Monthly Limits ({{ $currentYear }})</h5>
            <div class="row row-cols-1 row-cols-md-3 row-cols-xl-4 g-3">
                @foreach($balances as $balance)
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase fw-bold mb-3">{{ $balance->leaveType->name }}</h6>
                            <h2 class="mb-0 {{ $balance->pending_days > 0 ? 'text-primary' : 'text-danger' }}">
                                {{ $balance->pending_days }} <small class="text-muted fs-6" style="vertical-align: middle;">/ {{ $balance->allocated_days }} days/yr</small>
                            </h2>
                            <div class="small text-muted mb-0 mt-2">
                                <div>Used: <span class="fw-bold">{{ $balance->used_days }} day(s)</span></div>
                                <div class="mt-1 text-primary">
                                    <i class="fas fa-calendar-alt me-1"></i>Monthly Limit: <strong>{{ $balance->leaveType->max_leaves_per_month ?? 2 }}</strong> days/mo
                                </div>
                                <div class="text-secondary small">
                                    <i class="fas fa-star me-1"></i>Special Case: up to <strong>{{ $balance->leaveType->special_case_max_leaves_per_month ?? 7 }}</strong> days/mo
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="small badge {{ $balance->leaveType->is_paid ? 'bg-success' : 'bg-danger' }}">{{ $balance->leaveType->is_paid ? 'Paid' : 'Unpaid' }}</span>
                            @if($balance->leaveType->period_start_date && $balance->leaveType->period_end_date)
                                <small class="text-muted" style="font-size: 0.75rem;">{{ $balance->leaveType->period_start_date->format('d/m/y') }} - {{ $balance->leaveType->period_end_date->format('d/m/y') }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
            <h5 class="mb-0 fw-bold">Recent Applications</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Leave Type</th>
                            <th>Duration / Dates</th>
                            <th>Reason</th>
                            <th>Applied On</th>
                            <th>Status/Approver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                        <tr>
                            <td>
                                <strong>{{ $app->leaveType->name }}</strong>
                                @if($app->is_half_day)
                                    <span class="badge bg-secondary ms-1">Half Day</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ $app->total_days }} day(s)</div>
                                <small class="text-muted">{{ $app->from_date->format('M d') }} - {{ $app->to_date->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <span class="text-truncate d-block" style="max-width: 250px;" title="{{ $app->reason }}">{{ $app->reason }}</span>
                                @if($app->document_path)
                                    <a href="{{ Storage::url($app->document_path) }}" target="_blank" class="small text-decoration-none"><i class="fas fa-paperclip me-1"></i>View Attachment</a>
                                @endif
                            </td>
                            <td>{{ $app->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($app->status == 'pending')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending Dept. Head</span>
                                @elseif($app->status == 'hod_approved')
                                    <span class="badge bg-primary"><i class="fas fa-check me-1"></i> Pending Principal</span>
                                @elseif($app->status == 'principal_approved')
                                    <span class="badge bg-success"><i class="fas fa-check-double me-1"></i> Approved</span>
                                @elseif($app->status == 'rejected')
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Rejected</span>
                                @elseif($app->status == 'cancelled')
                                    <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i> Cancelled</span>
                                @endif

                                @if($app->currentApprover)
                                    <div class="small mt-1 text-muted">by {{ $app->currentApprover->name }}</div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">You haven't applied for any leaves yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $applications->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
