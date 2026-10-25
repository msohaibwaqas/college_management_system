@extends('layouts.app')

@section('title', 'Voucher Details - ' . $voucher->voucher_number)

@section('content')
<div class="card">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Voucher: {{ $voucher->voucher_number }}</h4>
        <div>
            <a href="{{ route('students.vouchers.download', $voucher) }}" class="btn btn-outline-secondary" target="_blank">
                <i class="fas fa-download me-1"></i>Download
            </a>
            <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Voucher Info --}}
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-info-circle fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Voucher Information</h5></div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr><th width="40%">Voucher Number</th><td>{{ $voucher->voucher_number }}</td></tr>
                            <tr><th>Student</th><td>
                                @if($voucher->student)
                                    <a href="{{ route('students.show', $voucher->student) }}">{{ $voucher->applicant_name }}</a>
                                @else
                                    {{ $voucher->applicant_name }}
                                @endif
                            </td></tr>
                            <tr><th>Father's Name</th><td>{{ $voucher->father_name }}</td></tr>
                            <tr><th>Type</th><td>{{ $voucher->remarks }}</td></tr>
                            <tr><th>Status</th><td>
                                <span class="badge bg-{{ $voucher->status_badge }} fs-6">{{ ucfirst($voucher->status) }}</span>
                                @if($voucher->is_overdue) <span class="badge bg-danger">Overdue</span> @endif
                            </td></tr>
                            <tr><th>Issue Date</th><td>{{ $voucher->issue_date?->format('d M Y') }}</td></tr>
                            <tr><th>Due Date</th><td>{{ $voucher->due_date?->format('d M Y') }}</td></tr>
                            @if($voucher->payment_date)
                            <tr><th>Payment Date</th><td>{{ $voucher->payment_date->format('d M Y') }}</td></tr>
                            @endif
                            <tr><th>Issued By</th><td>{{ $voucher->issuedBy?->name ?? 'System' }}</td></tr>
                            @if($voucher->confirmedBy)
                            <tr><th>Confirmed By</th><td>{{ $voucher->confirmedBy->name }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            {{-- Financial Summary --}}
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-chart-line fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Financial Summary</h5></div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr><th width="40%">Total Amount</th><td class="fs-5"><strong>Rs. {{ number_format($voucher->amount) }}</strong></td></tr>
                            <tr><th>Paid Amount</th><td class="fs-5 text-success"><strong>Rs. {{ number_format($voucher->paid_amount) }}</strong></td></tr>
                            <tr><th>Remaining</th><td class="fs-5 text-danger"><strong>Rs. {{ number_format($voucher->remaining_amount) }}</strong></td></tr>
                        </table>

                        <hr>
                        <h6>Bank Details</h6>
                        <table class="table table-borderless mb-0">
                            <tr><th width="40%">Bank</th><td>{{ $voucher->bank_name }}</td></tr>
                            <tr><th>Account #</th><td>{{ $voucher->account_number }}</td></tr>
                            <tr><th>Branch</th><td>{{ $voucher->bank_branch }}</td></tr>
                        </table>
                    </div>
                </div>

                {{-- Confirm Payment --}}
                @if($voucher->status === 'pending')
                <div class="card mb-3 border-success">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-success-subtle text-success me-2 p-2 rounded-circle"><i class="fas fa-check-circle fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Confirm Payment</h5></div>
                    <div class="card-body">
                        <form action="{{ route('vouchers.confirm', $voucher) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Paid Amount</label>
                                <input type="number" name="paid_amount" class="form-control" value="{{ $voucher->amount }}" required min="0" step="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-check me-1"></i>Confirm Payment</button>
                        </form>
                    </div>
                </div>

                <form action="{{ route('vouchers.cancel', $voucher) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                    @csrf
                    <button type="submit" class="btn btn-danger w-100"><i class="fas fa-times me-1"></i>Cancel Voucher</button>
                </form>
                @endif
            </div>
        </div>

        {{-- Voucher Items --}}
        <div class="card mt-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-list-ol fa-fw"></i></span><h5 class="mb-0 fw-bold text-dark">Voucher Items</h5></div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fee Head</th>
                            <th class="text-end">Amount</th>
                            <th>Refundable</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-end">Rs. {{ number_format($item->amount) }}</td>
                            <td>
                                @if($item->is_refundable)
                                    <span class="badge bg-info">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="2">Total</th>
                            <th class="text-end">Rs. {{ number_format($voucher->items->sum('amount')) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
