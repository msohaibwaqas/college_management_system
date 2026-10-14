@extends('layouts.app')

@section('title', 'HSSC Accountant Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">HSSC Accountant Dashboard</h1>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">HSSC Fees Collected</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rs. {{ number_format($collected) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending HSSC Vouchers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pending }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Intermediate Financials</h6>
                </div>
                    <div class="list-group">
                        <a href="{{ route('vouchers.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-money-check-alt me-2"></i> Intermediate Fee Collection
                        </a>
                        <a href="{{ route('vouchers.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-print me-2"></i> Print Inter Fee Vouchers
                        </a>
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection
