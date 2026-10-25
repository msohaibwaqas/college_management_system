@extends('layouts.app')

@section('body-class', 'print-hidden-body')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <a href="{{ route('reports.students-without-teacher.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Form
            </a>
        </div>
        <div class="col-auto">
            <form action="{{ route('reports.students-without-teacher.pdf') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="admission_year" value="{{ $admissionYear }}">
                <input type="hidden" name="subject_id" value="{{ $subject ? $subject->id : 'all' }}">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf me-2"></i>Export as PDF
                </button>
            </form>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>
    </div>

    <div id="printable-area" class="card shadow-sm print-visible-area">
        <div class="card-body p-4">
            <!-- Header -->
            <div class="text-center mb-4 pb-3 border-bottom">
                <h3 class="fw-bold">{{ config('app.name', 'Government College') }}</h3>
                <h5 class="text-muted">Students Without Teacher Report</h5>
            </div>

            <!-- Report Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Admission Year:</th>
                            <td><strong>{{ $admissionYear }}</strong></td>
                        </tr>
                        <tr>
                            <th>Filter Subject:</th>
                            <td><strong>{{ $subject ? $subject->name : 'All Subjects' }}</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Total Unassigned Students:</th>
                            <td><strong class="text-danger fs-5">{{ $reportData->count() }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Report List Section -->
            <h6 class="fw-bold mb-3 pb-2 border-bottom">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Students With Subjects Missing Teacher Assignment (Total: {{ $reportData->count() }})
            </h6>
            
            @if($reportData->count() > 0)
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    The following students are enrolled in subjects that currently have no teacher assigned to them.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm align-middle print-table">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th class="text-center" style="width: 110px;">Roll No.</th>
                                <th>Student Name</th>
                                <th>Father Name</th>
                                <th class="text-center" style="width: 70px;">Class</th>
                                <th>Unassigned Subjects (No Teacher)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="text-center font-monospace fw-bold fs-6 text-danger">{{ $row['college_roll_no'] }}</td>
                                    <td class="fw-semibold">{{ $row['student_name'] }}</td>
                                    <td>{{ $row['father_name'] }}</td>
                                    <td class="text-center">{{ $row['current_class'] }}</td>
                                    <td>
                                        @foreach($row['unassigned_subjects'] as $subName)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 me-1">
                                                {{ $subName }}
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Great! All students enrolled in subjects for {{ $admissionYear }} have been assigned to teachers.
                </div>
            @endif

            <!-- Footer -->
            <div class="mt-4 pt-3 border-top">
                <small class="text-muted">
                    Generated on: {{ now()->format('F d, Y h:i A') }}
                </small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 12mm;
    }
    body * {
        visibility: hidden;
    }
    .print-visible-area, .print-visible-area * {
        visibility: visible;
    }
    .print-visible-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
    }
    .table {
        border-collapse: collapse !important;
        width: 100% !important;
    }
    .table th, .table td {
        border: 1px solid #333 !important;
        padding: 4px 6px !important;
        font-size: 11px !important;
        color: #000 !important;
    }
    .badge {
        border: none !important;
        background: transparent !important;
        color: #000 !important;
        padding: 0 !important;
        font-weight: bold !important;
    }
    .badge + .badge::before {
        content: ", ";
    }
    tr {
        page-break-inside: avoid;
    }
}
</style>
@endsection
