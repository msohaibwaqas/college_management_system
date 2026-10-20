@extends('layouts.app')
@section('title', 'Struck-Off Students — Admin Panel')

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-slash text-danger me-2"></i>Struck-Off Students
            </h1>
            <p class="text-muted small mb-0 mt-1">
                Manage struck-off students. Readmit eligible students (max <strong>{{ $settings->max_readmissions_per_year }}</strong> times per academic year, threshold: <strong>&ge; {{ $settings->absent_threshold }} absents</strong>).
            </p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="fas fa-cog me-1"></i> Policy Rules &amp; Limits
            </button>
            <a href="{{ route('struck-off.teacher') }}" class="btn btn-danger btn-sm">
                <i class="fas fa-user-slash me-1"></i> Strike Off Eligible Students
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Policy Rules Modal --}}
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-primary">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="settingsModalLabel">
                        <i class="fas fa-sliders-h me-2"></i>Struck-Off Policy Rules &amp; Limits
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('struck-off.settings.update') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">
                                Absent Threshold to Strike Off (Absences)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-times text-danger"></i></span>
                                <input type="number" name="absent_threshold" class="form-control"
                                       value="{{ $settings->absent_threshold }}" min="1" max="100" required>
                                <span class="input-group-text">Absents</span>
                            </div>
                            <small class="text-muted">A student with this many or more absents becomes eligible for strike-off.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">
                                Max Readmissions Allowed Per Academic Year
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-redo text-success"></i></span>
                                <input type="number" name="max_readmissions_per_year" class="form-control"
                                       value="{{ $settings->max_readmissions_per_year }}" min="1" max="10" required>
                                <span class="input-group-text">Times</span>
                            </div>
                            <small class="text-muted">Maximum times a student can be readmitted in a single academic year.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">
                                Default Calculation Period
                            </label>
                            <select name="calculation_period" class="form-select" required>
                                <option value="monthly" {{ $settings->calculation_period === 'monthly' ? 'selected' : '' }}>
                                    Monthly (e.g. 10 absents in a single month)
                                </option>
                                <option value="session" {{ $settings->calculation_period === 'session' ? 'selected' : '' }}>
                                    Entire Session (Cumulative across the academic year)
                                </option>
                            </select>
                            <small class="text-muted">Teachers can also toggle between specific months and all-time on the strike-off panel.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Policy Rules
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small fw-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="struck_off" {{ $statusFilter === 'struck_off' ? 'selected' : '' }}>Currently Struck Off</option>
                        <option value="readmitted" {{ $statusFilter === 'readmitted' ? 'selected' : '' }}>Readmitted</option>
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Records</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold text-muted mb-1">Academic Year</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <a href="{{ route('struck-off.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
                <div class="col-auto ms-auto text-muted small">
                    <span class="badge bg-light text-dark border">
                        Current Policy: Threshold &ge; {{ $settings->absent_threshold }} absents | Max {{ $settings->max_readmissions_per_year }} readmissions/year
                    </span>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-danger">
                <i class="fas fa-list me-2"></i>
                @if($statusFilter === 'struck_off') Currently Struck-Off
                @elseif($statusFilter === 'readmitted') Readmitted Students
                @else All Struck-Off Records
                @endif
                <span class="badge bg-danger ms-2">{{ $logs->total() }}</span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p class="mb-0">No struck-off records found matching the criteria.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Roll No</th>
                                <th>Class &amp; Year</th>
                                <th>Subject</th>
                                <th>Absents at Strike</th>
                                <th>Struck Off By</th>
                                <th>Struck Off At</th>
                                <th>Status</th>
                                <th>Readmission Info</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $i => $log)
                            <tr>
                                <td class="text-muted small">{{ $logs->firstItem() + $i }}</td>
                                <td>
                                    <div class="fw-bold">{{ $log->student?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $log->student?->father_name ?? '' }}</small>
                                </td>
                                <td><code>{{ $log->admission?->college_roll_no ?? '—' }}</code></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $log->admission?->current_class }}th ({{ $log->academic_year }})
                                    </span>
                                </td>
                                <td>{{ $log->subject?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ $log->absence_count_at_strike }} Absents
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $log->struckOffBy?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $log->struckOffBy?->designation ?? '' }}</small>
                                </td>
                                <td class="small text-muted">
                                    {{ $log->struck_off_at?->format('d M Y, h:i A') }}
                                    @if($log->struck_off_reason)
                                        <div class="text-truncate" style="max-width:160px;" title="{{ $log->struck_off_reason }}">
                                            <em>{{ $log->struck_off_reason }}</em>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status === 'struck_off')
                                        <span class="badge bg-danger">Struck Off</span>
                                    @else
                                        <span class="badge bg-success">Readmitted</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status === 'readmitted')
                                        <div class="fw-semibold">{{ $log->readmittedBy?->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $log->readmitted_at?->format('d M Y') }}</small>
                                        @if($log->readmission_note)
                                            <div class="text-muted small fst-italic">{{ $log->readmission_note }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('struck-off.history', $log->student_id) }}"
                                       class="btn btn-outline-info btn-sm me-1" title="Full History">
                                        <i class="fas fa-history"></i>
                                    </a>

                                    @if($log->status === 'struck_off')
                                        {{-- Readmit Button --}}
                                        @php
                                            $readmissionsThisYear = \App\Models\StudentStruckOffLog::where('student_id', $log->student_id)
                                                ->where('academic_year', $log->academic_year)
                                                ->where('status', 'readmitted')
                                                ->count();
                                            $maxReadmit = $settings->max_readmissions_per_year;
                                        @endphp

                                        @if($readmissionsThisYear >= $maxReadmit)
                                            <span class="badge bg-secondary" title="Max readmissions reached for this year">
                                                <i class="fas fa-lock me-1"></i>Limit Reached ({{ $readmissionsThisYear }}/{{ $maxReadmit }})
                                            </span>
                                        @else
                                            <button type="button"
                                                    class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#readmitModal{{ $log->id }}">
                                                <i class="fas fa-user-check me-1"></i> Readmit
                                                <span class="badge bg-white text-success ms-1">{{ $readmissionsThisYear }}/{{ $maxReadmit }}</span>
                                            </button>

                                            <div class="modal fade" id="readmitModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-success">
                                                        <div class="modal-header bg-success text-white">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-user-check me-2"></i>Confirm Readmission
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="alert alert-info border-0">
                                                                Readmitting <strong>{{ $log->student?->full_name }}</strong>.
                                                                They have been readmitted
                                                                <strong>{{ $readmissionsThisYear }}</strong> time(s) this year.
                                                                Maximum allowed by policy: <strong>{{ $maxReadmit }}</strong>.
                                                            </div>
                                                            <form id="readmitForm{{ $log->id }}"
                                                                  action="{{ route('struck-off.readmit', $log->id) }}"
                                                                  method="POST">
                                                                @csrf
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small">
                                                                        Readmission Note <span class="text-muted">(optional)</span>
                                                                    </label>
                                                                    <textarea name="readmission_note" class="form-control" rows="3"
                                                                              placeholder="e.g. Paid readmission fee, submitted apology..."></textarea>
                                                                </div>
                                                            </form>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" form="readmitForm{{ $log->id }}" class="btn btn-success">
                                                                <i class="fas fa-user-check me-1"></i> Confirm Readmission
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
