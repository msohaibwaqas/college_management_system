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
                Manage struck-off students. Readmit where eligible (max 2 times per academic year).
            </p>
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
                @else All Records @endif
                <span class="badge bg-secondary ms-2">{{ $logs->total() }}</span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="mb-0">No records found for this filter.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Roll No</th>
                                <th>Struck Off Subject</th>
                                <th>By Teacher</th>
                                <th>Absents at Strike</th>
                                <th>Struck Off Date</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Readmitted By</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $log->student?->full_name ?? '—' }}</div>
                                    <small class="text-muted">Year: {{ $log->academic_year }}</small>
                                </td>
                                <td><code>{{ $log->admission?->college_roll_no ?? '—' }}</code></td>
                                <td>{{ $log->subject?->name ?? '—' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $log->struckOffBy?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $log->struckOffBy?->designation ?? '' }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger">{{ $log->absence_count_at_strike }}</span>
                                </td>
                                <td>
                                    <div>{{ $log->struck_off_at?->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $log->struck_off_at?->format('h:i A') }}</small>
                                </td>
                                <td class="text-muted small" style="max-width: 150px;">
                                    {{ $log->struck_off_reason ?? '—' }}
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
                                        @endphp

                                        @if($readmissionsThisYear >= 2)
                                            <span class="badge bg-secondary" title="Max readmissions reached for this year">
                                                <i class="fas fa-lock me-1"></i>Limit Reached ({{ $readmissionsThisYear }}/2)
                                            </span>
                                        @else
                                            <button type="button"
                                                    class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#readmitModal{{ $log->id }}">
                                                <i class="fas fa-user-check me-1"></i> Readmit
                                                <span class="badge bg-white text-success ms-1">{{ $readmissionsThisYear }}/2</span>
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
                                                                Maximum allowed: <strong>2</strong>.
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
                                                                              placeholder="Reason for readmission..."></textarea>
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
