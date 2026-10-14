@extends('layouts.app')
@section('title', 'Add Timetable Entry')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus me-2"></i>Add Timetable Entry</h2>
        <a href="{{ route('admin.timetables.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Timetables
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow" style="max-width: 800px; margin: 0 auto;">
        <div class="card-body">
            <form method="POST" action="{{ route('timetables.store') }}">
                @csrf
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Admission Year <span class="text-danger">*</span></label>
                        <select name="admission_year" class="form-select" required>
                            <option value="">Select Year</option>
                            @foreach($admissionYears as $year)
                                <option value="{{ $year }}" {{ old('admission_year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Subject Group <span class="text-danger">*</span></label>
                        <select name="subject_group_id" class="form-select" required>
                            <option value="">Select Group</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('subject_group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Day of Week <span class="text-danger">*</span></label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="">Select Day</option>
                            @foreach($days as $day)
                                <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>
                                    {{ $day }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Period <span class="text-danger">*</span></label>
                        <select name="period_number" class="form-select" required>
                            <option value="">Select Period</option>
                            @foreach($periods as $num => $times)
                                <option value="{{ $num }}" {{ old('period_number') == $num ? 'selected' : '' }}>
                                    Period {{ $num }} ({{ $times['start'] }} - {{ $times['end'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" role="switch" id="isBreakToggle" name="is_break" value="1" {{ old('is_break') ? 'checked' : '' }}>
                            <label class="form-check-label text-warning-emphasis fw-bold" for="isBreakToggle">Set as Break Period</label>
                        </div>
                        <small class="text-muted d-block mt-1">If checked, this slot will be marked as a break and no subject or teacher is required.</small>
                    </div>

                    <div id="subjectFields" class="row g-3 w-100 m-0 p-0 {{ old('is_break') ? 'd-none' : '' }}">
                        <div class="col-md-6">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <select name="subject_id" class="form-select" id="subjectSelect" {{ old('is_break') ? '' : 'required' }}>
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Room</label>
                            <select name="room_id" class="form-select" id="roomSelect">
                                <option value="">Select Room</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }} (Capacity: {{ $room->capacity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Staff (Optional)</label>
                            <select name="staff_id" class="form-select" id="staffSelect">
                                <option value="">Select Staff</option>
                                @foreach($staff as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('staff_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> The system will check for conflicts:
                            <ul class="mb-0 mt-2">
                                <li>Room availability in the selected slot</li>
                                <li>Staff availability (if assigned)</li>
                                <li>Subject not already scheduled for this group in this slot</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 text-end mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Save Entry
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isBreakToggle = document.getElementById('isBreakToggle');
        const subjectFields = document.getElementById('subjectFields');
        const subjectSelect = document.getElementById('subjectSelect');

        function toggleBreakFields() {
            if (isBreakToggle.checked) {
                subjectFields.classList.add('d-none');
                subjectSelect.removeAttribute('required');
            } else {
                subjectFields.classList.remove('d-none');
                subjectSelect.setAttribute('required', 'required');
            }
        }

        isBreakToggle.addEventListener('change', toggleBreakFields);
        
        // Initialize on load
        toggleBreakFields();
    });
</script>
@endsection
@endsection
