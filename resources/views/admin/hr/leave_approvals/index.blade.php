@extends('layouts.app')

@section('title', 'Leave Approvals')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-tasks me-2"></i>Leave Approvals Dashboard</h1>
            <p class="page-subtitle">Manage staff leave applications and authorization workflows</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body py-2">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link {{ $status == 'all' ? 'active' : '' }}" href="{{ route('leave-approvals.index', ['status' => 'all']) }}">All Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status == 'pending' ? 'active bg-warning text-dark' : 'text-warning' }} fw-bold" href="{{ route('leave-approvals.index', ['status' => 'pending']) }}">Pending HOD</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status == 'hod_approved' ? 'active bg-primary' : 'text-primary' }} fw-bold" href="{{ route('leave-approvals.index', ['status' => 'hod_approved']) }}">Pending Principal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status == 'principal_approved' ? 'active bg-success' : 'text-success' }}" href="{{ route('leave-approvals.index', ['status' => 'principal_approved']) }}">Fully Approved</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $status == 'rejected' ? 'active bg-danger' : 'text-danger' }}" href="{{ route('leave-approvals.index', ['status' => 'rejected']) }}">Rejected</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Staff Member</th>
                            <th>Class/Type</th>
                            <th>Duration (Days)</th>
                            <th>Dates Applied</th>
                            <th>Workflow Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar bg-primary text-white rounded-circle text-center d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        {{ substr($app->staff->first_name ?? '-', 0, 1) }}
                                    </div>
                                    <div>
                                        <strong>{{ $app->staff->first_name ?? 'Unknown' }} {{ $app->staff->last_name ?? '' }}</strong>
                                        <div class="small text-muted">{{ $app->staff->designation ?? 'Staff' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold">{{ $app->leaveType->name }}</span>
                                @if($app->is_half_day)
                                    <span class="badge bg-secondary ms-1">Half Day</span>
                                @endif
                                @if($app->document_path)
                                    <br><span class="badge bg-info bg-opacity-10 text-info border border-info px-1 mt-1" style="font-size: 0.7rem;"><i class="fas fa-paperclip me-1"></i>Doc Attached</span>
                                @endif
                            </td>
                            <td>
                                <h4><span class="badge bg-light text-dark border">{{ $app->total_days }}</span></h4>
                            </td>
                            <td>
                                <div>{{ $app->from_date->format('d M, Y') }}</div>
                                <div class="small text-muted">to {{ $app->to_date->format('d M, Y') }}</div>
                            </td>
                            <td>
                                @if($app->status == 'pending')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending HOD</span>
                                @elseif($app->status == 'hod_approved')
                                    <span class="badge bg-primary"><i class="fas fa-check me-1"></i> HOD Approved (Pending Principal)</span>
                                @elseif($app->status == 'principal_approved')
                                    <span class="badge bg-success"><i class="fas fa-check-double me-1"></i> Final Approved</span>
                                @elseif($app->status == 'rejected')
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Rejected</span>
                                @endif
                                <div class="small mt-1 text-muted">Applied: {{ $app->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <a href="{{ route('leave-approvals.show', $app->id) }}" class="btn btn-sm btn-outline-primary">
                                    Review & Action <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                No leave applications found for the selected filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $applications->appends(['status' => $status])->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
