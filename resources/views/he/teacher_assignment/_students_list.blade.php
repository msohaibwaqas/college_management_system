@if($students->count() > 0)
    <div class="list-group list-group-flush">
        @foreach($students as $student)
            <label class="list-group-item list-group-item-action d-flex align-items-center">
                <input type="checkbox" class="form-check-input me-2" 
                       value="{{ $type == 'unassigned' ? $student->id : $student->id }}">
                <div class="flex-grow-1">
                    <strong>{{ $type == 'unassigned' ? $student->roll_no : $student->roll_no }}</strong>
                    <br>
                    <small class="text-muted">
                        {{ $type == 'unassigned' 
                            ? (($student->student->first_name ?? '') . ' ' . ($student->student->last_name ?? ''))
                            : $student->student_name }}
                    </small>
                </div>
            </label>
        @endforeach
    </div>
@else
    <div class="p-3 text-muted text-center">
        <i class="fas fa-inbox fa-2x mb-2"></i>
        <p class="mb-0">No students found</p>
    </div>
@endif
