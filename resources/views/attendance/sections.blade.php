@extends('layouts.app')

@section('title', 'Manage Attendance Sections')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Sections: {{ $subject->name }} ({{ $class }}th Class, {{ $year }})</h1>
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Classes
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="m-0 font-weight-bold text-primary">Student Sections</h6>
            
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="applyStudentAffairsSections" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync me-1"></i>Apply Student Affairs Sections
                </button>

                <div class="dropdown no-arrow">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Bulk Assign Selected <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in p-3" aria-labelledby="bulkActionDropdown" style="min-width: 250px;">
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Select Section:</label>
                            <select id="bulkSectionSelect" class="form-select form-select-sm mb-2">
                                <option value="">None (Clear Section)</option>
                                @foreach($availableSections as $secName => $blocks)
                                    <option value="{{ $secName }}">{{ $secName }} ({{ $blocks->count() }} slots)</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" id="applyBulkAssign" class="btn btn-sm btn-success w-100">Apply to Selected</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="alert alert-info py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-info-circle me-1"></i> You have <strong>{{ count($assignments) }}</strong> students. Use this page to set attendance sections for daily attendance marking.
                </div>
                <small class="text-muted"><i class="fas fa-shield-alt text-success me-1"></i> Timetable clash validation active on save</small>
            </div>

            @if($availableSections->isEmpty())
                <div class="alert alert-warning py-2">
                    <i class="fas fa-exclamation-triangle me-1"></i> No timetable blocks have been scheduled for this subject by the Admin yet. You cannot assign sections until the Admin allocates time slots.
                </div>
            @endif

            <form action="{{ route('attendance.sections.store') }}" method="POST" id="sectionsForm">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="class" value="{{ $class }}">
                <input type="hidden" name="year" value="{{ $year }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle attendance-table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center" width="5%">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th class="text-nowrap">Roll No</th>
                                <th class="col-name-father">Student Name</th>
                                <th class="col-name-father">Father Name</th>
                                <th>Section Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                @php
                                    $saSec = property_exists($assignment, 'student_affairs_section') ? $assignment->student_affairs_section : null;
                                    $activeSec = $assignment->attendance_section ?? $saSec;
                                    $normActive = strtoupper(preg_replace('/[\s\-\_\(\)]/', '', $activeSec ?? ''));
                                @endphp
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="student-checkbox" value="{{ $assignment->student_id }}">
                                    </td>
                                    <td class="text-nowrap"><strong>{{ $assignment->roll_number }}</strong></td>
                                    <td class="col-name-father">
                                        {{ $assignment->full_name }}
                                        @if($saSec)
                                            <span class="badge bg-light text-primary border ms-1" title="Section assigned in Student Affairs">SA: {{ $saSec }}</span>
                                        @endif
                                    </td>
                                    <td class="col-name-father">{{ $assignment->father_name }}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <select name="sections[{{ $assignment->student_id }}]" class="form-select section-input" data-sa-section="{{ $saSec ?? '' }}">
                                                <option value="">-- Select --</option>
                                                @foreach($availableSections as $secName => $blocks)
                                                    @php
                                                        $normOption = strtoupper(preg_replace('/[\s\-\_\(\)]/', '', $secName ?? ''));
                                                        $isSelected = ($activeSec == $secName) || (!empty($normActive) && $normActive === $normOption);
                                                    @endphp
                                                    <option value="{{ $secName }}" {{ $isSelected ? 'selected' : '' }}>
                                                        {{ $secName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No students found for this class.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($assignments) > 0)
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Select All Checkbox
    $('#selectAll').change(function() {
        $('.student-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#applyBulkAssign').click(function(e) {
        e.preventDefault();
        
        let checkedBoxes = $('.student-checkbox:checked');
        if (checkedBoxes.length === 0) {
            alert('Please select at least one student.');
            return;
        }

        let finalSection = $('#bulkSectionSelect').val();

        // Apply to checked inputs
        checkedBoxes.each(function() {
            let row = $(this).closest('tr');
            row.find('.section-input').val(finalSection);
            
            // Highlight row briefly
            row.css('background-color', '#d4edda');
            setTimeout(() => {
                row.css('background-color', '');
            }, 500);
            
            // Uncheck box
            $(this).prop('checked', false);
        });
        
        $('#selectAll').prop('checked', false);
        
        // Hide dropdown
        $('.dropdown-menu').removeClass('show');
    });

    // Helper to normalize section string for fuzzy matching (e.g. ICS-II vs ICS II)
    function normalizeSec(str) {
        return (str || '').toString().toUpperCase().replace(/[\s\-\_\(\)]/g, '');
    }

    // Apply Student Affairs sections to all dropdowns
    $('#applyStudentAffairsSections').click(function() {
        let applied = 0;
        $('.section-input').each(function() {
            let saSec = $(this).data('sa-section');
            if (saSec) {
                let normSaSec = normalizeSec(saSec);
                let matchedVal = null;

                $(this).find('option').each(function() {
                    let optVal = $(this).attr('value');
                    if (!optVal) return;
                    if (optVal === saSec || normalizeSec(optVal) === normSaSec) {
                        matchedVal = optVal;
                        return false;
                    }
                });

                if (matchedVal !== null) {
                    $(this).val(matchedVal);
                    let row = $(this).closest('tr');
                    row.css('background-color', '#e8f0fe');
                    setTimeout(() => { row.css('background-color', ''); }, 700);
                    applied++;
                }
            }
        });
        if (applied > 0) {
            alert('Applied Student Affairs section for ' + applied + ' student(s). Click "Save Changes" to validate timetable constraints and save.');
        } else {
            alert('No Student Affairs section assignments matched available timetable sections.');
        }
    });

    // Keep dropdown open when interacting with it
    $('.dropdown-menu').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
@endsection
