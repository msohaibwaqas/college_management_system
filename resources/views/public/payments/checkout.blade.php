@extends('layouts.app') 
{{-- Using app layout for styles, but this would ideally be a slim public layout --}}

@section('title', 'Secure Checkout')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Secure Fee Payment</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="text-muted small text-uppercase fw-bold">Voucher Details</label>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="fs-5 fw-bold">#{{ $voucher->voucher_number }}</span>
                            <span class="badge bg-warning text-dark">PENDING</span>
                        </div>
                        <div class="mt-1 small">{{ $voucher->applicant_name }}</div>
                    </div>

                    <div class="bg-light p-3 rounded mb-4 d-flex justify-content-between">
                        <span class="fw-bold">Total Payable:</span>
                        <span class="fs-4 fw-bold text-primary">PKR {{ number_format($voucher->amount) }}</span>
                    </div>

                    <form action="{{ route('payment.initiate', $voucher->id) }}" method="POST">
                        @csrf
                        <label class="form-label fw-bold mb-3">Select Payment Method</label>
                        
                        <div class="mb-3">
                            <input type="radio" class="btn-check" name="gateway" id="jazzcash" value="jazzcash" checked autocomplete="off">
                            <label class="btn btn-outline-danger w-100 py-3 d-flex align-items-center justify-content-center fw-bold" for="jazzcash">
                                <i class="fas fa-wallet me-2"></i> JazzCash (Mobile Account)
                            </label>
                        </div>

                        <div class="mb-4">
                            <input type="radio" class="btn-check" name="gateway" id="easypaisa" value="easypaisa" autocomplete="off">
                            <label class="btn btn-outline-success w-100 py-3 d-flex align-items-center justify-content-center fw-bold" for="easypaisa">
                                <i class="fas fa-mobile-alt me-2"></i> EasyPaisa (Mobile Account)
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Registered Mobile Number</label>
                            <input type="text" name="mobile_number" class="form-control form-control-lg" placeholder="03XXXXXXXXX" required>
                            <div class="form-text">Enter the number associated with your mobile wallet.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold">
                            Pay Now <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-white text-center py-3">
                    <img src="https://img.icons8.com/color/48/000000/visa.png" width="30">
                    <img src="https://img.icons8.com/color/48/000000/mastercard.png" width="30" class="mx-2">
                    <div class="small text-muted mt-2"><i class="fas fa-lock me-1"></i> SSL Encrypted Connection</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
