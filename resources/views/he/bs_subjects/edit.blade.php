@extends('layouts.app')
@section('title', 'Edit BS Subject')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Subject: {{ $bsSubject->name }}</h2>
            <a href="{{ route('bs-subjects.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0">Subject Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('bs-subjects.update', $bsSubject) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code" class="form-label fw-bold">Subject Code</label>
                                <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" 
                                       placeholder="ENG-101" value="{{ old('code', $bsSubject->code) }}" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label fw-bold">Subject Name</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                       placeholder="e.g., Introduction to Linguistics" value="{{ old('name', $bsSubject->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Credit Hours</label>
                                <div class="input-group">
                                    <span class="input-group-text">Theory</span>
                                    <input type="number" name="theory_credit_hours" class="form-control" value="{{ old('theory_credit_hours', $bsSubject->theory_credit_hours) }}" min="0" max="6" required>
                                    <span class="input-group-text">Practical</span>
                                    <input type="number" name="practical_credit_hours" class="form-control" value="{{ old('practical_credit_hours', $bsSubject->practical_credit_hours) }}" min="0" max="6" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label fw-bold">Subject Type</label>
                                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="Major" {{ old('type', $bsSubject->type) == 'Major' ? 'selected' : '' }}>Major</option>
                                    <option value="Minor" {{ old('type', $bsSubject->type) == 'Minor' ? 'selected' : '' }}>Minor</option>
                                    <option value="General" {{ old('type', $bsSubject->type) == 'General' ? 'selected' : '' }}>General</option>
                                    <option value="Elective" {{ old('type', $bsSubject->type) == 'Elective' ? 'selected' : '' }}>Elective</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success py-2 fw-bold text-uppercase">
                                <i class="fas fa-save me-2"></i>Update Subject
                            </button>
                            <button type="button" class="btn btn-link text-danger" onclick="if(confirm('Are you sure you want to delete this subject?')) document.getElementById('delete-form').submit();">
                                <i class="fas fa-trash me-1"></i>Delete Subject
                            </button>
                        </div>
                    </form>
                    
                    <form id="delete-form" action="{{ route('bs-subjects.destroy', $bsSubject) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
