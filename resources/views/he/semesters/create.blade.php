@extends('layouts.app')
@section('title', 'Add to Curriculum')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Subject to Semester</h2>
            <a href="{{ route('semesters.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    @if($errors->has('error'))
        <div class="alert alert-danger">{{ $errors->first('error') }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0">Link Subject to Program Semester</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('semesters.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="program_id" class="form-label fw-bold">Select Program</label>
                            <select name="program_id" id="program_id" class="form-select select2 @error('program_id') is-invalid @enderror" required>
                                <option value="">Select Program...</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}"
                                        data-total-semesters="{{ $program->total_semesters }}"
                                        {{ old('program_id') == $program->id ? 'selected' : '' }}>
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('program_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="semester_no" class="form-label fw-bold">Semester</label>
                            <select name="semester_no" id="semester_no" class="form-select @error('semester_no') is-invalid @enderror" required>
                                <option value="">Select Program first...</option>
                            </select>
                            @error('semester_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="bs_subject_id" class="form-label fw-bold">Select Subject</label>
                            <select name="bs_subject_id" id="bs_subject_id" class="form-select select2 @error('bs_subject_id') is-invalid @enderror" required>
                                <option value="">Select Program First...</option>
                            </select>
                            @error('bs_subject_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success py-2 fw-bold text-uppercase">
                                <i class="fas fa-link me-2"></i>Add to Curriculum
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Self-executing function to avoid global namespace pollution
    (function($) {
        $(function() {
            const subjectSelect = $('#bs_subject_id');
            const programSelect = $('#program_id');

            console.log("Curriculum JS Loaded");

            // Initialize Select2
            programSelect.select2({ theme: 'bootstrap-5' });
            subjectSelect.select2({ theme: 'bootstrap-5' });

            function loadSubjects(programId) {
                console.log("Loading subjects for Program:", programId);
                
                if (!programId || programId === "") {
                    subjectSelect.empty().append('<option value="">Select Program First...</option>').trigger('change');
                    return;
                }

                // Visual feedback
                subjectSelect.empty().append('<option value="">Loading...</option>').trigger('change');
                subjectSelect.prop('disabled', true);

                $.ajax({
                    url: "/he/semesters/available-subjects",
                    type: "GET",
                    data: { program_id: programId },
                    success: function(response) {
                        console.log("Server responded with:", response);
                        subjectSelect.empty();
                        
                        if (response.length === 0) {
                            subjectSelect.append('<option value="">No available subjects found</option>');
                        } else {
                            subjectSelect.append('<option value="">-- Choose Subject --</option>');
                            $.each(response, function(i, item) {
                                subjectSelect.append(new Option(item.name + ' (' + item.code + ')', item.id));
                            });
                        }
                        subjectSelect.prop('disabled', false).trigger('change');
                    },
                    error: function(err) {
                        console.error("Failed to load subjects:", err);
                        subjectSelect.empty().append('<option value="">Error fetching subjects</option>');
                        subjectSelect.prop('disabled', false).trigger('change');
                    }
                });
            }

            const semesterSelect = $('#semester_no');
            const oldSemester = {{ old('semester_no', 'null') }};

            function updateSemesters(programId) {
                const selectedOption = programSelect.find('option[value="' + programId + '"]');
                const totalSemesters = parseInt(selectedOption.data('total-semesters'));

                semesterSelect.empty();

                if (!programId || isNaN(totalSemesters)) {
                    semesterSelect.append('<option value="">Select Program first...</option>');
                    return;
                }

                for (let i = 1; i <= totalSemesters; i++) {
                    const selected = (oldSemester === i) ? 'selected' : '';
                    semesterSelect.append(`<option value="${i}" ${selected}>Semester ${i}</option>`);
                }
            }

            // Bind to multiple events for maximum compatibility
            programSelect.on('change select2:select select2:clearing', function() {
                const programId = $(this).val();
                loadSubjects(programId);
                updateSemesters(programId);
            });

            // Initial check
            const startVal = programSelect.val();
            if (startVal) {
                loadSubjects(startVal);
                updateSemesters(startVal);
            }
        });
    })(jQuery);
</script>
@endsection
