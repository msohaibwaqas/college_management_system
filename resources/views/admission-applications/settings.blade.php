@extends('layouts.app')
@section('title', 'Admission Settings')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Admission Settings</h1>
                <p class="text-muted">Manage admission schedules and fees for different programs</p>
            </div>
            <form action="{{ route('admissions.settings.migrate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-sync-alt me-2"></i>Migrate Existing Data
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        @foreach(['Inter', 'ADP', 'BS'] as $program)
            @php
                $settings = match($program) {
                    'Inter' => $interSettings,
                    'ADP' => $adpSettings,
                    'BS' => $bsSettings,
                };
            @endphp
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header @if($program == 'Inter') bg-primary @elseif($program == 'ADP') bg-success @else bg-info @endif text-white">
                        <h5 class="mb-0"><i class="fas fa-university me-2"></i>{{ $program }} Admissions</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admissions.settings.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="program_type" value="{{ $program }}">

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="is_open" id="isOpen{{ $program }}" {{ $settings->is_open ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="isOpen{{ $program }}">
                                    Status: <span class="{{ $settings->is_open ? 'text-success' : 'text-danger' }}">
                                        {{ $settings->is_open ? 'Open' : 'Closed' }}
                                    </span>
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Admission Start Date</label>
                                <input type="date" class="form-control" name="start_date" 
                                    value="{{ $settings->start_date ? $settings->start_date->format('Y-m-d') : '' }}">
                                <small class="text-muted">When applicants can start applying</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Admission End Date</label>
                                <input type="date" class="form-control" name="end_date" 
                                    value="{{ $settings->end_date ? $settings->end_date->format('Y-m-d') : '' }}">
                                <small class="text-muted">Deadline for applications</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Admission Year</label>
                                <input type="number" class="form-control" name="admission_year" value="{{ $settings->admission_year }}" required>
                                <small class="text-muted">e.g., 2026</small>
                            </div>


                            <div class="d-grid mt-4">
                                <button type="submit" class="btn @if($program == 'Inter') btn-primary @elseif($program == 'ADP') btn-success @else btn-info @endif">
                                    <i class="fas fa-save me-2"></i>Save {{ $program }} Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const switches = document.querySelectorAll('.form-check-input');
    switches.forEach(sw => {
        sw.addEventListener('change', function() {
            const label = this.nextElementSibling;
            const statusText = label.querySelector('span');
            if (this.checked) {
                statusText.textContent = 'Open';
                statusText.classList.remove('text-danger');
                statusText.classList.add('text-success');
            } else {
                statusText.textContent = 'Closed';
                statusText.classList.remove('text-success');
                statusText.classList.add('text-danger');
            }
        });
    });
});
</script>
@endsection
@endsection
