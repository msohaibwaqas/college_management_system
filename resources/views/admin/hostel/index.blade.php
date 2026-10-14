@extends('layouts.app')

@section('title', 'Hostel Management')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-hotel me-2"></i>Hostel Facilities</h1>
            <p class="page-subtitle">Manage student hostels, room allotments, and maintenance.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHostelModal">
            <i class="fas fa-plus me-1"></i> Add New Hostel
        </button>
    </div>

    <div class="row mt-4">
        @foreach($hostels as $hostel)
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">{{ $hostel->name }}</h4>
                                <span class="badge {{ $hostel->type == 'boys' ? 'bg-info' : 'bg-danger' }} text-uppercase">
                                    {{ $hostel->type }} Hostel
                                </span>
                            </div>
                            <div class="text-end">
                                <div class="display-6 fw-bold text-primary">{{ $hostel->rooms_count }}</div>
                                <div class="small text-muted">Total Rooms</div>
                            </div>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="text-muted small">Max Capacity</div>
                                    <div class="fw-bold">{{ $hostel->capacity }} Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="text-muted small">Warden</div>
                                    <div class="fw-bold text-truncate">{{ $hostel->warden->first_name ?? 'Not Assigned' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('hostel.rooms', $hostel->id) }}" class="btn btn-primary w-100">
                                Manage Rooms <i class="fas fa-door-open ms-1"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
