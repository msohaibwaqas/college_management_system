@extends('layouts.app')
@section('title', 'Create Datesheet Entry')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-plus-circle me-2"></i>Add Datesheet Entry</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('he.datesheets.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('he.datesheets.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
                        <select name="program_id" id="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
                            <option value="">Select Program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" data-semesters="{{ $program->total_semesters }}" 
                                        {{ old('program_id') == $program->id ? 'selected' : '' }}>
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
                    <div class="col-md-6">
                        <label for="bs_subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="bs_subject_id" id="bs_subject_id" class="form-select @error('bs_subject_id') is-invalid @enderror" required disabled>
                            <option value="">Select Subject</option>
                        </select>
                        @error('bs_subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="exam_type" class="form-label">Exam Type <span class="text-danger">*</span></label>
                        <select name="exam_type" id="exam_type" class="form-select @error('exam_type') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="Mid" {{ old('exam_type') == 'Mid' ? 'selected' : '' }}>Mid Term</option>
                            <option value="Final" {{ old('exam_type') == 'Final' ? 'selected' : '' }}>Final</option>
                        </select>
                        @error('exam_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="exam_date" class="form-label">Exam Date <span class="text-danger">*</span></label>
                        <input type="date" name="exam_date" id="exam_date" 
                               class="form-control @error('exam_date') is-invalid @enderror"
                               value="{{ old('exam_date') }}" required>
                        @error('exam_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="start_time" 
                               class="form-control @error('start_time') is-invalid @enderror"
                               value="{{ old('start_time', '09:00') }}" required>
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" id="end_time" 
                               class="form-control @error('end_time') is-invalid @enderror"
                               value="{{ old('end_time', '12:00') }}" required>
                        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label for="academic_year" class="form-label">Year <span class="text-danger">*</span></label>
                        <select name="academic_year" id="academic_year" class="form-select @error('academic_year') is-invalid @enderror" required>
                            @foreach($academicYears as $year)
                                <option value="{{ $year }}" {{ old('academic_year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-1"></i>Save Entry
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
    // Populate semesters when program changes
    $('#program_id').on('change', function() {
        var totalSemesters = $(this).find(':selected').data('semesters') || 8;
        var $semesterSelect = $('#semester_no');
        
        $semesterSelect.html('<option value="">Select</option>');
        for (var i = 1; i <= totalSemesters; i++) {
            $semesterSelect.append('<option value="' + i + '">Semester ' + i + '</option>');
        }
        
        $('#bs_subject_id').prop('disabled', true).html('<option value="">Select Subject</option>');
    });

    // Load subjects when semester changes
    $('#semester_no').on('change', function() {
        var programId = $('#program_id').val();
        var semesterNo = $(this).val();

        if (!programId || !semesterNo) return;

        $.get('{{ route("he.datesheets.get_subjects") }}', {
            program_id: programId,
            semester_no: semesterNo
        }, function(subjects) {
            var $subjectSelect = $('#bs_subject_id');
            $subjectSelect.html('<option value="">Select Subject</option>');
            subjects.forEach(function(subject) {
                $subjectSelect.append('<option value="' + subject.id + '">' + subject.name + ' (' + subject.code + ')</option>');
            });
            $subjectSelect.prop('disabled', false);
        });
    });
});
</script>
@endsection
