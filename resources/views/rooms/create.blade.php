@extends('layouts.app')
@section('title', 'Add Room')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle me-2"></i>Add New Room</h2>
        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="card shadow" style="max-width: 800px; margin: 0 auto;">
        <div class="card-body">
            <form action="{{ route('rooms.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Room Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity') }}" required min="1">
                        @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location') }}">
                        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                        <select name="purpose" class="form-select @error('purpose') is-invalid @enderror" required>
                            <option value="both" {{ old('purpose') == 'both' ? 'selected' : '' }}>Both (Lecture & Exam)</option>
                            <option value="lecture" {{ old('purpose') == 'lecture' ? 'selected' : '' }}>Lecture Only</option>
                            <option value="exam" {{ old('purpose') == 'exam' ? 'selected' : '' }}>Exam Only</option>
                        </select>
                        @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Room
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
