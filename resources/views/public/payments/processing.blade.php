@extends('layouts.app')

@section('title', 'Processing Payment')

@section('content')
<div class="container py-5 text-center">
    <div class="spinner-border text-primary mb-4" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h3 class="fw-bold">Processing Your Payment</h3>
    <p class="text-muted">Please check your mobile phone for a push notification or USSD prompt to enter your MPIN.</p>
    
    <div class="card shadow-sm border-0 mx-auto mt-5" style="max-width: 400px;">
        <div class="card-body">
            <h6 class="text-uppercase text-muted small fw-bold">Voucher #{{ $transaction->feeVoucher->voucher_number }}</h6>
            <h4 class="fw-bold text-primary">PKR {{ number_format($transaction->amount) }}</h4>
            <hr>
            <div class="text-start small">
                <div class="d-flex justify-content-between mb-2">
                    <span>Reference:</span>
                    <span class="fw-bold">{{ $transaction->transaction_reference }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Gateway:</span>
                    <span class="fw-bold">{{ strtoupper($transaction->gateway) }}</span>
                </div>
            </div>
        </div>
    </div>

    <p class="mt-4 small text-muted">Awaiting confirmation from the provider. Do not close this window.</p>
</div>

<script>
    // Simulate polling for confirmation (In real world: AJAX to check status)
    setTimeout(() => {
        window.location.reload();
    }, 5000);
</script>
@endsection
