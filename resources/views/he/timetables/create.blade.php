@extends('layouts.app')

@section('title', 'Add HE Timetable Entry')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Add HE Timetable Entry</h5>
                    <a href="{{ route('he.timetables.index', ['program_id' => $preselectedProgram, 'semester_no' => $preselectedSemester, 'admission_year' => $preselectedYear]) }}" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger shadow-sm">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('he.timetables.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Program <span class="text-danger">*</span></label>
                                <select name="program_id" id="programSelect" class="form-select" required>
                                    <option value="">Select Program</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}"
                                            data-semesters="{{ $program->total_semesters }}"
                                            {{ (old('program_id', $preselectedProgram)) == $program->id ? 'selected' : '' }}>
                                            {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Batch (Admission Year) <span class="text-danger">*</span></label>
                                <select name="admission_year" class="form-select" required>
                                    <option value="">Select Batch</option>
                                    @foreach($admissionYears as $year)
                                        <option value="{{ $year }}" {{ (old('admission_year', $preselectedYear)) == $year ? 'selected' : '' }}>
                                            {{ $year }} Batch
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Semester <span class="text-danger">*</span></label>
                                <select name="semester_no" id="semesterSelect" class="form-select" required>
                                    <option value="">Select Semester</option>
                                    @for($i = 1; $i <= 8; $i++)
                                        <option value="{{ $i }}" {{ (old('semester_no', $preselectedSemester)) == $i ? 'selected' : '' }}>
                                            Semester {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Day of Week <span class="text-danger">*</span></label>
                                <select name="day_of_week" class="form-select" required>
                                    <option value="">Select Day</option>
                                    <option value="All Week" {{ old('day_of_week') == 'All Week' ? 'selected' : '' }}>📅 All Week (Mon-Sat)</option>
                                    <option value="First Three Days" {{ old('day_of_week') == 'First Three Days' ? 'selected' : '' }}>📅 First Three Days (Mon-Wed)</option>
                                    <option value="Last Three Days" {{ old('day_of_week') == 'Last Three Days' ? 'selected' : '' }}>📅 Last Three Days (Thu-Sat)</option>
                                    <option disabled>──────────</option>
                                    @foreach($days as $day)
                                        <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>
                                            {{ $day }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Time Slot <span class="text-danger">*</span></label>
                                <select name="period_number" class="form-select" required>
                                    <option value="">Select Time Slot</option>
                                    @foreach($timeSlots as $slot)
                                        <option value="{{ $slot->slot_number }}" {{ old('period_number') == $slot->slot_number ? 'selected' : '' }}>
                                            Lecture {{ $slot->slot_number }} ({{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                                <select name="bs_subject_id" id="subjectSelect" class="form-select select2" required>
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('bs_subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }} ({{ $subject->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted" id="subjectHint">
                                    <i class="fas fa-info-circle me-1"></i>Select a program and semester to load subjects for that semester's curriculum.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Room <span class="text-danger">*</span></label>
                                <select name="room_id" class="form-select" required>
                                    <option value="">Select Room</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                            {{ $room->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Teacher (Optional)</label>
                                <select name="staff_id" id="teacherSelect" class="form-select select2">
                                    <option value="">Select a subject first</option>
                                </select>
                                <small class="text-muted" id="teacherHint">
                                    <i class="fas fa-info-circle me-1"></i>Select a subject to load available teachers.
                                </small>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                                    <i class="fas fa-save me-2"></i>Save Timetable Entry
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });

        // Dynamic semester limit based on program selection
        $('#programSelect').on('change', function() {
            const maxSemesters = $(this).find(':selected').data('semesters') || 8;
            const semesterSelect = $('#semesterSelect');
            const currentVal = semesterSelect.val();
            
            semesterSelect.html('<option value="">Select Semester</option>');
            for (let i = 1; i <= maxSemesters; i++) {
                const selected = (currentVal == i) ? 'selected' : '';
                semesterSelect.append(`<option value="${i}" ${selected}>Semester ${i}</option>`);
            }

            // Also trigger subject reload
            loadSemesterSubjects();
        });

        // When semester changes, reload subjects
        $('#semesterSelect').on('change', function() {
            loadSemesterSubjects();
        });

        function loadSemesterSubjects() {
            const programId = $('#programSelect').val();
            const semesterNo = $('#semesterSelect').val();
            const subjectSelect = $('#subjectSelect');

            if (!programId || !semesterNo) {
                subjectSelect.empty().append('<option value="">Select Subject</option>');
                if ($.fn.select2) subjectSelect.trigger('change');
                $('#subjectHint').html('<i class="fas fa-info-circle me-1"></i>Select a program and semester to load subjects.');
                return;
            }

            subjectSelect.empty().append('<option value="">Loading subjects...</option>');
            if ($.fn.select2) subjectSelect.trigger('change');

            $.ajax({
                url: "{{ route('he.timetables.get_semester_subjects') }}",
                type: 'GET',
                data: { program_id: programId, semester_no: semesterNo },
                success: function(response) {
                    subjectSelect.empty().append('<option value="">Select Subject</option>');
                    
                    if (response.length === 0) {
                        subjectSelect.append('<option value="" disabled>No subjects assigned to this semester</option>');
                        $('#subjectHint').html('<i class="fas fa-exclamation-triangle me-1 text-warning"></i>No subjects found. Please assign subjects to this program\'s semester first.');
                    } else {
                        response.forEach(function(subject) {
                            subjectSelect.append(`<option value="${subject.id}">${subject.name} (${subject.code})</option>`);
                        });
                        $('#subjectHint').html(`<i class="fas fa-check-circle me-1 text-success"></i>Showing ${response.length} subjects for this semester.`);
                    }
                    
                    if ($.fn.select2) subjectSelect.trigger('change');

                    // Clear teacher when subjects reload
                    loadSubjectTeachers();
                },
                error: function() {
                    subjectSelect.empty().append('<option value="">Error loading subjects</option>');
                    if ($.fn.select2) subjectSelect.trigger('change');
                    $('#subjectHint').html('<i class="fas fa-times-circle me-1 text-danger"></i>Failed to load subjects.');
                }
            });
        }

        // When subject changes, reload teachers
        $('#subjectSelect').on('change', function() {
            loadSubjectTeachers();
        });

        function loadSubjectTeachers() {
            const subjectId = $('#subjectSelect').val();
            const teacherSelect = $('#teacherSelect');

            if (!subjectId) {
                teacherSelect.empty().append('<option value="">Select a subject first</option>');
                if ($.fn.select2) teacherSelect.trigger('change');
                $('#teacherHint').html('<i class="fas fa-info-circle me-1"></i>Select a subject to load available teachers.');
                return;
            }

            teacherSelect.empty().append('<option value="">Loading teachers...</option>');
            if ($.fn.select2) teacherSelect.trigger('change');

            $.ajax({
                url: "{{ route('he.teacher_assignment.get_teachers') }}",
                type: 'GET',
                data: { subject_id: subjectId },
                success: function(response) {
                    teacherSelect.empty().append('<option value="">Select Teacher</option>');
                    
                    if (response.length === 0) {
                        teacherSelect.append('<option value="" disabled>No specialized teachers found</option>');
                        $('#teacherHint').html('<i class="fas fa-exclamation-triangle me-1 text-warning"></i>No teachers found for this subject. Assign specializations first.');
                    } else {
                        response.forEach(function(teacher) {
                            let fullName = (teacher.first_name + ' ' + (teacher.last_name || '')).trim();
                            teacherSelect.append(`<option value="${teacher.id}">${fullName}</option>`);
                        });
                        $('#teacherHint').html(`<i class="fas fa-check-circle me-1 text-success"></i>Showing ${response.length} teacher(s) for this subject.`);
                    }
                    
                    if ($.fn.select2) teacherSelect.trigger('change');
                },
                error: function() {
                    teacherSelect.empty().append('<option value="">Error loading teachers</option>');
                    if ($.fn.select2) teacherSelect.trigger('change');
                    $('#teacherHint').html('<i class="fas fa-times-circle me-1 text-danger"></i>Failed to load teachers.');
                }
            });
        }

        // Trigger on page load if program is pre-selected
        if ($('#programSelect').val()) {
            // Only update semester limits, don't reload subjects if already pre-loaded
            const maxSemesters = $('#programSelect').find(':selected').data('semesters') || 8;
            const semesterSelect = $('#semesterSelect');
            const currentVal = semesterSelect.val();
            semesterSelect.html('<option value="">Select Semester</option>');
            for (let i = 1; i <= maxSemesters; i++) {
                const selected = (currentVal == i) ? 'selected' : '';
                semesterSelect.append(`<option value="${i}" ${selected}>Semester ${i}</option>`);
            }

            @if($preselectedProgram && $preselectedSemester && $subjects->isNotEmpty())
                $('#subjectHint').html('<i class="fas fa-check-circle me-1 text-success"></i>Showing {{ $subjects->count() }} subjects for this semester.');
            @endif
        }
    });
</script>
@endsection
