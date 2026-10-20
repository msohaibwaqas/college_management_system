@extends('layouts.app')
@section('title', 'Struck-Off History — ' . $student->full_name)

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-history text-primary me-2"></i>Struck-Off History
            </h1>
            <p class="text-muted small mb-0 mt-1">
                Complete record for: <strong>{{ $student->full_name }}</strong>
            </p>
        </div>
        <a href="{{ route('struck-off.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="display-5 fw-bold text-danger">{{ $totalStruckOff }}</div>
                    <div class="text-muted small mt-1">Total Times Struck Off</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="display-5 fw-bold text-warning">{{ $struckOffThisYear }}</div>
                    <div class="text-muted small mt-1">Times Struck Off This Year</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <div class="display-5 fw-bold text-success">{{ $readmissionsThisYear }}</div>
                    <div class="text-muted small mt-1">Readmissions This Year <span class="text-muted fs-6">(max {{ $settings->max_readmissions_per_year ?? 2 }})</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- History Timeline --}}
    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="m-0 fw-bold">Complete History</h6>
        </div>
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p>This student has never been struck off.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Struck Off By</th>
                                <th>Absents at Strike</th>
                                <th>Date & Reason</th>
                                <th>Status</th>
                                <th>Readmission Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $i => $log)
                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>
                                <td>{{ $log->subject?->name ?? '—' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $log->struckOffBy?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $log->struckOffBy?->designation ?? '' }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger">{{ $log->absence_count_at_strike }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $log->struck_off_at?->format('d M Y, h:i A') }}</div>
                                    @if($log->struck_off_reason)
                                        <small class="text-muted fst-italic">{{ $log->struck_off_reason }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status === 'struck_off')
                                        <span class="badge bg-danger">Currently Struck Off</span>
                                    @else
                                        <span class="badge bg-success">Readmitted</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status === 'readmitted')
                                        <div class="fw-semibold">{{ $log->readmittedBy?->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $log->readmitted_at?->format('d M Y, h:i A') }}</small>
                                        @if($log->readmission_note)
                                            <div class="text-muted small fst-italic">{{ $log->readmission_note }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
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

</div>
@endsection
