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
                Students exceeding the absence threshold (default <strong>&ge; {{ $settings->absent_threshold }} absents</strong>) in a specific subject. You may strike off any eligible student.
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(in_array(Auth::user()->roles->pluck('name')->first() ?? '', ['admin', 'principal', 'clerk', 'super_admin']) || Auth::user()->is_super_admin)
                <a href="{{ route('struck-off.index') }}" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sliders-h me-1"></i> Struck-Off Mgmt &amp; Rules
                </a>
            @endif
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Attendance
            </a>
        </div>
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

    {{-- Filter Form --}}
    <div class="card shadow-sm mb-4 border-start-warning">
        <div class="card-header bg-warning bg-opacity-10 border-warning py-3">
            <h6 class="m-0 fw-bold text-dark">
                <i class="fas fa-filter text-warning me-2"></i>Filter Class, Subject &amp; Absence Threshold
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('struck-off.teacher') }}" id="filterForm" class="row g-3 align-items-end">
                {{-- 1. Admission Year / Batch --}}
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">1. Admission Year (Batch)</label>
                    <select name="year" id="yearSelect" class="form-select" required onchange="handleYearChange(this.value)">
                        <option value="">— Select Batch Year —</option>
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ ($selectedYear == $yr) ? 'selected' : '' }}>
                                Batch {{ $yr }} ({{ $yr == 2026 ? '1st Year / 11th' : ($yr == 2025 ? '2nd Year / 12th' : 'Class of ' . $yr) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Class & Subject --}}
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">2. Subject &amp; Class</label>
                    <select name="class_subject" id="classSubjectSelect" class="form-select" required onchange="handleClassSubjectChange(this)">
                        <option value="">— Select Class &amp; Subject —</option>
                        @foreach($mySubjects as $sub)
                            @php
                                $valKey = $sub->subject_id . ':' . $sub->current_class . ':' . $sub->admission_year;
                                $isSelected = ($selectedSubjectId == $sub->subject_id && $selectedYear == $sub->admission_year && (!$selectedClass || $selectedClass == $sub->current_class));
                            @endphp
                            <option value="{{ $valKey }}"
                                    data-year="{{ $sub->admission_year }}"
                                    data-subject="{{ $sub->subject_id }}"
                                    data-class="{{ $sub->current_class }}"
                                    {{ $isSelected ? 'selected' : '' }}>
                                Class {{ $sub->current_class }}th ({{ $sub->admission_year }}) — {{ $sub->subject_name }} ({{ $sub->student_count }} students)
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="subject_id" id="hiddenSubjectId" value="{{ $selectedSubjectId }}">
                    <input type="hidden" name="class" id="hiddenClass" value="{{ $selectedClass }}">
                </div>

                {{-- 3. Attendance Period / Month --}}
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">3. Attendance Period</label>
                    <select name="month" class="form-select">
                        <option value="all" {{ ($selectedMonth === 'all') ? 'selected' : '' }}>Entire Academic Session</option>
                        @foreach($availableMonths as $m)
                            @php
                                try {
                                    $mLabel = \Carbon\Carbon::createFromFormat('Y-m', $m)->format('F Y');
                                } catch (\Exception $e) {
                                    $mLabel = $m;
                                }
                            @endphp
                            <option value="{{ $m }}" {{ ($selectedMonth === $m) ? 'selected' : '' }}>
                                In {{ $mLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. Custom Absent Threshold --}}
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">Min Absents</label>
                    <div class="input-group">
                        <input type="number" name="threshold" class="form-control"
                               value="{{ $selectedThreshold }}" min="1" max="100" required title="Minimum absents threshold">
                        <button type="submit" class="btn btn-primary" title="Search Eligible Students">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Filter Summary & Eligible Students Table --}}
    @if($selectedSubjectId && $selectedYear)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="m-0 fw-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Students Eligible for Struck-Off
                    <span class="badge bg-danger ms-2">{{ $eligibleStudents->count() }} Found</span>
                </h6>
                <div class="text-muted small mt-1">
                    Showing students with <strong>&ge; {{ $selectedThreshold }} absents</strong> in
                    @php
                        $activeSubject = $mySubjects->firstWhere('subject_id', $selectedSubjectId);
                    @endphp
                    <strong>{{ $activeSubject->subject_name ?? 'Subject #' . $selectedSubjectId }}</strong>
                    (Class {{ $selectedClass ?? ($activeSubject->current_class ?? '') }}th, Batch {{ $selectedYear }})
                    @if($selectedMonth && $selectedMonth !== 'all')
                        for <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('F Y') }}</strong>
                    @else
                        (Entire Session)
                    @endif
                </div>
            </div>
            <div>
                <span class="badge bg-light text-dark border p-2">
                    <i class="fas fa-info-circle text-primary me-1"></i> Policy Limit: Max {{ $settings->max_readmissions_per_year }} readmissions/year
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($eligibleStudents->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold text-dark">No Students Exceed Threshold</h5>
                    <p class="mb-0 text-muted">
                        No students in Batch {{ $selectedYear }} exceeded {{ $selectedThreshold }} absents in this subject
                        @if($selectedMonth && $selectedMonth !== 'all')
                            for {{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('F Y') }}.
                        @else
                            for this session.
                        @endif
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Father Name</th>
                                <th>College Roll No</th>
                                <th>Class &amp; Batch</th>
                                <th class="text-center">Absences</th>
                                <th class="text-center">Status / Action</th>
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
                                            <i class="fas fa-ban me-1"></i> Currently Struck Off
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $s->father_name ?? '—' }}</td>
                                <td><code>{{ $s->roll_number }}</code></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        Class {{ $s->current_class }}th (Batch {{ $s->admission_year }})
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                        {{ $s->absence_count }} Absents
                                    </span>
                                    @if($selectedMonth && $selectedMonth !== 'all')
                                        <div class="small text-muted mt-1">{{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('M Y') }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($s->already_struck_off)
                                        <span class="text-muted small">
                                            <i class="fas fa-lock me-1"></i>Pending Readmission
                                        </span>
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
                                                <div class="modal-content border-danger text-start">
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
                                                            struck off the college rolls due to <strong>{{ $s->absence_count }} absents</strong>.
                                                            They will be excluded from attendance lists until readmitted by the Admin / Principal.
                                                        </div>
                                                        <form id="strikeForm{{ $s->student_id }}"
                                                              action="{{ route('struck-off.store') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="student_id" value="{{ $s->student_id }}">
                                                            <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">
                                                            <input type="hidden" name="threshold" value="{{ $selectedThreshold }}">
                                                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small">Reason for Strike-Off <span class="text-muted">(optional)</span></label>
                                                                <textarea name="reason" class="form-control" rows="3"
                                                                          placeholder="e.g. Exceeded {{ $selectedThreshold }} absents threshold..."></textarea>
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

<script>
function handleYearChange(newYear) {
    var classSubSelect = document.getElementById('classSubjectSelect');
    var options = classSubSelect.options;
    var foundFirst = false;

    for (var i = 1; i < options.length; i++) {
        var optYear = options[i].getAttribute('data-year');
        if (!newYear || optYear === newYear) {
            options[i].style.display = '';
            if (!foundFirst) {
                options[i].selected = true;
                foundFirst = true;
            }
        } else {
            options[i].style.display = 'none';
        }
    }

    if (foundFirst) {
        handleClassSubjectChange(classSubSelect);
    } else {
        document.getElementById('hiddenSubjectId').value = '';
        document.getElementById('hiddenClass').value = '';
        document.getElementById('filterForm').submit();
    }
}

function handleClassSubjectChange(selectElem) {
    var selectedOpt = selectElem.options[selectElem.selectedIndex];
    if (selectedOpt && selectedOpt.value) {
        var parts = selectedOpt.value.split(':');
        document.getElementById('hiddenSubjectId').value = parts[0] || '';
        document.getElementById('hiddenClass').value = parts[1] || '';
        if (parts[2]) {
            document.getElementById('yearSelect').value = parts[2];
        }
        document.getElementById('filterForm').submit();
    }
}
</script>
@endsection
