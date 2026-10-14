<div class="table-responsive">
    <table class="table table-bordered table-striped mb-0 align-middle">
        <thead class="table-dark">
            <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 100px;">Roll No</th>
                <th>Student Name</th>
                
                @if($examType === 'Sessional')
                    <th style="width: 100px;" class="text-center">Sessional</th>
                @elseif($examType === 'Mid')
                     <th style="width: 100px;" class="text-center">Mid Term</th>
                @else
                    {{-- Final Exam --}}
                    <th style="width: 100px;" class="text-center">Theory</th>
                    @if($subject->practical_credit_hours > 0)
                        <th style="width: 100px;" class="text-center">Practical</th>
                    @endif
                @endif
                
                @if($examType === 'Final')
                    <th style="width: 100px;" class="text-center">Total</th>
                @endif
                
                <th style="width: 80px;" class="text-center">Absent</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $admission)
                @php
                    $existingResult = $existingResults[$admission->id] ?? null;
                    $isLocked = $existingResult ? $existingResult->is_locked : false;
                    $canEdit = !$isLocked || $canEditLocked;
                    $isAbsent = $existingResult ? $existingResult->is_absent : false;
                @endphp
                <tr class="mark-row" 
                    data-admission-id="{{ $admission->id }}" 
                    data-subject-id="{{ $subject->id }}"
                    data-result-id="{{ $existingResult ? $existingResult->id : '' }}">
                    <td>{{ $index + 1 }}</td>
                    <td class="fw-bold">{{ $admission->roll_no }}</td>
                    <td>
                        <div class="fw-bold">{{ $admission->student->full_name }}</div>
                        <small class="text-muted">{{ $admission->student->father->name ?? '' }}</small>
                    </td>
                    
                    @if($examType === 'Sessional')
                        <td>
                            <input type="number" 
                                   class="form-control form-control-sm theory-marks text-center" 
                                   min="0" max="100"
                                   value="{{ $existingResult ? $existingResult->theory_marks : '' }}"
                                   {{ !$canEdit || $isAbsent ? 'disabled' : '' }}
                                   placeholder="-">
                            {{-- Hidden fields for compatibility --}}
                            <input type="hidden" class="practical-marks" value="0">
                        </td>
                    @elseif($examType === 'Mid')
                        <td>
                            <input type="number" 
                                   class="form-control form-control-sm theory-marks text-center" 
                                   min="0" max="100"
                                   value="{{ $existingResult ? $existingResult->theory_marks : '' }}"
                                   {{ !$canEdit || $isAbsent ? 'disabled' : '' }}
                                   placeholder="-">
                             {{-- Hidden fields for compatibility --}}
                            <input type="hidden" class="practical-marks" value="0">
                        </td>
                    @else
                        {{-- Final Exam --}}
                        <td>
                            <input type="number" 
                                   class="form-control form-control-sm theory-marks text-center" 
                                   min="0" max="100"
                                   value="{{ $existingResult ? $existingResult->theory_marks : '' }}"
                                   {{ !$canEdit || $isAbsent ? 'disabled' : '' }}
                                   placeholder="-">
                        </td>
                        @if($subject->practical_credit_hours > 0)
                            <td>
                                <input type="number" 
                                       class="form-control form-control-sm practical-marks text-center" 
                                       min="0" max="100"
                                       value="{{ $existingResult ? $existingResult->practical_marks : '' }}"
                                       {{ !$canEdit || $isAbsent ? 'disabled' : '' }}
                                       placeholder="-">
                            </td>
                        @else
                             <input type="hidden" class="practical-marks" value="0">
                        @endif
                    @endif

                    @if($examType === 'Final')
                        <td>
                            <input type="number" 
                                   class="form-control form-control-sm total-marks text-center fw-bold" 
                                   min="0" max="200"
                                   value="{{ $existingResult ? $existingResult->total_marks : '' }}"
                                   {{ !$canEdit ? 'disabled' : '' }}
                                   placeholder="0" readonly tabindex="-1">
                        </td>
                    @else
                         {{-- For Mid/Sessional, total is calculated implicitly or handled by controller. 
                              Displaying it might be redundant if it equals the single input. 
                              But hidden input is needed for JS calculation if we keep it generic. --}}
                        <input type="hidden" class="total-marks" value="{{ $existingResult ? $existingResult->total_marks : '' }}">
                    @endif

                    <td class="text-center">
                        <input type="checkbox" 
                               class="form-check-input is-absent"
                               {{ $isAbsent ? 'checked' : '' }}
                               {{ !$canEdit ? 'disabled' : '' }}>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        No students found for this selection.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($students->count() > 0)
<div class="p-3 bg-light border-top">
    <div class="row align-items-center">
        <div class="col-md-6">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Subject: <strong>{{ $subject->name }}</strong> ({{ $subject->code }}) | 
                Total Students: <strong>{{ $students->count() }}</strong>
            </small>
        </div>
        <div class="col-md-6 text-end">
            <small class="text-muted">
                @if(!$canEditLocked)
                    <i class="fas fa-lock text-warning me-1"></i>
                    Locked marks cannot be edited.
                @else
                    <i class="fas fa-unlock text-success me-1"></i>
                    You can edit locked marks.
                @endif
            </small>
        </div>
    </div>
</div>
@endif
