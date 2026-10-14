@extends('layouts.app')

@section('title', 'Graduation Batches')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-graduation-cap me-2"></i>Graduation Batches</h1>
            <p class="page-subtitle">Management of student cohorts and their graduation status.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('alumni.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-user-friends me-1"></i> Alumni Directory
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBatchModal">
                <i class="fas fa-plus me-1"></i> New Batch
            </button>
        </div>
    </div>

    <div class="row mt-4">
        @forelse($batches as $batch)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100 batch-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="batch-icon bg-soft-primary text-primary rounded p-3">
                            <i class="fas fa-layer-group fa-2x"></i>
                        </div>
                        @if($batch->is_graduated)
                            <span class="badge bg-success-soft text-success">Graduated</span>
                        @else
                            <span class="badge bg-warning-soft text-warning">In Progress</span>
                        @endif
                    </div>
                    <h5 class="fw-bold mb-1">{{ $batch->name }}</h5>
                    <p class="text-muted small mb-3">Academic Year: {{ $batch->year }}</p>
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="h3 fw-bold mb-0 me-2">{{ $batch->records_count }}</div>
                        <div class="text-muted small">Students Enrolled</div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-light text-primary fw-medium">
                            <i class="fas fa-eye me-1"></i> View Students
                        </a>
                        <button class="btn btn-outline-light text-muted border-0 btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit Batch
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <div class="text-muted">
                <i class="fas fa-folder-open fa-3x mb-3"></i>
                <p>No graduation batches have been created yet.</p>
            </div>
        </div>
        @endforelse
    </div>
</div>

<style>
.batch-card { transition: box-shadow 0.2s; border-radius: 12px; }
.batch-card:hover { box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); border: 1px solid rgba(25, 135, 84, 0.2); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.2); }
</style>
@endsection
