@extends('layouts.app')

@section('title', 'Generate Fine Voucher')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-gavel me-2"></i>Generate Fine Voucher</h4>
                <a href="{{ route('students.show', $student) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Student
                </a>
            </div>
            <div class="card-body">
                {{-- Student Info --}}
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Student:</strong> {{ $student->full_name }}</p>
                                <p class="mb-1"><strong>Father:</strong> {{ $student->father?->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Class:</strong> {{ $student->current_class }}</p>
                                <p class="mb-1"><strong>Roll No:</strong> {{ $student->admissions->first()?->college_roll_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Fine Form --}}
                <form action="{{ route('vouchers.fine.store', $student) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="amount" class="form-label"><strong>Fine Amount (Rs.)</strong></label>
                        <input type="number" name="amount" id="amount" class="form-control form-control-lg"
                            min="50" max="2000" step="50" required placeholder="e.g. 500"
                            value="{{ old('amount') }}">
                        <div class="form-text">Minimum Rs. 50, Maximum Rs. 2,000</div>
                        @error('amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="remarks" class="form-label"><strong>Reason for Fine</strong></label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="3" required
                            placeholder="Describe the misconduct or reason for the fine..."
                        >{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Are you sure you want to generate this fine voucher?')">
                            <i class="fas fa-gavel me-1"></i>Generate Fine Voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
