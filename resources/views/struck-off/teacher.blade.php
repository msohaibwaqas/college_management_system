@extends('layouts.app')
@section('title', 'Struck-Off — Eligible Students')

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-slash text-danger me-2"></i>Struck-Off — Eligible Students
            </h1>
            <p class="text-muted small mb-0 mt-1">
                Students with <strong>&gt; 10 absents</strong> in a specific subject you teach. You may strike off any eligible student.
            </p>
        </div>
        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Attendance
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Subject / Year Filter --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning bg-opacity-10 border-warning">
            <h6 class="m-0 fw-bold text-warning-emphasis">
                <i class="fas fa-filter me-2"></i>Select Subject to Check Absences
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('struck-off.teacher') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-muted">Subject / Class / Year</label>
                    <select name="subject_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">— Select a subject —</option>
                        @foreach($mySubjects as $sub)
                            <option value="{{ $sub->subject_id }}"
                                {{ ($selectedSubjectId == $sub->subject_id) ? 'selected' : '' }}>
                                {{ $sub->subject_name }}
                                (Class {{ $sub->current_class }}, {{ $sub->admission_year }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Admission Year</label>
                    <select name="year" class="form-select" required onchange="this.form.submit()">
                        <option value="">— Select year —</option>
                        @foreach($mySubjects->pluck('admission_year')->unique()->sortDesc() as $yr)
                            <option value="{{ $yr }}" {{ ($selectedYear == $yr) ? 'selected' : '' }}>
                                {{ $yr }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Eligible Students Table --}}
    @if($selectedSubjectId && $selectedYear)
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Students Eligible for Struck-Off
                <span class="badge bg-danger ms-2">{{ $eligibleStudents->count() }}</span>
            </h6>
            <small class="text-muted">Showing students with &gt; 10 absents in selected subject</small>
        </div>
        <div class="card-body p-0">
            @if($eligibleStudents->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="mb-0">No students exceed 10 absents in this subject. No action needed.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Father Name</th>
                                <th>Roll No</th>
                                <th>Class</th>
                                <th class="text-center">Absences in Subject</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eligibleStudents as $i => $s)
                            <tr class="{{ $s->already_struck_off ? 'table-secondary opacity-75' : 'table-danger bg-opacity-10' }}">
                                <td class="text-muted small">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-bold">{{ trim($s->first_name . ' ' . $s->last_name) }}</div>
                                    @if($s->already_struck_off)
                                        <span class="badge bg-dark bg-opacity-75 mt-1">
                                            <i class="fas fa-ban me-1"></i> Already Struck Off
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $s->father_name ?? '—' }}</td>
                                <td><code>{{ $s->roll_number }}</code></td>
                                <td>{{ $s->current_class }}th</td>
                                <td class="text-center">
                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                        {{ $s->absence_count }} Absents
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($s->already_struck_off)
                                        <span class="text-muted small"><i class="fas fa-lock me-1"></i>Pending Readmission</span>
                                    @else
                                        {{-- Strike-Off Button + Modal Trigger --}}
                                        <button type="button"
                                                class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#strikeModal{{ $s->student_id }}">
                                            <i class="fas fa-user-slash me-1"></i> Strike Off
                                        </button>

                                        {{-- Confirmation Modal --}}
                                        <div class="modal fade" id="strikeModal{{ $s->student_id }}" tabindex="-1" aria-labelledby="strikeModalLabel{{ $s->student_id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-danger">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="strikeModalLabel{{ $s->student_id }}">
                                                            <i class="fas fa-user-slash me-2"></i>Confirm Strike-Off
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning border-0">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <strong>{{ trim($s->first_name . ' ' . $s->last_name) }}</strong> will be
                                                            struck off and will disappear from <strong>all</strong> your attendance lists.
                                                            Only an Admin or Clerk can readmit them.
                                                        </div>
                                                        <form id="strikeForm{{ $s->student_id }}"
                                                              action="{{ route('struck-off.store') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="student_id" value="{{ $s->student_id }}">
                                                            <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small">Reason <span class="text-muted">(optional)</span></label>
                                                                <textarea name="reason" class="form-control" rows="3"
                                                                          placeholder="Reason for striking off (e.g. persistent absenteeism)..."></textarea>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" form="strikeForm{{ $s->student_id }}" class="btn btn-danger">
                                                            <i class="fas fa-user-slash me-1"></i> Confirm Strike-Off
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
