@extends('layouts.app')

@section('title', 'Transport Management')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-bus me-2"></i>Transport Routes</h1>
            <p class="page-subtitle">Manage college bus routes, vehicles, and student assignments.</p>
        </div>
        <a href="{{ route('transport.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add New Route
        </a>
    </div>

    <div class="row mt-4">
        @foreach($routes as $route)
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-primary">{{ $route->name }}</h5>
                        <span class="badge bg-light text-dark">PKR {{ number_format($route->monthly_fee) }}/mo</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <i class="fas fa-map-marker-alt text-danger me-3 mt-1"></i>
                            <div>
                                <div class="small text-muted">Route Span</div>
                                <div class="fw-bold">{{ $route->start_point }} <i class="fas fa-long-arrow-alt-right mx-1"></i> {{ $route->end_point }}</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="small text-muted">Vehicle</div>
                                <div class="fw-bold text-truncate">{{ $route->vehicle->registration_no ?? 'Not Assigned' }}</div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="small text-muted">Students</div>
                                <div class="fw-bold">{{ $route->assignments_count }} Enrolled</div>
                            </div>
                        </div>

                        <div class="bg-light p-2 rounded mb-3 small d-flex align-items-center">
                            <i class="fas fa-user-tie me-2 text-muted"></i>
                            <span>Driver: <strong>{{ $route->driver->name ?? 'Unassigned' }}</strong></span>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-outline-secondary btn-sm">Manage Stops</a>
                            <a href="#" class="btn btn-outline-primary btn-sm">Assign Students</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
