@extends('layouts.app')
@section('title', 'Enter BS/ADP Marks')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-keyboard me-2"></i>Enter BS/ADP Marks</h2>
            <p class="text-muted">Select program, semester, subject, and exam type to enter marks.</p>
        </div>
    </div>

    <!-- Selection Filters -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Select Class & Exam</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
                    <select id="program_id" class="form-select select2" required>
                        <option value="">Select Program</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" 
                                    data-semesters="{{ $program->total_semesters }}"
                                    {{ (isset($preSelected['program_id']) && $preSelected['program_id'] == $program->id) ? 'selected' : '' }}>
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="semester_no" class="form-label">Semester <span class="text-danger">*</span></label>
                    <select id="semester_no" class="form-select" {{ !isset($preSelected['semester_no']) ? 'disabled' : '' }}>
                        <option value="">Select Sem</option>
                        @if(isset($preSelected['semester_no']))
                            @php 
                                $prog = $programs->where('id', $preSelected['program_id'])->first();
                                $totalSems = $prog ? $prog->total_semesters : 8;
                            @endphp
                            @for($i = 1; $i <= $totalSems; $i++)
                                <option value="{{ $i }}" {{ $preSelected['semester_no'] == $i ? 'selected' : '' }}>Semester {{ $i }}</option>
                            @endfor
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="bs_subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                    <select id="bs_subject_id" class="form-select" {{ !isset($preSelected['bs_subject_id']) ? 'disabled' : '' }}>
                        <option value="">Select Subject</option>
                        {{-- Will be populated via AJAX, but for pre-selection we might need a placeholder or trigger AJAX --}}
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="exam_type" class="form-label">Exam Type <span class="text-danger">*</span></label>
                    <select id="exam_type" class="form-select">
                        <option value="">Select Type</option>
                        <option value="Mid" {{ (isset($preSelected['exam_type']) && $preSelected['exam_type'] == 'Mid') ? 'selected' : '' }}>Mid Term</option>
                        <option value="Sessional" {{ (isset($preSelected['exam_type']) && $preSelected['exam_type'] == 'Sessional') ? 'selected' : '' }}>Sessional</option>
                        @if(!$isTeacher)
                            <option value="Final" {{ (isset($preSelected['exam_type']) && $preSelected['exam_type'] == 'Final') ? 'selected' : '' }}>Final</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="academic_year" class="form-label">Year <span class="text-danger">*</span></label>
                    <select id="academic_year" class="form-select">
                        <option value="">Select Year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ (isset($preSelected['academic_year']) && $preSelected['academic_year'] == $year) ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 text-end mt-3">
                    <button type="button" id="loadStudentsBtn" class="btn btn-primary px-4">
                        <i class="fas fa-users me-1"></i>Load Students
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Students & Marks Entry -->
    <div id="marksContainer" class="card" style="display: none;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-pencil-alt me-2"></i>Student Marks Entry</h5>
            <div>
                <button type="button" id="saveMarksBtn" class="btn btn-success me-2">
                    <i class="fas fa-save me-1"></i>Save Marks
                </button>
                <button type="button" id="lockMarksBtn" class="btn btn-warning">
                    <i class="fas fa-lock me-1"></i>Lock & Submit
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="studentsTable">
                <!-- Students table will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading data...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Populate semesters when program changes (Handle both standard change and Select2)
    $('#program_id').on('change select2:select', function() {
        console.log('Program changed (Event triggered)');
        
        // Get selected option correctly whether standard or Select2
        var $selectedOption = $(this).find('option:selected');
        var totalSemesters = $selectedOption.data('semesters') || 8;
        
        console.log('Selected Program ID:', $(this).val());
        console.log('Total Semesters:', totalSemesters);
        
        var $semesterSelect = $('#semester_no');
        
        // Clear and rebuild options
        $semesterSelect.empty().append('<option value="">Select Sem</option>');
        for (var i = 1; i <= totalSemesters; i++) {
            $semesterSelect.append('<option value="' + i + '">Semester ' + i + '</option>');
        }
        
        $semesterSelect.prop('disabled', false);
        
        // Reset Subject dropdown
        $('#bs_subject_id').html('<option value="">Select Subject</option>').prop('disabled', true);
        
        // Trigger change to update UI if Select2 is used on semester select too
        $semesterSelect.trigger('change');
    });

    // Load subjects when semester changes
    $('#semester_no').on('change select2:select', function() {
        var programId = $('#program_id').val();
        var semesterNo = $(this).val();
        console.log('Semester changed:', semesterNo, 'Program ID:', programId);

        if (programId && semesterNo) {
            console.log('Fetching subjects...');
            var $subjectSelect = $('#bs_subject_id');
            
            // Show loading state
            $subjectSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            
            $.ajax({
                url: '{{ route("he.results.get_subjects") }}',
                method: 'GET',
                data: { program_id: programId, semester_no: semesterNo },
                success: function(subjects) {
                    console.log('Subjects fetched:', subjects);
                    $subjectSelect.empty().append('<option value="">Select Subject</option>');
                    
                    if (subjects.length === 0) {
                        $subjectSelect.append('<option value="" disabled>No subjects found. Please assign subjects in Academic > Semesters.</option>');
                        // Optional: Show a helper link
                        if($('#subjectHelperLink').length === 0) {
                            $subjectSelect.after('<small id="subjectHelperLink" class="text-danger d-block mt-1"><i class="fas fa-exclamation-circle"></i> No subjects assigned. <a href="{{ route("semesters.index") }}" target="_blank">Manage Semesters</a></small>');
                        }
                    } else {
                        $('#subjectHelperLink').remove();
                        $.each(subjects, function(index, subject) {
                            $subjectSelect.append('<option value="' + subject.id + '">' + subject.name + ' (' + subject.code + ')</option>');
                        });
                        $subjectSelect.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching subjects:', xhr);
                    $subjectSelect.html('<option value="">Error loading subjects</option>');
                    alert('Error loading subjects. Check console for details.');
                }
            });
        } else {
            $('#bs_subject_id').html('<option value="">Select Subject</option>').prop('disabled', true);
        }
    });

    // Load students
    $('#loadStudentsBtn').on('click', function() {
        var programId = $('#program_id').val();
        var semesterNo = $('#semester_no').val();
        var subjectId = $('#bs_subject_id').val();
        var examType = $('#exam_type').val();
        var academicYear = $('#academic_year').val();

        if (!programId || !semesterNo || !subjectId || !examType || !academicYear) {
            alert('Please select all required fields.');
            return;
        }

        $('#loadingIndicator').show();
        $('#marksContainer').hide();

        $.ajax({
            url: '{{ route("he.results.fetch_students") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                program_id: programId,
                semester_no: semesterNo,
                bs_subject_id: subjectId,
                exam_type: examType,
                academic_year: academicYear
            },
            success: function(response) {
                $('#loadingIndicator').hide();
                $('#studentsTable').html(response.html);
                $('#marksContainer').show();
            },
            error: function(xhr) {
                $('#loadingIndicator').hide();
                alert('Error loading students: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Save marks
    $('#saveMarksBtn').on('click', function() {
        var marks = [];
        var subjectId = $('#bs_subject_id').val(); // Subject ID from selection

        $('.mark-row').each(function() {
            var $row = $(this);
            var isAbsent = $row.find('.is-absent').is(':checked');
            marks.push({
                he_admission_id: $row.data('admission-id'),
                bs_subject_id: subjectId,
                theory_marks: isAbsent ? 0 : ($row.find('.theory-marks').val() || null),
                practical_marks: isAbsent ? 0 : ($row.find('.practical-marks').val() || null),
                total_marks: isAbsent ? 0 : ($row.find('.total-marks').val() || 0),
                is_absent: isAbsent ? 1 : 0
            });
        });

        if (marks.length === 0) {
            alert('No marks to save.');
            return;
        }

        $.ajax({
            url: '{{ route("he.results.store") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                program_id: $('#program_id').val(),
                semester_no: $('#semester_no').val(),
                exam_type: $('#exam_type').val(),
                academic_year: $('#academic_year').val(),
                marks: marks
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error saving marks: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Lock marks (same logic as before, just ensure result IDs are collected correctly)
    $('#lockMarksBtn').on('click', function() {
        if (!confirm('Are you sure you want to lock these marks? Once locked, you will not be able to edit them.')) {
            return;
        }

        var resultIds = [];
        $('.mark-row').each(function() {
            var resultId = $(this).data('result-id');
            if (resultId) {
                resultIds.push(resultId);
            }
        });

        if (resultIds.length === 0) {
            alert('Please save marks first before locking.');
            return;
        }

        $.ajax({
            url: '{{ route("he.results.lock") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                result_ids: resultIds
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    // Reload students to update locked status UI
                    $('#loadStudentsBtn').click();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error locking marks: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });

    });

    // Auto-calculate totals (Event Delegation)
    $('#studentsTable').on('input', '.theory-marks, .practical-marks', function() {
        var row = $(this).closest('tr');
        var theory = parseFloat(row.find('.theory-marks').val()) || 0;
        var practical = parseFloat(row.find('.practical-marks').val()) || 0;
        row.find('.total-marks').val(theory + practical);
    });

    // Handle Absent Toggle
    $('#studentsTable').on('change', '.is-absent', function() {
        var $row = $(this).closest('tr');
        var isAbsent = $(this).is(':checked');
        
        var $inputs = $row.find('.theory-marks, .practical-marks');
        
        if (isAbsent) {
            $inputs.prop('disabled', true).val(''); // Disable and clear
            $row.find('.total-marks').val(0);
        } else {
            $inputs.prop('disabled', false); // Enable
        }
    });

    // Handle pre-selected Subject from dashboard
    var preSelectedSubjectId = "{{ $preSelected['bs_subject_id'] ?? '' }}";
    if (preSelectedSubjectId) {
        // We need to trigger subject loading first
        $('#semester_no').trigger('change');
        
        // After subjects load, we need to select the right one
        // This is tricky because AJAX is async. We'll use a MutationObserver or a simple interval check
        var subjectCheckInterval = setInterval(function() {
            var $subjectSelect = $('#bs_subject_id');
            if ($subjectSelect.find('option[value="' + preSelectedSubjectId + '"]').length > 0) {
                $subjectSelect.val(preSelectedSubjectId).trigger('change');
                clearInterval(subjectCheckInterval);
                
                // If everything is pre-selected, auto-click load
                var examType = "{{ $preSelected['exam_type'] ?? '' }}";
                var academicYear = "{{ $preSelected['academic_year'] ?? '' }}";
                if (examType && academicYear) {
                    $('#loadStudentsBtn').click();
                }
            }
        }, 500);
        
        // Timeout after 5 seconds to avoid infinite loop
        setTimeout(function() { clearInterval(subjectCheckInterval); }, 5000);
    }
});
</script>
@endsection
