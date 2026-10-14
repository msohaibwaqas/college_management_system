@extends('layouts.app')

@section('title', isset($holiday) ? 'Edit Holiday' : 'Add Holiday')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-calendar-day me-2"></i>{{ isset($holiday) ? 'Edit Holiday: ' . $holiday->title : 'Add Public Holiday' }}
        </h1>
        <p class="page-subtitle">Configure days where no attendance markings are required</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ isset($holiday) ? route('holidays.update', $holiday->id) : route('holidays.store') }}" method="POST">
                        @csrf
                        @if(isset($holiday))
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Holiday Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $holiday->title ?? '') }}" required placeholder="e.g. Eid-ul-Fitr Day 1">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', isset($holiday) ? $holiday->date->format('Y-m-d') : '') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Holiday Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="public" {{ old('type', $holiday->type ?? '') === 'public' ? 'selected' : '' }}>Public (National)</option>
                                    <option value="gazetted" {{ old('type', $holiday->type ?? '') === 'gazetted' ? 'selected' : '' }}>Gazetted</option>
                                    <option value="local" {{ old('type', $holiday->type ?? '') === 'local' ? 'selected' : '' }}>Local / Institutional</option>
                                </select>
                            </div>

                            <div class="col-md-12 border-top pt-3 mt-4">
                                <label class="form-label fw-bold">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Context or details regarding the holiday...">{{ old('description', $holiday->description ?? '') }}</textarea>
                            </div>

                            <div class="col-md-12 mt-3">
                                <div class="form-check form-switch mb-0 custom-switch-lg">
                                    <input class="form-check-input" type="checkbox" role="switch" id="isActiveSwitch" name="is_active" {{ old('is_active', $holiday->is_active ?? true) ? 'checked' : '' }} style="transform: scale(1.2); transform-origin: left center;">
                                    <label class="form-check-label ms-2 fw-bold text-success" for="isActiveSwitch">Active in Calendar</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="{{ route('holidays.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>{{ isset($holiday) ? 'Update Holiday' : 'Save Holiday' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
