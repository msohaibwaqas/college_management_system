@extends('layouts.app')
@section('title', 'Edit Subject Group')

@section('content')
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('subject-groups.index') }}">Subject Groups</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>

    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white">
            <h4 class="mb-0">Edit Subject Group: {{ $subjectGroup->name }}</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('subject-groups.update', $subjectGroup) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Group Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $subjectGroup->name) }}" placeholder="e.g. Pre-Medical" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Brief details about this group">{{ old('description', $subjectGroup->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label d-block">Select Elective Subjects <span class="text-danger">*</span></label>
                    <div class="row g-3">
                        @foreach($subjects as $subject)
                            <div class="col-md-4">
                                <div class="card p-3 h-100 shadow-sm border-0 {{ in_array($subject->id, $selectedSubjects) ? 'bg-primary bg-opacity-10 border-primary' : 'bg-light' }}">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="subjects[]" value="{{ $subject->id }}" id="subject_{{ $subject->id }}" {{ in_array($subject->id, old('subjects', $selectedSubjects)) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="subject_{{ $subject->id }}">
                                            {{ $subject->name }}
                                        </label>
                                    </div>
                                    <div class="ps-4">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" name="mandatory_subjects[]" value="{{ $subject->id }}" id="mandatory_{{ $subject->id }}" {{ in_array($subject->id, old('mandatory_subjects', $mandatorySubjects)) ? 'checked' : '' }}>
                                            <label class="form-check-label small text-muted" for="mandatory_{{ $subject->id }}">Mandatory</label>
                                        </div>
                                        <div class="mt-2 slot-assignment-container" id="slot_container_{{ $subject->id }}" style="display: none;">
                                            <select name="slot_assignment_{{ $subject->id }}" class="form-select form-select-sm">
                                                <option value="0" {{ (!in_array($subject->id, old('slot1_subjects', $slot1Subjects)) && !in_array($subject->id, old('slot2_subjects', $slot2Subjects)) && !in_array($subject->id, old('slot3_subjects', $slot3Subjects))) ? 'selected' : '' }}>Any Slot</option>
                                                <option value="1" {{ in_array($subject->id, old('slot1_subjects', $slot1Subjects)) ? 'selected' : '' }}>Slot 1</option>
                                                <option value="2" {{ in_array($subject->id, old('slot2_subjects', $slot2Subjects)) ? 'selected' : '' }}>Slot 2</option>
                                                <option value="3" {{ in_array($subject->id, old('slot3_subjects', $slot3Subjects)) ? 'selected' : '' }}>Slot 3</option>
                                            </select>
                                        </div>
                                        <small class="text-muted d-block mt-1">{{ $subject->code }} | {{ $subject->class_level }}</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('subject-groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Subject Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="subjects[]"]');
    checkboxes.forEach(cb => {
        const toggleSlot = () => {
            const container = document.getElementById('slot_container_' + cb.value);
            if (container) {
                container.style.display = cb.checked ? 'block' : 'none';
            }
        };
        cb.addEventListener('change', toggleSlot);
        toggleSlot();
    });
});
</script>
