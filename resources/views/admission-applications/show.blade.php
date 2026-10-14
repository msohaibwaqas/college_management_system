@extends('layouts.app')
@section('title', 'Application #' . $application->application_number)

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-file-alt me-2"></i>Application #{{ $application->application_number }}
            <span class="badge bg-{{ $application->status_badge }} ms-2">{{ $application->status_label }}</span>
        </h4>
        <div>
            <a href="{{ route('admission-applications.edit', $application) }}" class="btn btn-warning shadow-sm">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            <form action="{{ route('admission-applications.destroy', $application) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this application?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger shadow-sm">
                    <i class="fas fa-trash me-1"></i> Delete
                </button>
            </form>
            <a href="{{ route('admission-applications.index') }}" class="btn btn-outline-secondary ms-2 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Left Column: Application Details --}}
        <div class="col-lg-8">
            {{-- Personal Info --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user me-2"></i>Personal Information</span>
                    @if($application->picture)
                        <img src="{{ Storage::disk('public')->url($application->picture) }}" alt="Student Picture" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Name:</strong> {{ $application->full_name }}</div>
                        <div class="col-md-3"><strong>DOB:</strong> {{ $application->date_of_birth->format('d M Y') }}</div>
                        <div class="col-md-3"><strong>Gender:</strong> {{ $application->gender }}</div>
                        <div class="col-md-3"><strong>CNIC:</strong> {{ $application->cnic ?? '—' }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3"><strong>Religion:</strong> {{ $application->religion ?? '—' }}</div>
                        <div class="col-md-3"><strong>Blood Group:</strong> {{ $application->bloodGroup->name ?? '—' }}</div>
                        <div class="col-md-3"><strong>Quota:</strong> {{ $application->quota->name ?? 'None' }}</div>
                        <div class="col-md-3"><strong>Source:</strong>
                            <span class="badge bg-{{ $application->source === 'online' ? 'info' : 'dark' }}">
                                {{ $application->source === 'online' ? 'Online' : 'Walk-in' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Father & Contact --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-users me-2"></i>Father & Contact</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Father:</strong> {{ $application->father_name }}</div>
                        <div class="col-md-3"><strong>Father CNIC:</strong> {{ $application->father_cnic ?? '—' }}</div>
                        <div class="col-md-3"><strong>Father Contact:</strong> {{ $application->father_contact ?? '—' }}</div>
                        <div class="col-md-3"><strong>Occupation:</strong> {{ $application->father_occupation ?? '—' }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3"><strong>Student Phone:</strong> {{ $application->student_contact ?? '—' }}</div>
                        <div class="col-md-3"><strong>WhatsApp:</strong> {{ $application->whatsapp_no ?? '—' }}</div>
                        <div class="col-md-3"><strong>Email:</strong> {{ $application->email ?? '—' }}</div>
                        <div class="col-md-3"><strong>Address:</strong> {{ $application->address ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- SSC Record --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-graduation-cap me-2"></i>SSC Record</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>BISE:</strong> {{ $application->bise->name ?? '—' }}</div>
                        <div class="col-md-2"><strong>Roll No:</strong> {{ $application->ssc_roll_no }}</div>
                        <div class="col-md-2"><strong>Reg No:</strong> {{ $application->ssc_reg_no }}</div>
                        <div class="col-md-3"><strong>School:</strong> {{ $application->ssc_school_name }}</div>
                        <div class="col-md-2"><strong>Group:</strong> {{ $application->ssc_group }}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-2">
                            <strong>Marks:</strong>
                            <span class="fw-bold text-success">{{ $application->ssc_obtained_marks }}</span> / {{ $application->ssc_total_marks }}
                        </div>
                        <div class="col-md-2"><strong>Percentage:</strong> {{ $application->ssc_percentage }}%</div>
                        <div class="col-md-2"><strong>Year:</strong> {{ $application->ssc_passing_year }}</div>
                        <div class="col-md-3"><strong>Session:</strong> {{ $application->ssc_passing_session }}</div>
                    </div>
                </div>
            </div>

            {{-- Elective Subjects --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-book me-2"></i>Elective Subjects
                    @if($application->subject_group)
                        <span class="badge bg-primary ms-2">{{ $application->subject_group }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4"><strong>1.</strong> {{ $application->electiveSubject1->name ?? '—' }}</div>
                        <div class="col-md-4"><strong>2.</strong> {{ $application->electiveSubject2->name ?? '—' }}</div>
                        <div class="col-md-4"><strong>3.</strong> {{ $application->electiveSubject3->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Documents --}}
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-file-image me-2"></i>Documents</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted mb-2">SSC Result Card</h6>
                            @if($application->ssc_result_card)
                                <a href="{{ Storage::disk('public')->url($application->ssc_result_card) }}" target="_blank">
                                    <img src="{{ Storage::disk('public')->url($application->ssc_result_card) }}" alt="SSC Result Card" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                                </a>
                            @else
                                <div class="bg-light p-4 rounded border text-muted">No image uploaded</div>
                            @endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted mb-2">B-Form / CNIC</h6>
                            @if($application->b_form_pic)
                                <a href="{{ Storage::disk('public')->url($application->b_form_pic) }}" target="_blank">
                                    <img src="{{ Storage::disk('public')->url($application->b_form_pic) }}" alt="B-Form" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                                </a>
                            @else
                                <div class="bg-light p-4 rounded border text-muted">No image uploaded</div>
                            @endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted mb-2">Father CNIC</h6>
                            @if($application->father_cnic_pic)
                                <a href="{{ Storage::disk('public')->url($application->father_cnic_pic) }}" target="_blank">
                                    <img src="{{ Storage::disk('public')->url($application->father_cnic_pic) }}" alt="Father CNIC" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                                </a>
                            @else
                                <div class="bg-light p-4 rounded border text-muted">No image uploaded</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Voucher Info --}}
            @if($application->voucher)
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-receipt me-2"></i>Fee Voucher</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Voucher #:</strong> {{ $application->voucher->voucher_number }}</div>
                        <div class="col-md-2"><strong>Amount:</strong> Rs. {{ number_format($application->voucher->amount) }}</div>
                        <div class="col-md-2"><strong>Paid:</strong> Rs. {{ number_format($application->voucher->paid_amount) }}</div>
                        <div class="col-md-2">
                            <strong>Status:</strong>
                            <span class="badge bg-{{ $application->voucher->status_badge }}">{{ ucfirst($application->voucher->status) }}</span>
                        </div>
                        <div class="col-md-3"><strong>Due:</strong> {{ $application->voucher->due_date->format('d M Y') }}</div>
                    </div>
                    @if($application->voucher->issuedBy)
                    <div class="row mt-2">
                        <div class="col-md-3"><strong>Issued By:</strong> {{ $application->voucher->issuedBy->full_name ?? $application->voucher->issuedBy->username }}</div>
                        <div class="col-md-3"><strong>Issue Date:</strong> {{ $application->voucher->issue_date->format('d M Y') }}</div>
                        @if($application->voucher->confirmedBy)
                        <div class="col-md-3"><strong>Confirmed By:</strong> {{ $application->voucher->confirmedBy->full_name ?? $application->voucher->confirmedBy->username }}</div>
                        <div class="col-md-3"><strong>Payment Date:</strong> {{ $application->voucher->payment_date ? $application->voucher->payment_date->format('d M Y') : '—' }}</div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column: Actions --}}
        <div class="col-lg-4">
            {{-- Paid Voucher Upload Image --}}
            @if($application->paid_voucher_path)
            <div class="card mb-3">
                <div class="card-header"><i class="fas fa-image me-2"></i>Uploaded Payment Proof</div>
                <div class="card-body text-center">
                    <img src="{{ Storage::disk('public')->url($application->paid_voucher_path) }}" alt="Paid Voucher" class="img-fluid rounded border">
                </div>
            </div>
            @endif

            {{-- Action: Review (if submitted/under_review) --}}
            @if(in_array($application->status, ['submitted', 'under_review']))
            <div class="card mb-3 border-primary">
                <div class="card-header bg-primary text-white"><i class="fas fa-gavel me-2"></i>Review Application</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admission-applications.review', $application) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="review_remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="approve" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Action: Generate Voucher (if approved) --}}
            @if($application->status === 'approved' && !$application->voucher)
            <div class="card mb-3 border-warning">
                <div class="card-header bg-warning text-dark"><i class="fas fa-receipt me-2"></i>Generate Fee Voucher</div>
                <div class="card-body">
                    <div class="d-grid mb-2">
                        <a href="{{ route('admission-applications.voucher-preview', $application) }}" class="btn btn-warning">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Review & Generate Voucher
                        </a>
                    </div>
                    <p class="small text-muted text-center mb-0">Use this to apply discounts (20%, 30%, 50%) or remove specific fee items like Library Security.</p>
                </div>
            </div>
            @endif

            {{-- Action: Confirm Payment (if fee_pending) --}}
            @if($application->status === 'fee_pending')
            <div class="card mb-3 border-success">
                <div class="card-header bg-success text-white"><i class="fas fa-money-bill-wave me-2"></i>Confirm Payment</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admission-applications.confirm-payment', $application) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Paid Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="paid_amount" class="form-control" value="{{ $application->voucher->amount ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check-circle me-1"></i> Confirm Payment
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Action: Admit Late (if fee_paid and not yet in merit list) --}}
            @if($application->status === 'fee_paid')
            <div class="card mb-3 border-info">
                <div class="card-header bg-info text-white"><i class="fas fa-user-plus me-2"></i>Late Admission</div>
                <div class="card-body">
                    <p class="small text-muted">Admit this student as a late applicant. They will be assigned the next available roll number in their subject group.</p>
                    <form method="POST" action="{{ route('admission-applications.admit-late', $application) }}" onsubmit="return confirm('Are you sure you want to admit this student as a late applicant?');">
                        @csrf
                        <button type="submit" class="btn btn-info w-100">
                            <i class="fas fa-user-plus me-1"></i> Admit (Late Entry)
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Download Voucher --}}
            @if($application->voucher)
            <div class="card mb-3">
                <div class="card-body">
                    <a href="{{ route('apply.download-voucher', $application) }}" class="btn btn-outline-primary w-100" target="_blank">
                        <i class="fas fa-download me-1"></i> Download Voucher
                    </a>
                </div>
            </div>
            @endif

            {{-- Assigned Roll Number --}}
            @if($application->assigned_roll_no)
            <div class="card mb-3 border-success">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Assigned Roll Number</h6>
                    <h2 class="text-success">{{ $application->assigned_roll_no }}</h2>
                    <span class="badge bg-primary">{{ $application->subject_group }}</span>
                </div>
            </div>
            @endif

            {{-- Timeline --}}
            <div class="card">
                <div class="card-header"><i class="fas fa-history me-2"></i>Timeline</div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <small class="text-muted">{{ $application->created_at->format('d M Y h:i A') }}</small><br>
                            <strong>Application Created</strong>
                            @if($application->createdBy)
                                <span class="text-muted">by {{ $application->createdBy->full_name ?? $application->createdBy->username }}</span>
                            @endif
                        </li>
                        @if($application->reviewed_at)
                        <li class="mb-2">
                            <small class="text-muted">{{ $application->reviewed_at->format('d M Y h:i A') }}</small><br>
                            <strong>Reviewed</strong>
                            @if($application->reviewer)
                                <span class="text-muted">by {{ $application->reviewer->full_name ?? $application->reviewer->username }}</span>
                            @endif
                            @if($application->review_remarks)
                                <br><small class="text-muted">{{ $application->review_remarks }}</small>
                            @endif
                        </li>
                        @endif
                        @if($application->fee_confirmed_at)
                        <li class="mb-2">
                            <small class="text-muted">{{ $application->fee_confirmed_at->format('d M Y h:i A') }}</small><br>
                            <strong>Payment Confirmed</strong>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
