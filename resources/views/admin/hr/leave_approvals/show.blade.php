@extends('layouts.app')

@section('title', 'Review Leave Application')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-search me-2"></i>Review Leave Application</h1>
            <p class="page-subtitle">Examine details, check balances, and advance the approval workflow</p>
        </div>
        <div>
            <a href="{{ route('leave-approvals.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

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

    <div class="row">
        <!-- Application Details -->
        <div class="col-lg-8">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 fw-bold">Application Details</h5>
                    <div>
                        @if($application->status == 'pending')
                            <span class="badge bg-warning fs-6 text-dark py-2 px-3"><i class="fas fa-clock me-1"></i> Pending HOD Review</span>
                        @elseif($application->status == 'hod_approved')
                            <span class="badge bg-primary fs-6 py-2 px-3"><i class="fas fa-check me-1"></i> Pending Principal Review</span>
                        @elseif($application->status == 'principal_approved')
                            <span class="badge bg-success fs-6 py-2 px-3"><i class="fas fa-check-double me-1"></i> Final Approved</span>
                        @elseif($application->status == 'rejected')
                            <span class="badge bg-danger fs-6 py-2 px-3"><i class="fas fa-times me-1"></i> Rejected</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase fw-bold">Applicant</label>
                            <div class="d-flex align-items-center mt-1">
                                <div class="avatar bg-primary text-white rounded-circle text-center d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                    {{ substr($application->staff->first_name ?? '-', 0, 1) }}
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold">{{ $application->staff->first_name ?? 'Unknown' }} {{ $application->staff->last_name ?? '' }}</h5>
                                    <div class="text-muted">{{ $application->staff->designation ?? 'Staff Member' }} | ID: {{ $application->staff->employee_id ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                            <label class="text-muted small text-uppercase fw-bold">Request Timeline</label>
                            <h5 class="mb-0 mt-1">{{ $application->from_date->format('d M, Y') }} - {{ $application->to_date->format('d M, Y') }}</h5>
                            <div class="text-muted">Total: <span class="fw-bold {{ $application->total_days > 2 ? 'text-danger' : 'text-primary' }}">{{ $application->total_days }} Day(s)</span></div>
                        </div>
                    </div>

                    <div class="row bg-light rounded p-3 mb-4 mx-0">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="text-muted small text-uppercase fw-bold"><i class="fas fa-tag me-1"></i>Leave Type</label>
                            <div class="fs-5 fw-bold">{{ $application->leaveType->name }}</div>
                            <span class="badge {{ $application->leaveType->is_paid ? 'bg-success' : 'bg-danger' }}">{{ $application->leaveType->is_paid ? 'Paid' : 'Unpaid' }}</span>
                            @if($application->is_half_day)
                                <span class="badge bg-secondary ms-1">Half Day</span>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <label class="text-muted small text-uppercase fw-bold"><i class="fas fa-align-left me-1"></i>Reason/Justification</label>
                            <p class="mb-0 mt-1">{{ $application->reason }}</p>
                        </div>
                    </div>

                    @if($application->document_path)
                    <div class="mb-2">
                        <label class="text-muted small text-uppercase fw-bold"><i class="fas fa-paperclip me-1"></i>Supporting Document</label>
                        <div class="mt-2 border rounded p-3 d-flex align-items-center justify-content-between bg-white">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                <div>
                                    <strong>Medical/Supporting Certificate Attached</strong>
                                    <div class="small text-muted">Uploaded by applicant</div>
                                </div>
                            </div>
                            <a href="{{ Storage::url($application->document_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> View Document
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Workflow Actions -->
            <div class="card shadow-sm border-0 border-top border-primary border-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="fas fa-gavel me-2 text-primary"></i>Decision Workflow</h5>
                    
                    <form action="{{ route('leave-approvals.update', $application->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Workflow Action <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="form-check custom-radio border p-3 rounded h-100">
                                        <input class="form-check-input" type="radio" name="status" id="actionHOD" value="hod_approved" {{ $application->status == 'pending' ? 'checked' : '' }} required>
                                        <label class="form-check-label fw-bold d-block" for="actionHOD">
                                            Approve as HOD
                                        </label>
                                        <small class="text-muted">Move to Principal step</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check custom-radio border p-3 rounded h-100 border-success">
                                        <input class="form-check-input" type="radio" name="status" id="actionPrincipal" value="principal_approved" {{ $application->status == 'hod_approved' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold d-block text-success" for="actionPrincipal">
                                            Final Approval (Principal)
                                        </label>
                                        <small class="text-success">Deducts leave balance</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check custom-radio border p-3 rounded h-100 border-danger">
                                        <input class="form-check-input" type="radio" name="status" id="actionReject" value="rejected">
                                        <label class="form-check-label fw-bold d-block text-danger" for="actionReject">
                                            Reject Request
                                        </label>
                                        <small class="text-danger">Denies the leave entirely</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Official Remarks/Comments</label>
                            <textarea name="comments" rows="2" class="form-control" placeholder="Optional comments for the applicant and audit log..."></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-lg px-4" onclick="return confirm('Confirm this workflow transition?');">
                                <i class="fas fa-check-circle me-2"></i>Execute Transition
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar context -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <!-- Balances Context -->
            <div class="card mb-4 border-0 bg-info bg-opacity-10 shadow-none">
                <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                    <h6 class="fw-bold text-info text-uppercase mb-0"><i class="fas fa-chart-pie me-2"></i>Current Balances</h6>
                </div>
                <div class="card-body">
                    @foreach($balances as $balance)
                        <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2 border-bottom pb-2 border-info border-opacity-25' : '' }}">
                            <div>
                                <span class="fw-bold {{ $balance->leave_type_id == $application->leave_type_id ? 'text-primary' : 'text-dark' }}">
                                    {{ $balance->leaveType->code }}
                                </span>
                                @if($balance->leave_type_id == $application->leave_type_id)
                                    <span class="badge bg-primary ms-1" style="font-size: 0.6rem;">Targeting</span>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="fw-bold fs-5 {{ $balance->pending_days < $application->total_days && $balance->leave_type_id == $application->leave_type_id ? 'text-danger' : '' }}">{{ $balance->pending_days }}</span>
                                <span class="text-muted small">/ {{ $balance->allocated_days }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>Audit Trail</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item bg-light p-3 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 text-primary"><i class="fas fa-flag text-primary me-2"></i>Application Submitted</h6>
                                <small class="text-muted">{{ $application->created_at->format('d M H:i') }}</small>
                            </div>
                            <p class="mb-0 text-muted mt-1"><i class="fas fa-user-circle me-1"></i>by {{ $application->staff->first_name ?? 'System' }}</p>
                        </li>
                        
                        @foreach($application->logs()->orderBy('created_at', 'asc')->get() as $log)
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-bold">
                                    @if($log->action == 'hod_approved')
                                        <i class="fas fa-check-circle text-primary me-2"></i>HOD Reviewed
                                    @elseif($log->action == 'principal_approved')
                                        <i class="fas fa-check-double text-success me-2"></i>Finalized
                                    @elseif($log->action == 'rejected')
                                        <i class="fas fa-times-circle text-danger me-2"></i>Rejected
                                    @endif
                                </h6>
                                <small class="text-muted" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-light text-dark fw-normal border"><i class="fas fa-user-shield me-1"></i>{{ $log->actionBy->username ?? 'Admin' }}</span>
                            </div>
                            @if($log->comments)
                                <div class="mt-2 bg-light rounded p-2 text-muted fw-lighter fst-italic border-start border-3 border-secondary">
                                    "{{ $log->comments }}"
                                </div>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-radio {
    cursor: pointer;
    transition: all 0.2s ease;
}
.custom-radio:hover {
    background-color: #f8f9fa;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}
.custom-radio input[type="radio"] {
    transform: scale(1.3);
    margin-right: 10px;
}
</style>
@endsection
