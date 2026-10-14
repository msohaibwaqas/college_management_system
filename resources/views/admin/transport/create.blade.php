@extends('layouts.app')

@section('title', 'Add New Transport Route')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-plus-circle me-2"></i>New Transport Route</h1>
            <p class="page-subtitle">Configure a new vehicle route and monthly subscription fee.</p>
        </div>
        <a href="{{ route('transport.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Routes
        </a>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('transport.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Route Name / Destination</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. City Center to North Campus">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Starting Point</label>
                                <input type="text" name="start_point" class="form-control" required placeholder="e.g. Main Gate">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ending Point</label>
                                <input type="text" name="end_point" class="form-control" required placeholder="e.g. Sector G-10">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Monthly Fee (PKR)</label>
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" name="monthly_fee" class="form-control" required step="0.01">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Assign Vehicle (Optional)</label>
                                <select name="vehicle_id" class="form-select">
                                    <option value="">Select Vehicle</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}">{{ $vehicle->registration_no }} ({{ $vehicle->type }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Assign Driver (Optional)</label>
                                <select name="driver_id" class="form-select">
                                    <option value="">Select Driver</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Create Transport Route
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 bg-light shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-info-circle me-2"></i>Setup Guide</h6>
                    <p class="small text-muted mb-2"><strong>Route Name:</strong> Use a descriptive name that students can recognize easily.</p>
                    <p class="small text-muted mb-2"><strong>Monthly Fee:</strong> This amount will be automatically added to the monthly fee vouchers of assigned students.</p>
                    <p class="small text-muted"><strong>Vehicles & Drivers:</strong> You can assign them now or later if they aren't finalized yet.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
