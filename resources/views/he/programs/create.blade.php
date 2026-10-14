@extends('layouts.app')
@section('title', 'Add Program')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Program</h2>
            <a href="{{ route('programs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">Program Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('programs.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Program Name</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                   placeholder="e.g., BS English" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label fw-bold">Program Type</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="BS" {{ old('type') == 'BS' ? 'selected' : '' }}>BS (4 Years)</option>
                                <option value="ADP" {{ old('type') == 'ADP' ? 'selected' : '' }}>ADP (2 Years)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="total_semesters" class="form-label fw-bold">Total Semesters</label>
                            <input type="number" name="total_semesters" id="total_semesters" class="form-control @error('total_semesters') is-invalid @enderror" 
                                   value="{{ old('total_semesters', 8) }}" min="1" max="10" required>
                            <small class="text-muted">Typically 8 for BS and 4 for ADP.</small>
                            @error('total_semesters')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2 fw-bold text-uppercase">
                                <i class="fas fa-save me-2"></i>Create Program
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
