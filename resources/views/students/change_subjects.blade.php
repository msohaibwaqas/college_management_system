@extends('layouts.app')

@section('title', 'Change Subjects - ' . $student->full_name)

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Change Subjects</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
        <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
        <li class="breadcrumb-item active">Change Subjects</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Change Elective Subjects for {{ $student->full_name }} (Roll No: {{ $student->admissions->first()->college_roll_no ?? 'N/A' }})
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('students.update-subjects', $student) }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="alert alert-info">
                            <strong>Note:</strong> Changing subjects to a Science or Computer-related group may incur additional fees (Breakage Fund/Computer Fund). If applicable, a new voucher will be generated for the difference.
                        </div>
                    </div>

                    <!-- Subject Group Selection (Cards) -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label d-block fw-bold mb-3">Choose Subject Group <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            @foreach($subjectGroups as $group)
                                <div class="col-md-4">
                                    <div class="form-check card h-100 p-3 border shadow-sm bg-white">
                                        <input class="form-check-input group-radio" type="radio" name="subject_group_id" 
                                               id="group_{{ $group->id }}" value="{{ $group->id }}"
                                               {{ (isset($groupId) && $groupId == $group->id) ? 'checked' : '' }}
                                               data-subjects='@json($group->subjects)' required>
                                        <label class="form-check-label ps-2" for="group_{{ $group->id }}">
                                            <div class="fw-bold">{{ $group->name }}</div>
                                            <div class="small text-muted">{{ $group->description }}</div>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Elective Subjects -->
                    <div class="col-md-4 mb-3">
                        <label for="elective_subject_1" class="form-label">Elective Subject 1 <span class="text-danger">*</span></label>
                        <select class="form-select elective-select" id="elective_subject_1" name="elective_subjects[]" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="elective_subject_2" class="form-label">Elective Subject 2 <span class="text-danger">*</span></label>
                        <select class="form-select elective-select" id="elective_subject_2" name="elective_subjects[]" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="elective_subject_3" class="form-label">Elective Subject 3 <span class="text-danger">*</span></label>
                        <select class="form-select elective-select" id="elective_subject_3" name="elective_subjects[]" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to change subjects? This may generate a new fee voucher.')">
                        Update Subjects & Calculate Fee
                    </button>
                    <a href="{{ route('students.show', $student) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupRadios = document.querySelectorAll('.group-radio');
    const electiveSelects = document.querySelectorAll('.elective-select');

    function applySubjectRules(e) {
        const selectedGroup = document.querySelector('.group-radio:checked');
        if (!selectedGroup) return;

        // If this was triggered by a subject change, check for DUPLICATES and CLEAR them
        if (e && e.target && e.target.classList.contains('elective-select')) {
            const changedSelect = e.target;
            const newValue = changedSelect.value;
            const allSelects = Array.from(electiveSelects);
            const changedIdx = allSelects.indexOf(changedSelect);

            if (newValue) {
                allSelects.forEach((select, index) => {
                    if (index !== changedIdx && select.value === newValue) {
                        // Clear the other slot to avoid duplicates and allow "moving"
                        select.value = '';
                    }
                });
            }
        }

        const groupSubjects = JSON.parse(selectedGroup.dataset.subjects || '[]');
        const mandatorySubjects = groupSubjects.filter(s => s.pivot.is_mandatory);
        const possibleSubjectIds = groupSubjects.map(s => s.id.toString());

        electiveSelects.forEach((select, index) => {
            const currentValue = select.value;
            const slotNum = index + 1; // 1, 2, 3
            
            if (!select.dataset.originalOptions) {
                select.dataset.originalOptions = select.innerHTML;
            }

            // 1. Reset options from backup
            select.innerHTML = select.dataset.originalOptions;

            // 1.5 Filter by SLOT if slots are defined for this group
            const slotKey = `is_slot${slotNum}`;
            const allowedForThisSlot = groupSubjects.filter(s => {
                const isS1 = s.pivot.is_slot1 == 1;
                const isS2 = s.pivot.is_slot2 == 1;
                const isS3 = s.pivot.is_slot3 == 1;
                const isThisSlot = s.pivot[slotKey] == 1;
                const hasSpecificSlot = isS1 || isS2 || isS3;
                return isThisSlot || !hasSpecificSlot;
            });
            const allowedIdsForThisSlot = allowedForThisSlot.map(s => s.id.toString());

            // 2. Filter by GROUP (only show subjects belonging to this group/slot)
            Array.from(select.options).forEach(option => {
                if (option.value && !allowedIdsForThisSlot.includes(option.value)) {
                    option.remove();
                } else if (option.value) {
                    // Check if this subject is selected in another slot
                    const isSelectedElseWhere = Array.from(electiveSelects)
                        .some((s, idx) => idx !== index && s.value === option.value);
                    
                    if (isSelectedElseWhere) {
                        // Instead of removing, we'll mark it visually so the user knows it will SWAP
                        option.text = '🔄 ' + option.text + ' (Move here)';
                    }
                }
            });

            // 3. Apply MANDATORY rules
            if (mandatorySubjects[index]) {
                const mandatoryId = mandatorySubjects[index].id.toString();
                select.value = mandatoryId;
                select.setAttribute('readonly', true);
                select.style.pointerEvents = 'none';
                select.style.backgroundColor = '#f8f9fa';
                select.style.border = '1px solid #ced4da';
            } else {
                select.removeAttribute('readonly');
                select.style.pointerEvents = 'auto';
                select.style.backgroundColor = '#fff';

                // Restore previous value if it's still an option, otherwise reset
                const stillValid = Array.from(select.options).some(o => o.value === currentValue);
                select.value = stillValid ? currentValue : (currentValue === '' ? '' : '');
            }
        });
    }

    // Set initial values from student's current subjects (BEFORE first rule run)
    const currentSubjectIds = @json($student->electives()->orderBy('sequence')->pluck('subject_id')->map(fn($id) => (string)$id)->toArray());
    if (currentSubjectIds.length > 0) {
        electiveSelects.forEach((select, i) => {
            if (currentSubjectIds[i]) {
                select.value = currentSubjectIds[i];
            }
        });
    }

    groupRadios.forEach(radio => radio.addEventListener('change', (e) => applySubjectRules(e)));
    electiveSelects.forEach(select => select.addEventListener('change', (e) => applySubjectRules(e)));
    
    // Initial trigger
    applySubjectRules();
});
</script>
@endsection
@endsection
