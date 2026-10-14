@extends('layouts.app')
@section('title', 'Schedule Test')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-plus me-2"></i>Schedule New Test</h2>
        <a href="{{ route('datesheets.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Test Details</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('datesheets.store') }}" method="POST" id="scheduleForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Admission Year</label>
                        <select name="admission_year" class="form-select" required>
                            <option value="">Select Year</option>
                            @foreach($admissionYears as $year)
                                <option value="{{ $year }}" {{ old('admission_year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Test Date</label>
                        <input type="date" name="test_date" id="test_date" class="form-control" value="{{ old('test_date') }}" required min="{{ date('Y-m-d') }}">
                        <div class="form-text text-danger d-none" id="sundayError">Tests cannot be scheduled on Sunday.</div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Subjects (Select Multiple)</label>
                        <select name="subject_ids[]" class="form-select" multiple required size="10">
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ (collect(old('subject_ids'))->contains($subject->id)) ? 'selected' : '' }}>
                                    {{ $subject->name }} ({{ $subject->type }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple subjects.</div>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Schedule Test(s)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    $('#test_date').on('change', function() {
        const dateVal = $(this).val();
        if (dateVal) {
            const date = new Date(dateVal);
            if (date.getDay() === 0) { // 0 is Sunday
                $('#sundayError').removeClass('d-none');
                $(this).addClass('is-invalid');
                $(this).val(''); // Clear input
            } else {
                $('#sundayError').addClass('d-none');
                $(this).removeClass('is-invalid');
            }
        }
    });
});
</script>
@endsection
@endsection
