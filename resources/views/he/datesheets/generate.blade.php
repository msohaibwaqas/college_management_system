@extends('layouts.app')
@section('title', 'Generate Datesheet')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-magic me-2"></i>Auto Generate Datesheet</h2>
            <p class="text-muted">Automatically generate exam schedule for all subjects in a semester.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('he.datesheets.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('he.datesheets.store_generated') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
                        <select name="program_id" id="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
                            <option value="">Select Program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" data-semesters="{{ $program->total_semesters }}">
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label for="semester_no" class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester_no" id="semester_no" class="form-select @error('semester_no') is-invalid @enderror" required>
                            <option value="">Select</option>
                        </select>
                        @error('semester_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="exam_type" class="form-label">Exam Type <span class="text-danger">*</span></label>
                        <select name="exam_type" id="exam_type" class="form-select @error('exam_type') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="Mid">Mid Term</option>
                            <option value="Final">Final</option>
                        </select>
                        @error('exam_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <input type="number" name="academic_year" id="academic_year" 
                               class="form-control @error('academic_year') is-invalid @enderror"
                               value="{{ date('Y') }}" min="2020" max="2050" required>
                        @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                <h5><i class="fas fa-cog me-2"></i>Schedule Settings</h5>
                
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" 
                               class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ \Carbon\Carbon::now()->addDays(7)->toDateString() }}" required>
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" 
                               class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ \Carbon\Carbon::now()->addDays(20)->toDateString() }}" required>
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="default_start_time" class="form-label">Default Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="default_start_time" id="default_start_time" 
                               class="form-control @error('default_start_time') is-invalid @enderror"
                               value="09:00" required>
                        @error('default_start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="default_end_time" class="form-label">Default End Time <span class="text-danger">*</span></label>
                        <input type="time" name="default_end_time" id="default_end_time" 
                               class="form-control @error('default_end_time') is-invalid @enderror"
                               value="12:00" required>
                        @error('default_end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> This will generate datesheet entries for all subjects in the selected semester.
                    Existing entries will be skipped to avoid duplicates.
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-magic me-1"></i>Generate Datesheet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#program_id').on('change', function() {
        var totalSemesters = $(this).find(':selected').data('semesters') || 8;
        var $semesterSelect = $('#semester_no');
        
        $semesterSelect.html('<option value="">Select</option>');
        for (var i = 1; i <= totalSemesters; i++) {
            $semesterSelect.append('<option value="' + i + '">Semester ' + i + '</option>');
        }
    });
});
</script>
@endsection
