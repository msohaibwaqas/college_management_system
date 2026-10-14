@extends('layouts.public')
@section('title', 'Application Status Check - Online Admission Portal')

@section('content')
{{-- Hero Header --}}
<section class="page-hero-v2 text-center">
    <div class="container position-relative" style="z-index: 3;" data-aos="fade-up">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="fas fa-clipboard-check text-warning"></i> Online Admission Portal
            </span>
        </div>
        <h1 class="mb-3">Check Application Status</h1>
        <p class="lead mb-0">
            Track your admission status, fee voucher verification, and roll number assignment in real-time.
        </p>
    </div>
    <div class="page-hero-wave">
        <svg viewBox="0 0 1440 50" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,25 C360,50 720,0 1080,25 C1260,37 1380,20 1440,25 L1440,50 L0,50 Z" fill="#ffffff"></path>
        </svg>
    </div>
</section>

<section class="py-5 bg-light position-relative" style="min-height: 50vh;">
    <div class="container py-2">
        
        {{-- Search Form --}}
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8" data-aos="fade-up">
                <div class="page-card-v2 p-4 p-md-5 border-0 shadow-sm text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 text-white shadow-sm"
                         style="width: 68px; height: 68px; background: linear-gradient(135deg, var(--col-primary), var(--col-dark)); font-size: 1.6rem;">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-2" style="font-family: 'Playfair Display', serif;">Search Your Application</h3>
                    <p class="text-muted small mb-4">Enter your <strong>Application Number</strong> (e.g. <code>APP2600001</code>) or student <strong>CNIC / B-Form Number</strong>.</p>
                    
                    <form action="{{ route('apply.status') }}" method="POST">
                        @csrf
                        <div class="input-group input-group-lg shadow-xs rounded-pill overflow-hidden border">
                            <span class="input-group-text bg-white border-0 ps-4 text-primary fs-5">
                                <i class="fas fa-id-card"></i>
                            </span>
                            <input type="text" name="search_term" class="form-control border-0 ps-1 fs-6" 
                                   placeholder="Application No (e.g. APP2600001) or CNIC" required value="{{ old('search_term', $searchTerm ?? '') }}">
                            <button type="submit" class="btn btn-primary px-4 px-md-5 fw-bold shine-btn fs-6">
                                <i class="fas fa-search me-1"></i> Track Status
                            </button>
                        </div>
                    </form>

                    @if(session('error'))
                        <div class="alert alert-danger mt-4 text-start rounded-3 shadow-xs border-0 mb-0" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        </div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success mt-4 text-start rounded-3 shadow-xs border-0 mb-0" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Results Area --}}
        @if(isset($application))
        <div class="row justify-content-center" data-aos="fade-up">
            <div class="col-lg-10">
                <div class="page-card-v2 border-0 shadow-lg overflow-hidden">
                    
                    {{-- Header --}}
                    <div class="px-4 py-4 text-white d-flex flex-wrap justify-content-between align-items-center gap-3"
                         style="background: linear-gradient(135deg, var(--col-primary) 0%, var(--col-dark) 100%);">
                        <div>
                            <span class="badge bg-white text-primary rounded-pill px-3 py-1 mb-2 fw-bold" style="font-size: 0.72rem;">
                                {{ strtoupper($application->program_type ?? 'APPLICATION') }}
                            </span>
                            <h3 class="mb-1 text-white fw-bold" style="font-family: 'Playfair Display', serif;">{{ $application->full_name }}</h3>
                            <small class="text-white-50">
                                @if($application->gender === 'Male')
                                    S/O {{ $application->father_name }}
                                @elseif($application->gender === 'Female')
                                    D/O {{ $application->father_name }}
                                @else
                                    {{ $application->father_name }}
                                @endif
                            </small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-1 text-warning fw-bold font-monospace">{{ $application->application_number }}</h5>
                            <span class="badge bg-white text-{{ $application->status_badge }} px-3 py-2 rounded-pill fw-bold shadow-xs">
                                {{ $application->status_label }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <div class="row g-4">
                            <div class="col-md-7">
                                <h5 class="fw-bold text-dark border-bottom pb-3 mb-4" style="font-family: 'Playfair Display', serif;">
                                    <i class="fas fa-info-circle text-primary me-2"></i>Application Details
                                </h5>
                                
                                <table class="table table-borderless align-middle small mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted fw-semibold" width="40%">Submission Date:</td>
                                            <td class="fw-bold text-dark">{{ $application->created_at->format('d F Y, h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted fw-semibold">SSC/Matric Marks:</td>
                                            <td class="fw-bold text-dark">{{ $application->ssc_obtained_marks }} / {{ $application->ssc_total_marks }} ({{ $application->ssc_percentage }}%)</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted fw-semibold">Subject Combination:</td>
                                            <td class="fw-bold text-dark">
                                                @if($application->subject_group)
                                                    <span class="badge bg-light text-primary border mb-1">{{ $application->subject_group }}</span><br>
                                                @endif
                                                <small class="text-secondary">
                                                    {{ $application->electiveSubject1->name ?? '' }}, 
                                                    {{ $application->electiveSubject2->name ?? '' }}, 
                                                    {{ $application->electiveSubject3->name ?? '' }}
                                                </small>
                                            </td>
                                        </tr>
                                        @if($application->review_remarks && in_array($application->status, ['rejected']))
                                        <tr>
                                            <td class="text-danger fw-semibold">Review Remarks:</td>
                                            <td class="fw-bold text-danger">{{ $application->review_remarks }}</td>
                                        </tr>
                                        @endif
                                        @if($application->assigned_roll_no)
                                        <tr>
                                            <td class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i> Assigned Roll No:</td>
                                            <td><h4 class="text-success fw-bold mb-0 font-monospace">{{ $application->assigned_roll_no }}</h4></td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            {{-- Action Area (Voucher) --}}
                            <div class="col-md-5">
                                <div class="p-4 rounded-4 bg-light border h-100 d-flex flex-column justify-content-center">
                                    <h5 class="text-center fw-bold text-dark mb-3">Next Action Required</h5>

                                    @if($application->status === 'submitted' || $application->status === 'under_review')
                                        <div class="text-center text-muted">
                                            <i class="fas fa-hourglass-half fa-3x mb-3 text-warning"></i>
                                            <p class="small mb-0">Your application is currently under review by the college admission office. Please check back soon.</p>
                                        </div>
                                    @elseif($application->status === 'rejected')
                                        <div class="text-center text-danger mb-4">
                                            <i class="fas fa-times-circle fa-3x mb-3"></i>
                                            <p class="small mb-3">Your application was not approved. Please review the remarks or correct details.</p>
                                            <div class="d-grid">
                                                <a href="{{ route('apply.edit', ['application' => $application, 'search_term' => $searchTerm]) }}" class="btn btn-danger fw-bold rounded-pill">
                                                    <i class="fas fa-edit me-2"></i> Correct Application
                                                </a>
                                            </div>
                                        </div>
                                    @elseif(($application->status === 'approved' || $application->status === 'fee_pending') && $application->voucher && !$application->paid_voucher_path)
                                        <div class="text-center mb-4">
                                            <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-2"></i>
                                            <p class="small text-muted mb-3">Fee voucher generated! Download it, deposit <strong>Rs. {{ number_format($application->voucher->amount) }}</strong>, and upload the paid receipt.</p>
                                            <a href="{{ route('apply.download-voucher', $application) }}" target="_blank" class="btn btn-outline-primary fw-bold rounded-pill w-100 mb-3">
                                                <i class="fas fa-download me-2"></i> Download Fee Voucher
                                            </a>
                                        </div>
                                        
                                        <form action="{{ route('apply.upload-voucher', $application) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-dark">Upload Paid Voucher Image</label>
                                                <input type="file" name="paid_voucher" class="form-control form-control-sm" accept="image/*" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shine-btn">
                                                <i class="fas fa-upload me-2"></i> Upload Receipt Proof
                                            </button>
                                        </form>

                                    @elseif($application->status === 'fee_pending' && $application->paid_voucher_path)
                                        <div class="text-center">
                                            <i class="fas fa-tasks fa-3x text-info mb-3"></i>
                                            <div class="alert alert-info py-2 mb-3 rounded-3 border-0">
                                                <strong>Payment Proof Uploaded</strong>
                                            </div>
                                            <p class="small text-muted mb-0">The accounts section is verifying your paid voucher. Your status will update upon verification.</p>
                                        </div>
                                    @elseif($application->status === 'fee_paid' || $application->status === 'admitted')
                                        <div class="text-center">
                                            <i class="fas fa-clipboard-check fa-4x text-success mb-3"></i>
                                            <h5 class="text-success fw-bold">Fee Verified!</h5>
                                            <p class="text-muted small mb-0">Your admission fee payment has been confirmed by the accounts office.</p>
                                            @if($application->status === 'admitted')
                                                <div class="alert alert-success mt-3 py-2 mb-0 rounded-3 border-0">
                                                    <strong>Congratulations!</strong> Admitted to GGC Chishtian.
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-center text-muted">
                                            Please wait for further updates.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection
