@extends('layouts.app')

@section('title', 'HE Timetable')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-alt me-2"></i>Higher Education Timetable</h2>
        <a href="{{ route('he.timetables.create', ['program_id' => $selectedProgram, 'semester_no' => $selectedSemester, 'admission_year' => $selectedYear]) }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Entry
        </a>
    </div>

    {{-- Filter Form --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('he.timetables.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Program</label>
                    <select name="program_id" class="form-select" required>
                        <option value="">Select Program</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" {{ $selectedProgram == $program->id ? 'selected' : '' }}>
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Batch (Admission Year)</label>
                    <select name="admission_year" class="form-select" required>
                        <option value="">Select Batch</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                {{ $year }} Batch
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Semester</label>
                    <select name="semester_no" class="form-select" required id="semesterSelect">
                        <option value="">Select Semester</option>
                        @for($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" {{ $selectedSemester == $i ? 'selected' : '' }}>
                                Semester {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-search me-2"></i>View
                    </button>
                </div>
                @if($selectedProgram && $selectedSemester && $selectedYear)
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#cloneModal">
                        <i class="fas fa-copy me-2"></i>Clone
                    </button>
                </div>
                @endif
            </form>
        </div>
    </div>

    @if(session('warning'))
        <div class="alert alert-warning shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i>{!! session('warning') !!}
        </div>
    @endif

    @if($selectedProgram && $selectedSemester && $selectedYear)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-primary mb-0">
                <i class="fas fa-graduation-cap me-2"></i>{{ $programs->find($selectedProgram)->name ?? 'Unknown' }}
                — Semester {{ $selectedSemester }}
                <span class="badge bg-info ms-2">{{ $selectedYear }} Batch</span>
            </h5>
            @if($timetables->isNotEmpty())
                <form action="{{ route('he.timetables.destroy_all') }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="program_id" value="{{ $selectedProgram }}">
                    <input type="hidden" name="semester_no" value="{{ $selectedSemester }}">
                    <input type="hidden" name="admission_year" value="{{ $selectedYear }}">
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete ALL timetable entries for this batch/semester? This cannot be undone.')">
                        <i class="fas fa-trash me-1"></i>Clear All
                    </button>
                </form>
            @endif
        </div>

        @if($timetables->isNotEmpty())
            <div class="row g-4">
                {{-- Session 1: Monday to Wednesday --}}
                <div class="col-12 col-xl-6">
                    <div class="card shadow h-100">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0 text-center"><i class="fas fa-calendar-day me-2"></i>Monday – Wednesday</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th style="width: 100px;">Period</th>
                                            @foreach(['Monday', 'Tuesday', 'Wednesday'] as $day)
                                                <th>{{ $day }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($timeSlots as $slot)
                                            <tr>
                                                <td class="text-center bg-light fw-bold align-middle">
                                                    Lecture {{ $slot->slot_number }}<br>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}</small>
                                                </td>
                                                @foreach(['Monday', 'Tuesday', 'Wednesday'] as $day)
                                                    <td class="align-middle text-center p-2" style="min-height: 80px; width: 30%;">
                                                        @php
                                                            $entry = $timetables->get($day)?->where('period_number', $slot->slot_number)->first();
                                                        @endphp
                                                        @if($entry)
                                                            <div class="p-2 border rounded bg-white position-relative shadow-sm">
                                                                <div class="fw-bold mb-1 text-primary">{{ $entry->subject->name }}</div>
                                                                <div class="small"><i class="fas fa-door-open me-1"></i>{{ $entry->room->name }}</div>
                                                                <div class="small text-muted"><i class="fas fa-user-tie me-1"></i>{{ $entry->staff->full_name ?? 'No Teacher' }}</div>
                                                                
                                                                <form action="{{ route('he.timetables.destroy', $entry) }}" method="POST" class="position-absolute top-0 end-0 p-1">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" onclick="return confirm('Delete this entry?')">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        @else
                                                            <span class="text-muted small fst-italic">- Free -</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Session 2: Thursday to Saturday --}}
                <div class="col-12 col-xl-6">
                    <div class="card shadow h-100">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0 text-center"><i class="fas fa-calendar-day me-2"></i>Thursday – Saturday</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th style="width: 100px;">Period</th>
                                            @foreach(['Thursday', 'Friday', 'Saturday'] as $day)
                                                <th>{{ $day }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($timeSlots as $slot)
                                            <tr>
                                                <td class="text-center bg-light fw-bold align-middle">
                                                    Lecture {{ $slot->slot_number }}<br>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}</small>
                                                </td>
                                                @foreach(['Thursday', 'Friday', 'Saturday'] as $day)
                                                    <td class="align-middle text-center p-2" style="min-height: 80px; width: 30%;">
                                                        @php
                                                            $entry = $timetables->get($day)?->where('period_number', $slot->slot_number)->first();
                                                        @endphp
                                                        @if($entry)
                                                            <div class="p-2 border rounded bg-white position-relative shadow-sm">
                                                                <div class="fw-bold mb-1 text-primary">{{ $entry->subject->name }}</div>
                                                                <div class="small"><i class="fas fa-door-open me-1"></i>{{ $entry->room->name }}</div>
                                                                <div class="small text-muted"><i class="fas fa-user-tie me-1"></i>{{ $entry->staff->full_name ?? 'No Teacher' }}</div>
                                                                
                                                                <form action="{{ route('he.timetables.destroy', $entry) }}" method="POST" class="position-absolute top-0 end-0 p-1">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" onclick="return confirm('Delete this entry?')">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        @else
                                                            <span class="text-muted small fst-italic">- Free -</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info shadow-sm">
                <i class="fas fa-info-circle me-2"></i>No timetable entries found for this program, semester, and batch.
                <a href="{{ route('he.timetables.create', ['program_id' => $selectedProgram, 'semester_no' => $selectedSemester, 'admission_year' => $selectedYear]) }}" class="alert-link ms-2">
                    <i class="fas fa-plus me-1"></i>Add the first entry
                </a>
            </div>
        @endif
    @else
        <div class="alert alert-primary shadow-sm text-center py-4">
            <i class="fas fa-hand-point-up fa-2x mb-3 d-block"></i>
            <h5>Please select a program, batch, and semester to view the timetable.</h5>
        </div>
    @endif
</div>

{{-- Clone Modal --}}
@if($selectedProgram && $selectedSemester && $selectedYear)
<div class="modal fade" id="cloneModal" tabindex="-1" aria-labelledby="cloneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('he.timetables.clone') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="cloneModalLabel"><i class="fas fa-copy me-2"></i>Clone Timetable</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>Source:</strong> {{ $programs->find($selectedProgram)->name ?? '' }} — Semester {{ $selectedSemester }} ({{ $selectedYear }} Batch)
                    </div>

                    <input type="hidden" name="source_program_id" value="{{ $selectedProgram }}">
                    <input type="hidden" name="source_semester_no" value="{{ $selectedSemester }}">
                    <input type="hidden" name="source_admission_year" value="{{ $selectedYear }}">

                    <h6 class="fw-bold text-muted mb-3">Clone To:</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Program</label>
                        <select name="target_program_id" class="form-select" required>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" {{ $selectedProgram == $program->id ? 'selected' : '' }}>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Batch (Admission Year)</label>
                        <select name="target_admission_year" class="form-select" required>
                            @foreach($admissionYears as $year)
                                <option value="{{ $year }}">{{ $year }} Batch</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Semester</label>
                        <select name="target_semester_no" class="form-select" required>
                            @for($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}">Semester {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-copy me-2"></i>Clone Timetable
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    // Dynamic semester limit based on selected program
    document.querySelector('[name="program_id"]')?.addEventListener('change', function() {
        // Programs data from server
        const programData = @json($programs->pluck('total_semesters', 'id'));
        const selectedProgramId = this.value;
        const semesterSelect = document.getElementById('semesterSelect');
        const maxSemesters = programData[selectedProgramId] || 8;
        
        // Update semester options
        semesterSelect.innerHTML = '<option value="">Select Semester</option>';
        for (let i = 1; i <= maxSemesters; i++) {
            semesterSelect.innerHTML += `<option value="${i}">Semester ${i}</option>`;
        }
    });
</script>
@endsection
