@extends('layouts.app')

@section('title', 'Monthly Staff Attendance Report')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-calendar-check me-2"></i>Staff Attendance Matrix</h1>
            <p class="page-subtitle">Generate a comprehensive daily view of staff attendance for a specific month.</p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('reports.staff-attendance.generate') }}" method="POST" class="row gx-3 gy-2 align-items-end">
                @csrf
                <div class="col-sm-4">
                    <label class="form-label fw-bold" for="monthFilter">Select Month</label>
                    <input type="month" class="form-control" id="monthFilter" name="month" value="{{ $monthValue ?? date('Y-m') }}" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-invoice me-1"></i> Generate Report
                    </button>
                    @if(isset($reportGrid))
                        <button type="button" class="btn btn-outline-secondary ms-2" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print / Save PDF
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if(isset($reportGrid))
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Attendance Matrix: <span class="text-primary">{{ $month }}</span></h5>
            <div class="small">
                <span class="badge bg-success me-1">P = Present</span>
                <span class="badge bg-danger me-1">A = Absent</span>
                <span class="badge bg-warning text-dark me-1">L = Late</span>
                <span class="badge bg-secondary me-1">HD = Half Day</span>
                <span class="badge" style="background-color: #e83e8c;">LV = Leave</span>
                <span class="badge bg-info text-dark">H = Holiday</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="sticky-top bg-light text-nowrap" style="min-width: 250px;">Staff Member</th>
                            @for($i = 1; $i <= $daysInMonth; $i++)
                                <th class="text-center sticky-top bg-light" style="min-width: 30px;">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</th>
                            @endfor
                            <th class="text-center bg-light fw-bold" title="Present" style="min-width: 40px; border-left: 2px solid #ccc;">P</th>
                            <th class="text-center bg-light fw-bold text-danger" title="Absent" style="min-width: 40px;">A</th>
                            <th class="text-center bg-light fw-bold text-warning" title="Late" style="min-width: 40px;">L</th>
                            <th class="text-center bg-light fw-bold" style="color: #e83e8c;" title="Leave" style="min-width: 40px;">LV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportGrid as $row)
                        <tr>
                            <td class="text-nowrap fw-bold bg-white">
                                {{ $row['staff']->first_name }} {{ $row['staff']->last_name }} 
                                <span class="text-muted small ps-1">({{ $row['staff']->designation ?? 'Staff' }})</span>
                            </td>
                            @for($i = 1; $i <= $daysInMonth; $i++)
                                @php 
                                    $code = $row['days'][$i]; 
                                    $cellClass = '';
                                    if($code == 'P') $cellClass = 'text-success fw-bold';
                                    elseif($code == 'A') $cellClass = 'bg-danger bg-opacity-10 text-danger fw-bold';
                                    elseif($code == 'L') $cellClass = 'text-warning fw-bold';
                                    elseif($code == 'HD') $cellClass = 'text-secondary fw-bold';
                                    elseif($code == 'LV') $cellClass = 'bg-pink bg-opacity-10 fw-bold';
                                    elseif($code == 'H') $cellClass = 'bg-info bg-opacity-10 text-dark';
                                    elseif($code == 'W') $cellClass = 'bg-light text-muted small';
                                @endphp
                                <td class="text-center {{ $cellClass }} border-end-0 border-start-0" style="color: {{ $code == 'LV' ? '#e83e8c' : '' }};">
                                    {{ $code }}
                                </td>
                            @endfor
                            <td class="text-center fw-bold bg-light" style="border-left: 2px solid #ccc;">{{ $row['summary']['present'] }}</td>
                            <td class="text-center text-danger fw-bold bg-light">{{ $row['summary']['absent'] }}</td>
                            <td class="text-center text-warning fw-bold bg-light">{{ $row['summary']['late'] }}</td>
                            <td class="text-center fw-bold bg-light" style="color: #e83e8c;">{{ $row['summary']['leave'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $daysInMonth + 5 }}" class="text-center py-4 text-muted">No staff found for the active roster.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 pt-0 py-3 d-none d-print-block">
            <div class="row text-center mt-5 mb-3">
                <div class="col-4 border-top border-dark pt-2 mx-auto" style="max-width: 250px;">HR Approvals</div>
                <div class="col-4 border-top border-dark pt-2 mx-auto" style="max-width: 250px;">Principal Signature</div>
            </div>
            <p class="small text-center text-muted mb-0">Generated automatically by the Management System on {{ now()->format('d M, Y H:i') }}</p>
        </div>
    </div>
    
    <style>
        @media print {
            .sidebar, .page-header, .navbar, .btn, form { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
            .table { zoom: 90%; }
        }
    </style>
    @endif
</div>
@endsection
