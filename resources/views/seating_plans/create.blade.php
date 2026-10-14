@extends('layouts.app')
@section('title', 'Generate Seating Plan')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-magic me-2"></i>Generate Seating Plan</h2>
        <a href="{{ route('seating-plans.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="card shadow" style="max-width: 600px; margin: 0 auto;">
        <div class="card-body">
            <form action="{{ route('seating-plans.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Select Test Date <span class="text-danger">*</span></label>
                    <select name="test_date" class="form-select @error('test_date') is-invalid @enderror" required>
                        <option value="">-- Select a Date --</option>
                        @forelse($availableDates as $date)
                            <option value="{{ $date->format('Y-m-d') }}" {{ old('test_date') == $date->format('Y-m-d') ? 'selected' : '' }}>
                                {{ $date->format('l, d F Y') }}
                            </option>
                        @empty
                            <option value="" disabled>No dates available</option>
                        @endforelse
                    </select>
                    @error('test_date') 
                        <div class="invalid-feedback">{{ $message }}</div> 
                    @enderror
                    @error('error')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Only dates with scheduled tests and no existing seating plan are shown.</small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>How it works:</strong>
                    <ul class="mb-0 mt-2">
                        <li>System will fetch all students who have exams on the selected date</li>
                        <li>Students will be sorted by admission year and roll number</li>
                        <li>They will be distributed across active rooms based on capacity</li>
                        <li>Each student gets a unique seat number</li>
                    </ul>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-cogs me-2"></i>Generate Seating Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
