@extends('layouts.app')
@section('title', 'Edit Program')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Program: {{ $program->name }}</h2>
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
                    <form action="{{ route('programs.update', $program) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Program Name</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                   placeholder="e.g., BS English" value="{{ old('name', $program->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label fw-bold">Program Type</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="BS" {{ old('type', $program->type) == 'BS' ? 'selected' : '' }}>BS (4 Years)</option>
                                <option value="ADP" {{ old('type', $program->type) == 'ADP' ? 'selected' : '' }}>ADP (2 Years)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="total_semesters" class="form-label fw-bold">Total Semesters</label>
                            <input type="number" name="total_semesters" id="total_semesters" class="form-control @error('total_semesters') is-invalid @enderror" 
                                   value="{{ old('total_semesters', $program->total_semesters) }}" min="1" max="10" required>
                            @error('total_semesters')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold text-uppercase">
                                <i class="fas fa-save me-2"></i>Update Program
                            </button>
                            <button type="button" class="btn btn-link text-danger" onclick="if(confirm('Are you sure you want to delete this program?')) document.getElementById('delete-form').submit();">
                                <i class="fas fa-trash me-1"></i>Delete Program
                            </button>
                        </div>
                    </form>
                    
                    <form id="delete-form" action="{{ route('programs.destroy', $program) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
