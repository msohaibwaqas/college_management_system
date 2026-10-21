@extends('layouts.app')
@section('title', 'Struck-Off Students — Admin Panel')

@section('content')
<div class="container-fluid">
    @php
        $authUser = Auth::user();
        $canManagePolicy = $authUser && $authUser->isAdministrativeStaff();
    @endphp

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-slash text-danger me-2"></i>Struck-Off Students
            </h1>
            <p class="text-muted small mb-0 mt-1">
                @if(!empty($isTeacher))
                    Viewing students struck off by you. You can readmit students on their 1st strike-off. 2nd &amp; subsequent strike-offs can only be readmitted by Admin / Clerk.
                @else
                    Manage struck-off students. 1st strike-off can be readmitted by striking teacher or Admin/Clerk; 2nd &amp; subsequent strike-offs can only be readmitted by Admin / Clerk (max <strong>{{ $settings->max_readmissions_per_year }}</strong> readmissions/yr).
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            @if($canManagePolicy && ($activeStruckOffCount ?? 0) > 0)
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkReadmitModal">
                    <i class="fas fa-user-check me-1"></i> Bulk Readmit All ({{ $activeStruckOffCount }})
                </button>
            @endif
            @if($canManagePolicy)
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#settingsModal">
                    <i class="fas fa-cog me-1"></i> Policy Rules &amp; Limits
                </button>
            @endif
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

    {{-- Bulk Readmit Modal --}}
    @if($canManagePolicy && ($activeStruckOffCount ?? 0) > 0)
    <div class="modal fade" id="bulkReadmitModal" tabindex="-1" aria-labelledby="bulkReadmitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="bulkReadmitModalLabel">
                        <i class="fas fa-user-check me-2"></i>Bulk Readmit All Struck-Off Students
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('struck-off.bulk-readmit') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info border-0">
                            <i class="fas fa-info-circle me-1"></i> This will readmit all <strong>{{ $activeStruckOffCount }}</strong> currently struck-off students who are eligible under the annual limit (&le; {{ $settings->max_readmissions_per_year }} readmissions/yr).
                            <div class="mt-2 text-dark small fw-bold">
                                Their admission status will be reactivated, and their previous absences will be protected from causing subsequent strike-offs.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">
                                Readmission Note <span class="text-muted">(optional)</span>
                            </label>
                            <textarea name="readmission_note" class="form-control" rows="3"
                                      placeholder="e.g. Bulk readmitted as per policy reset..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-double me-1"></i> Confirm Bulk Readmission
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                                Monthly Absent Threshold to Strike Off
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-times text-danger"></i></span>
                                <input type="number" name="absent_threshold" class="form-control"
                                       value="{{ $settings->absent_threshold }}" min="1" max="100" required>
                                <span class="input-group-text">Absents / Month</span>
                            </div>
                            <small class="text-muted">A student with this many or more absents in a single month becomes eligible for strike-off.</small>
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
                                Calculation Period Policy
                            </label>
                            <select name="calculation_period" class="form-select" required>
                                <option value="monthly" {{ $settings->calculation_period === 'monthly' ? 'selected' : '' }}>
                                    Monthly (Strict: 20 absents per calendar month)
                                </option>
                            </select>
                            <small class="text-muted">Policy strictly requires absence count to be evaluated on a per-calendar-month basis. Entire-year cumulative strikes are disabled.</small>
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

    {{-- Filters Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('struck-off.index') }}" id="filterForm" class="row g-2 align-items-end">
                {{-- Search Box --}}
                <div class="col-md-4 col-sm-6">
                    <label for="search" class="form-label small fw-bold text-muted mb-1">
                        <i class="fas fa-search me-1"></i>Search Student
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" id="search" class="form-control"
                               placeholder="Name, Roll No, Subject, Father, Teacher..."
                               value="{{ request('search') }}">
                        @if(request('search'))
                            <a href="{{ route('struck-off.index', request()->except('search', 'page')) }}"
                               class="btn btn-outline-secondary" title="Clear Search">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </div>
                </div>

                {{-- Status Filter --}}
                <div class="col-md-2 col-sm-3 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="struck_off" {{ $statusFilter === 'struck_off' ? 'selected' : '' }}>Currently Struck Off</option>
                        <option value="readmitted" {{ $statusFilter === 'readmitted' ? 'selected' : '' }}>Readmitted</option>
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Records</option>
                    </select>
                </div>

                {{-- Academic Year Filter --}}
                <div class="col-md-2 col-sm-3 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Academic Year</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Per Page Selector --}}
                <div class="col-md-2 col-sm-3 col-6">
                    <label class="form-label small fw-bold text-muted mb-1">Per Page</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="30" {{ request('per_page', 30) == 30 ? 'selected' : '' }}>30</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                {{-- Reset / Clear Button --}}
                <div class="col-md-2 col-sm-3 col-6 d-flex gap-2">
                    <a href="{{ route('struck-off.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted small">
            @if($logs->total() > 0)
                Showing <strong>{{ $logs->firstItem() }}</strong> to <strong>{{ $logs->lastItem() }}</strong> of <strong>{{ $logs->total() }}</strong> records
                @if(request('search'))
                    matching "<strong>{{ request('search') }}</strong>"
                @endif
            @else
                No records found
                @if(request('search'))
                    matching "<strong>{{ request('search') }}</strong>"
                @endif
            @endif
        </div>
        <div>
            <span class="badge bg-light text-dark border">
                Current Policy: Threshold &ge; {{ $settings->absent_threshold }} absents/mo | Max {{ $settings->max_readmissions_per_year }} readmissions/year
            </span>
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
                    <p class="mb-0">
                        @if(request('search'))
                            No struck-off records found matching "<strong>{{ request('search') }}</strong>".
                        @else
                            No struck-off records found matching the criteria.
                        @endif
                    </p>
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
                                        @if($log->isFirstStrikeOff())
                                            <span class="badge bg-info text-dark d-block mt-1" title="1st strike-off: striking teacher or admin/clerk can readmit">
                                                <i class="fas fa-tag me-1"></i>1st Strike
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark d-block mt-1" title="Strike #{{ $log->getStrikeSequenceNumber() }}: only admin and clerk can readmit">
                                                <i class="fas fa-shield-alt me-1"></i>Strike #{{ $log->getStrikeSequenceNumber() }} (Admin/Clerk)
                                            </span>
                                        @endif
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
                                        @php
                                            $maxReadmit = $settings->max_readmissions_per_year;
                                            $readmissionsThisYear = $log->getReadmissionsCountForYear();
                                            $strikeNumber = $log->getStrikeSequenceNumber();
                                            $canReadmit = $log->canUserReadmit($authUser, $maxReadmit);
                                        @endphp

                                        @if(!$log->canBeReadmitted($maxReadmit))
                                            <span class="badge bg-secondary" title="Max readmissions reached for this year">
                                                <i class="fas fa-lock me-1"></i>Limit Reached ({{ $readmissionsThisYear }}/{{ $maxReadmit }})
                                            </span>
                                        @elseif($canReadmit)
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
                                                                <div>Readmitting <strong>{{ $log->student?->full_name }}</strong>.</div>
                                                                <div class="mt-1 small">
                                                                    Strike sequence: <strong>Strike #{{ $strikeNumber }}</strong>
                                                                    ({{ $readmissionsThisYear }} previous readmission(s) this year; max allowed: {{ $maxReadmit }}).
                                                                </div>
                                                                @if($log->isFirstStrikeOff() && $authUser && $authUser->isTeacher())
                                                                    <div class="mt-1 small text-success fw-semibold">
                                                                        <i class="fas fa-check-circle me-1"></i> Authorized: You struck off this student (1st strike-off).
                                                                    </div>
                                                                @elseif($strikeNumber > 1)
                                                                    <div class="mt-1 small text-primary fw-semibold">
                                                                        <i class="fas fa-user-shield me-1"></i> Authorized: Administrative Staff / Clerk readmission for Strike #{{ $strikeNumber }}.
                                                                    </div>
                                                                @endif
                                                                <div class="mt-2 small text-dark">
                                                                    <strong>Note:</strong> All past absences will be preserved in history, but excluded from new strike evaluations so students are not struck off again on past absences.
                                                                </div>
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
                                        @else
                                            @if(!$log->isFirstStrikeOff())
                                                <span class="badge bg-warning text-dark" title="Strike #{{ $strikeNumber }}: 2nd+ strike-offs can only be readmitted by Admin or Clerk">
                                                    <i class="fas fa-shield-alt me-1"></i>Admin/Clerk Readmit Only
                                                </span>
                                            @else
                                                <span class="badge bg-secondary" title="Only striking teacher ({{ $log->struckOffBy?->full_name }}) or Admin/Clerk can readmit">
                                                    <i class="fas fa-lock me-1"></i>Struck by {{ $log->struckOffBy?->full_name ?? 'Other Teacher' }}
                                                </span>
                                            @endif
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') return;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                document.getElementById('filterForm').submit();
            }, 600);
        });
    }
});
</script>
@endsection
