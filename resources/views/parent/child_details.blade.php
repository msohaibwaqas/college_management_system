@extends('layouts.app')

@section('title', $student->full_name . ' - Student Profile')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('parent.dashboard') }}" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        <h1 class="page-title mb-0">{{ $student->full_name }}</h1>
    </div>

    <div class="row">
        <!-- Sidebar: Quick Stats -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center py-4">
                    <img src="{{ $student->getProfilePictureUrl() }}" class="rounded-circle mb-3 border border-3 border-light shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                    <h4 class="fw-bold">{{ $student->full_name }}</h4>
                    <span class="badge bg-success mb-3">Currently Enrolled</span>
                    <div class="text-muted small">Class: {{ $student->current_class }} | Roll #: {{ $student->admission_no }}</div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Primary Contact</h6></div>
                <div class="card-body">
                    <div class="mb-2"><small class="text-muted d-block">Email</small> {{ $student->getContactInfo()['email'] ?? 'N/A' }}</div>
                    <div><small class="text-muted d-block">Phone</small> {{ $student->getContactInfo()['phone'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Fee Status -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Fee Vouchers</h6>
                    <span class="small text-muted">Latest records</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3 border-0">Voucher #</th>
                                    <th class="border-0">Amount</th>
                                    <th class="border-0">Due Date</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th class="pe-3 border-0 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($student->vouchers as $voucher)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $voucher->voucher_number }}</td>
                                    <td>PKR {{ number_format($voucher->amount) }}</td>
                                    <td>{{ $voucher->due_date->format('M d, Y') }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $voucher->status_badge }}">{{ strtoupper($voucher->status) }}</span>
                                    </td>
                                    <td class="pe-3 text-end">
                                        @if($voucher->status == 'pending')
                                            <a href="{{ route('payment.checkout', $voucher->voucher_number) }}" class="btn btn-sm btn-primary">Pay Online</a>
                                        @else
                                            <span class="text-muted small">No action</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($student->vouchers->isEmpty())
                        <div class="p-4 text-center text-muted">No fee vouchers found.</div>
                    @endif
                </div>
            </div>

            <!-- Attendance Preview -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Recent Attendance (This Month)</h6></div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-4">
                            <h2 class="fw-bold mb-0 text-success">22</h2>
                            <span class="text-muted small">Present</span>
                        </div>
                        <div class="col-4">
                            <h2 class="fw-bold mb-0 text-danger">2</h2>
                            <span class="text-muted small">Absent</span>
                        </div>
                        <div class="col-4">
                            <h2 class="fw-bold mb-0 text-warning">1</h2>
                            <span class="text-muted small">Late</span>
                        </div>
                    </div>
                    {{-- Full calendar would go here --}}
                    <p class="text-center text-muted small mb-0">Only summaries are currently available in the preview.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
