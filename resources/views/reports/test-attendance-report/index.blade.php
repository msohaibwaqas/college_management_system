@extends('layouts.app')
@section('title', 'Test Attendance Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice me-2"></i>Test Attendance Report</h2>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Report</h5>
        </div>
        <div class="card-body">
            <form action="{{ url('/reports/test-attendance-report') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="admission_year" class="form-label">Admission Year <span class="text-danger">*</span></label>
                    <select name="admission_year" id="admission_year" class="form-select" required>
                        <option value="">Select Year</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedYear)
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary">Attendance Summary for Year: {{ $selectedYear }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="12%">Admission Year</th>
                                <th width="15%">Date</th>
                                <th>Subject</th>
                                <th width="10%">Present</th>
                                <th width="10%">Absent</th>
                                <th width="10%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $row)
                                <tr>
                                    <td class="text-center">{{ $selectedYear }}</td>
                                    <td class="text-center">
                                        {{ \Carbon\Carbon::parse($row->test_date)->format('d-m-Y') }}
                                        <div class="small text-muted text-uppercase" style="font-size: 0.7rem;">
                                            {{ \Carbon\Carbon::parse($row->test_date)->format('l') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $row->subject->name }}</div>
                                        <div class="small text-muted">
                                            {{ str_replace('_', ' ', strtoupper($row->exam_type)) }} - {{ $row->exam_name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success fs-6">{{ $row->present_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $row->absent_count > 0 ? 'bg-danger' : 'bg-secondary' }} fs-6">
                                            {{ $row->absent_count }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark fs-6">{{ $row->total_students }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle mb-2" style="font-size: 2rem;"></i>
                                            <p class="mb-0">No attendance records found for the selected year.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info shadow-sm">
            <i class="fas fa-arrow-up me-2"></i>Please select an admission year to view the attendance report.
        </div>
    @endif
</div>
@endsection
