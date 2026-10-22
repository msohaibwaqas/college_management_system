@extends('layouts.app')
@section('title', 'Student Details')

@section('content')
@php
    $activeRole = session('active_role');
    if (!$activeRole && Auth::check()) {
        if (Auth::user()->is_super_admin) {
            $activeRole = 'admin';
        } elseif (Auth::user()->roles->isNotEmpty()) {
            if (Auth::user()->hasRole('admin')) $activeRole = 'admin';
            elseif (Auth::user()->hasRole('principal')) $activeRole = 'principal';
            elseif (Auth::user()->hasRole('clerk')) $activeRole = 'clerk';
            elseif (Auth::user()->hasRole('teacher')) $activeRole = 'teacher';
            else $activeRole = Auth::user()->roles->first()->name;
        }
    }
@endphp

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user-graduate me-2"></i>Student Details</h2>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    @if(session('voucher_id'))
                        <a href="{{ route('students.vouchers.download', session('voucher_id')) }}" class="btn btn-sm btn-dark ms-3" target="_blank">
                            <i class="fas fa-print me-1"></i>Print Voucher
                        </a>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @php
                $backUrl = request('redirect_to') ?? url()->previous();
                if (empty($backUrl) || $backUrl === request()->fullUrl()) {
                    $backUrl = route('students.index');
                }
            @endphp
            <a href="{{ $backUrl }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
            @if($activeRole != 'teacher')
            <a href="{{ route('students.edit', [$student->id, 'redirect_to' => $backUrl]) }}" class="btn btn-primary ms-2">
                <i class="fas fa-edit me-1"></i>Edit Student
            </a>
            @php
                $latestAdmission = $student->admissions()->latest()->first();
                $isClass12 = $latestAdmission && $latestAdmission->current_class === '12';
                $hasPendingVoucher = $student->vouchers()
                    ->where('status', 'pending')
                    ->where('remarks', 'Subject Change Fee Difference')
                    ->exists();
            @endphp
            @if(!$isClass12 && !$hasPendingVoucher)
            <a href="{{ route('students.change-subjects', $student) }}" class="btn btn-info ms-2">
                <i class="fas fa-exchange-alt me-1"></i>Change Subjects
            </a>
            @endif
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="{{ $student->getProfilePictureUrl() }}" alt="Student Photo" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Name:</th>
                            <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                        </tr>
                        <tr>
                            <th>Date of Birth:</th>
                            <td>{{ $student->date_of_birth ? $student->date_of_birth->format('d M, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>CNIC:</th>
                            <td>{{ $student->attribute->student_cnic ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Blood Group:</th>
                            <td>{{ $student->attribute->bloodGroup->code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Quota:</th>
                            <td>{{ $student->attribute->quota->name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Father Information -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-male me-2"></i>Father Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Father Name:</th>
                            <td>{{ $student->father->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>CNIC:</th>
                            <td>{{ $student->father->cnic ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Contact:</th>
                            <td>{{ $student->father->contact ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Occupation:</th>
                            <td>{{ $student->father->occupation ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Annual Income:</th>
                            <td>{{ $student->father->annual_income ? 'Rs. ' . number_format($student->father->annual_income) : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Contact Information -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Student Contact:</th>
                            <td>{{ $student->contact->student_contact ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>WhatsApp:</th>
                            <td>{{ $student->contact->whatsapp_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $student->contact->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td>{{ $student->contact->address ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Admission Information -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Admission Information</h5>
                </div>
                <div class="card-body">
                    @php $admission = $student->admissions->first(); @endphp
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">College Roll No:</th>
                            <td>{{ $admission->college_roll_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>HSSC Reg No:</th>
                            <td>{{ $admission->hssc_reg_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>BISE Roll 11:</th>
                            <td>{{ $admission->bise_roll_11 ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>BISE Roll 12:</th>
                            <td>{{ $admission->bise_roll_12 ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Admission Year:</th>
                            <td>{{ $admission->admission_year ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Current Class:</th>
                            <td>{{ $admission->current_class ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td><span class="badge bg-{{ $admission->status_badge ?? 'secondary' }}">{{ $admission->status ?? 'N/A' }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- User Account Information -->
            <div class="card mb-3 border-{{ $student->user ? 'success' : 'danger' }}">
                <div class="card-header bg-{{ $student->user ? 'success' : 'danger' }} text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>User Account</h5>
                    @if($student->user)
                        <span class="badge bg-white text-success">Active Account</span>
                    @else
                        <span class="badge bg-white text-danger">No Account</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($student->user)
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Username:</th>
                                <td><code>{{ $student->user->username }}</code></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $student->user->email }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-{{ $student->user->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($student->user->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Last Login:</th>
                                <td>{{ $student->user->last_login_at ? $student->user->last_login_at->diffForHumans() : 'Never' }}</td>
                            </tr>
                        </table>
                        <div class="mt-3">
                            <form action="{{ route('users.reset_password', $student->user) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset this password?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="fas fa-key me-1"></i>Reset Password to 123
                                </button>
                            </form>
                            <a href="{{ route('users.edit', $student->user) }}" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fas fa-user-edit me-1"></i>Edit User
                            </a>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <p class="text-muted mb-3">This student does not have a user account yet.</p>
                            <form action="{{ route('users.student.create', $student) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-user-plus me-1"></i>Create Student Account
                                </button>
                            </form>
                            <small class="text-muted d-block mt-2">Default password will be <code>123</code></small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SSC Record -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-certificate me-2"></i>SSC Record</h5>
                </div>
                <div class="card-body">
                    @if($student->sscRecord)
                        <div class="row">
                            <div class="col-md-3">
                                <strong>BISE:</strong> {{ $student->sscRecord->bise->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-2">
                                <strong>Roll No:</strong> {{ $student->sscRecord->roll_no }}
                            </div>
                            <div class="col-md-2">
                                <strong>Reg No:</strong> {{ $student->sscRecord->reg_no }}
                            </div>
                            <div class="col-md-3">
                                <strong>School:</strong> {{ $student->sscRecord->school_name }}
                            </div>
                            <div class="col-md-2">
                                <strong>Marks:</strong> {{ $student->sscRecord->obtained_mark }}/{{ $student->sscRecord->total_mark }}
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <strong>Passing Year:</strong> {{ $student->sscRecord->passing_year }}
                            </div>
                            <div class="col-md-3">
                                <strong>Session:</strong> {{ $student->sscRecord->passing_session }}
                            </div>
                            <div class="col-md-3">
                                <strong>Group:</strong> {{ $student->sscRecord->ssc_group }}
                            </div>
                        </div>
                    @else
                        <p class="text-muted">No SSC record available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Subjects -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>Compulsory Subjects</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @forelse($student->compulsorySubjects as $cs)
                            @php
                                $teacher = $student->studentTeacherSubjects->where('subject_id', $cs->subject_id)->first();
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>{{ $cs->subject->name ?? 'N/A' }}</strong></span>
                                @if($teacher && $teacher->staff)
                                    <span class="badge bg-success">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>
                                        {{ $teacher->staff->first_name }} {{ $teacher->staff->last_name }}
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle me-1"></i>No Teacher
                                    </span>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No compulsory subjects assigned</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>Elective Subjects</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @forelse($student->electives->sortBy('sequence') as $elective)
                            @php
                                $teacher = $student->studentTeacherSubjects->where('subject_id', $elective->subject_id)->first();
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <span class="badge bg-primary me-2">{{ $elective->sequence }}</span>
                                    <strong>{{ $elective->subject->name ?? 'N/A' }}</strong>
                                </span>
                                @if($teacher && $teacher->staff)
                                    <span class="badge bg-success">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>
                                        {{ $teacher->staff->first_name }} {{ $teacher->staff->last_name }}
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle me-1"></i>No Teacher
                                    </span>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No elective subjects assigned</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Vouchers -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Fee Vouchers</h5>
                    <div>
                        <a href="{{ route('vouchers.fine.create', $student) }}" class="btn btn-sm btn-danger">
                            <i class="fas fa-gavel me-1"></i>Generate Fine
                        </a>
                        <span class="badge bg-dark ms-2">{{ $student->vouchers->count() }} Total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher No</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($student->vouchers->sortByDesc('created_at') as $voucher)
                                    <tr>
                                        <td>{{ $voucher->voucher_number }}</td>
                                        <td>
                                            @if(str_contains($voucher->remarks ?? '', 'Fine'))
                                                <span class="badge bg-danger">Fine</span>
                                            @elseif(str_contains($voucher->remarks ?? '', 'Class 12'))
                                                <span class="badge bg-primary">Class 12</span>
                                            @elseif(str_contains($voucher->remarks ?? '', 'Subject Change'))
                                                <span class="badge bg-info">Subject Change</span>
                                            @else
                                                <span class="badge bg-secondary">Admission</span>
                                            @endif
                                        </td>
                                        <td>{{ $voucher->issue_date->format('d-M-Y') }}</td>
                                        <td>Rs. {{ number_format($voucher->amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $voucher->status_badge }}">
                                                {{ ucfirst($voucher->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('students.vouchers.download', $voucher) }}" class="btn btn-sm btn-outline-dark" target="_blank">
                                                <i class="fas fa-print me-1"></i>Print
                                            </a>
                                            @if($voucher->status === 'pending')
                                                <button type="button" class="btn btn-sm btn-success ms-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#confirmPaymentModal"
                                                        data-voucher-id="{{ $voucher->id }}"
                                                        data-voucher-no="{{ $voucher->voucher_number }}"
                                                        data-amount="{{ $voucher->amount }}">
                                                    <i class="fas fa-check-circle me-1"></i>Confirm Payment
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">No vouchers found for this student.</td>
                                    </tr>
                                @endforelse
                                
                                {{-- Also show application voucher if exists --}}
                                @php $appVoucher = $student->admissions->first()->application->voucher ?? null; @endphp
                                @if($appVoucher && !$student->vouchers->contains($appVoucher->id))
                                    <tr>
                                        <td>{{ $appVoucher->voucher_number }} (Admission)</td>
                                        <td>{{ $appVoucher->issue_date->format('d-M-Y') }}</td>
                                        <td>Rs. {{ number_format($appVoucher->amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $appVoucher->status_badge }}">
                                                {{ ucfirst($appVoucher->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('apply.download-voucher', $appVoucher->application_id) }}" class="btn btn-sm btn-outline-dark" target="_blank">
                                                <i class="fas fa-print me-1"></i>Print
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Results -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-poll me-2"></i>Final Results</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle">Subject</th>
                                    <th colspan="2">Class 11</th>
                                    <th colspan="3">Class 12</th>
                                    <th rowspan="2" class="align-middle">Grand Total</th>
                                </tr>
                                <tr>
                                    <th>Theory</th>
                                    <th>Total</th>
                                    <th>Theory</th>
                                    <th>Practical</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Merge subjects
                                    $allSubjects = $student->compulsorySubjects->pluck('subject')
                                        ->merge($student->electives->pluck('subject'))
                                        ->sortBy('name');
                                    
                                    $grandTotalObtained = 0;
                                    $grandTotalMarks = 0;
                                @endphp
                                
                                @foreach($allSubjects as $subject)
                                    @php
                                        // Get results for this subject
                                        $res11 = $student->results->where('subject_id', $subject->id)
                                            ->where('class_level', '11')
                                            ->where('exam_type', 'Final')
                                            ->first();
                                            
                                        $res12 = $student->results->where('subject_id', $subject->id)
                                            ->where('class_level', '12')
                                            ->where('exam_type', 'Final')
                                            ->first();
                                            
                                        $obt11 = $res11 ? $res11->obtained_theory : 0;
                                        $tot11 = $subject->total_11;
                                        
                                        $obt12T = $res12 ? $res12->obtained_theory : 0;
                                        $obt12P = $res12 ? $res12->obtained_practical : 0;
                                        $tot12 = $subject->total_12;
                                        
                                        $subTotal = $obt11 + $obt12T + $obt12P;
                                        $subMax = $tot11 + $tot12;
                                        
                                        // Only add to grand total if results exist or subject is relevant
                                        // Actually, we should show total marks for all subjects assigned
                                        $grandTotalObtained += $subTotal;
                                        $grandTotalMarks += $subMax;
                                    @endphp
                                    <tr>
                                        <td class="text-start fw-bold">{{ $subject->name }}</td>
                                        
                                        <!-- 11th -->
                                        <td>{{ $res11 ? $obt11 : '-' }}</td>
                                        <td>{{ $tot11 }}</td>
                                        
                                        <!-- 12th -->
                                        <td>{{ $res12 ? $obt12T : '-' }}</td>
                                        <td>{{ $res12 && $subject->practical_12 > 0 ? $obt12P : '-' }}</td>
                                        <td>{{ $tot12 }}</td>
                                        
                                        <!-- Grand Total -->
                                        <td class="fw-bold">{{ $subTotal }} / {{ $subMax }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-secondary fw-bold">
                                    <td class="text-end">Total:</td>
                                    <td colspan="2"></td>
                                    <td colspan="3"></td>
                                    <td>{{ $grandTotalObtained }} / {{ $grandTotalMarks }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if($student->vouchers->where('status', 'pending')->isNotEmpty())
<!-- Confirm Payment Modal -->
<div class="modal fade" id="confirmPaymentModal" tabindex="-1" aria-labelledby="confirmPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="confirmPaymentModalLabel"><i class="fas fa-money-bill-wave me-2"></i>Confirm Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="confirmPaymentForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p>Confirming payment for Voucher: <strong id="modalVoucherNo"></strong></p>
                    <div class="mb-3">
                        <label for="paid_amount" class="form-label">Amount Paid (PKR) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="paid_amount" name="paid_amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
@if($student->vouchers->where('status', 'pending')->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const confirmPaymentModal = document.getElementById('confirmPaymentModal');
        if (confirmPaymentModal) {
            confirmPaymentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const voucherId = button.getAttribute('data-voucher-id');
                const voucherNo = button.getAttribute('data-voucher-no');
                const amount = button.getAttribute('data-amount');
                
                const form = document.getElementById('confirmPaymentForm');
                const modalVoucherNo = document.getElementById('modalVoucherNo');
                const paidAmountInput = document.getElementById('paid_amount');
                
                // Update form action URL
                form.action = "{{ route('students.vouchers.confirm-payment', ':id') }}".replace(':id', voucherId);
                modalVoucherNo.textContent = voucherNo;
                paidAmountInput.value = amount;
            });
        }
    });
</script>
@endif
@endsection
