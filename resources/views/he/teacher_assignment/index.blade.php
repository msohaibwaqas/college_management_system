@extends('layouts.app')
@section('title', 'HE Student-Teacher Assignment')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user-graduate me-2"></i>BS/ADP Student-Teacher Assignment</h2>
            <p class="text-muted">Assign BS/ADP students to teachers for specific subjects.</p>
        </div>
    </div>

    <!-- Selection Filters -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Select Class & Subject</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
                    <select id="program_id" class="form-select">
                        <option value="">Select Program</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" data-semesters="{{ $program->total_semesters }}">
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="semester_no" class="form-label">Semester <span class="text-danger">*</span></label>
                    <select id="semester_no" class="form-select" disabled>
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                    <select id="subject_id" class="form-select" disabled>
                        <option value="">Select Subject</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="academic_year" class="form-label">Year <span class="text-danger">*</span></label>
                    <select id="academic_year" class="form-select">
                        <option value="">Select</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                    <select id="teacher_id" class="form-select" disabled>
                        <option value="">Select Teacher</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Assignment Section -->
    <div class="row">
        <!-- Unassigned Students -->
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-user-minus me-2"></i>Unassigned Students</h6>
                    <span class="badge bg-dark" id="unassignedCount">0</span>
                </div>
                <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;" id="unassignedStudents">
                    <div class="p-3 text-muted text-center">
                        <i class="fas fa-users-slash fa-2x mb-2"></i>
                        <p class="mb-0">Select filters to load students</p>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" id="assignSelectedBtn" class="btn btn-success btn-sm w-100" disabled>
                        <i class="fas fa-arrow-right me-1"></i>Assign Selected
                    </button>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="col-md-2 d-flex align-items-center justify-content-center">
            <div class="text-center">
                <button type="button" id="assignAllBtn" class="btn btn-primary mb-2" disabled>
                    <i class="fas fa-angle-double-right"></i>
                </button>
                <br>
                <button type="button" id="unassignAllBtn" class="btn btn-danger" disabled>
                    <i class="fas fa-angle-double-left"></i>
                </button>
            </div>
        </div>

        <!-- Assigned Students -->
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-user-plus me-2"></i>Assigned to Teacher</h6>
                    <span class="badge bg-light text-dark" id="assignedCount">0</span>
                </div>
                <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;" id="assignedStudents">
                    <div class="p-3 text-muted text-center">
                        <i class="fas fa-user-check fa-2x mb-2"></i>
                        <p class="mb-0">Select teacher to load assignments</p>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" id="unassignSelectedBtn" class="btn btn-warning btn-sm w-100" disabled>
                        <i class="fas fa-arrow-left me-1"></i>Unassign Selected
                    </button>
                </div>
            </div>
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
        $semesterSelect.prop('disabled', false);
        $('#subject_id').prop('disabled', true).html('<option value="">Select Subject</option>');
    });

    // Load subjects when semester changes
    $('#semester_no').on('change', function() {
        var programId = $('#program_id').val();
        var semesterNo = $(this).val();

        if (!programId || !semesterNo) return;

        $.get('{{ route("he.teacher_assignment.get_subjects") }}', {
            program_id: programId,
            semester_no: semesterNo
        }, function(subjects) {
            var $subjectSelect = $('#subject_id');
            $subjectSelect.html('<option value="">Select Subject</option>');
            subjects.forEach(function(subject) {
                $subjectSelect.append('<option value="' + subject.id + '">' + subject.name + ' (' + subject.code + ')</option>');
            });
            $subjectSelect.prop('disabled', false);
        });
    });

    // Load teachers when subject changes
    $('#subject_id').on('change', function() {
        var subjectId = $(this).val();
        if (!subjectId) return;

        $.get('{{ route("he.teacher_assignment.get_teachers") }}', {
            subject_id: subjectId
        }, function(teachers) {
            var $teacherSelect = $('#teacher_id');
            $teacherSelect.html('<option value="">Select Teacher</option>');
            teachers.forEach(function(teacher) {
                $teacherSelect.append('<option value="' + teacher.id + '">' + teacher.first_name + ' ' + (teacher.last_name || '') + '</option>');
            });
            $teacherSelect.prop('disabled', false);
        });

        loadUnassignedStudents();
    });

    // Load assigned students when teacher changes
    $('#teacher_id').on('change', function() {
        loadAssignedStudents();
        enableButtons();
    });

    // Reload students when academic year changes
    $('#academic_year').on('change', function() {
        loadUnassignedStudents();
        loadAssignedStudents();
    });

    function loadUnassignedStudents() {
        var programId = $('#program_id').val();
        var semesterNo = $('#semester_no').val();
        var subjectId = $('#subject_id').val();
        var academicYear = $('#academic_year').val();

        if (!programId || !semesterNo || !subjectId) return;

        $.get('{{ route("he.teacher_assignment.unassigned_students") }}', {
            program_id: programId,
            semester_no: semesterNo,
            subject_id: subjectId,
            academic_year: academicYear
        }, function(response) {
            $('#unassignedStudents').html(response.html);
            $('#unassignedCount').text(response.count);
        });
    }

    function loadAssignedStudents() {
        var subjectId = $('#subject_id').val();
        var staffId = $('#teacher_id').val();
        var academicYear = $('#academic_year').val();

        if (!subjectId || !staffId) return;

        $.get('{{ route("he.teacher_assignment.assigned_students") }}', {
            subject_id: subjectId,
            staff_id: staffId,
            academic_year: academicYear
        }, function(response) {
            $('#assignedStudents').html(response.html);
            $('#assignedCount').text(response.count);
        });
    }

    function enableButtons() {
        var hasTeacher = $('#teacher_id').val();
        $('#assignSelectedBtn, #assignAllBtn').prop('disabled', !hasTeacher);
        $('#unassignSelectedBtn, #unassignAllBtn').prop('disabled', !hasTeacher);
    }

    // Assign selected students
    $('#assignSelectedBtn').on('click', function() {
        var selectedIds = [];
        $('#unassignedStudents input:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select students to assign.');
            return;
        }

        $.post('{{ route("he.teacher_assignment.assign_students") }}', {
            _token: '{{ csrf_token() }}',
            student_ids: selectedIds,
            subject_id: $('#subject_id').val(),
            staff_id: $('#teacher_id').val(),
            academic_year: $('#academic_year').val()
        }, function(response) {
            if (response.success) {
                loadUnassignedStudents();
                loadAssignedStudents();
            }
            alert(response.message);
        });
    });

    // Unassign selected students
    $('#unassignSelectedBtn').on('click', function() {
        var selectedIds = [];
        $('#assignedStudents input:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select students to unassign.');
            return;
        }

        $.post('{{ route("he.teacher_assignment.unassign_students") }}', {
            _token: '{{ csrf_token() }}',
            student_ids: selectedIds,
            subject_id: $('#subject_id').val(),
            staff_id: $('#teacher_id').val()
        }, function(response) {
            if (response.success) {
                loadUnassignedStudents();
                loadAssignedStudents();
            }
            alert(response.message);
        });
    });
});
</script>
@endsection
