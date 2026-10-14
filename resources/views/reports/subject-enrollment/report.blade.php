@extends('layouts.app')

@section('body-class', 'print-hidden-body')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <a href="{{ route('reports.subject-enrollment.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Form
            </a>
        </div>
        <div class="col-auto">
            <form action="{{ route('reports.subject-enrollment.pdf') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="admission_year" value="{{ $admissionYear }}">
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>Export as PDF
                </button>
            </form>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>
    </div>

    <div id="printable-area" class="card shadow-sm print-visible-area">
        <div class="card-body p-4">
            <!-- Header -->
            <div class="text-center mb-4 pb-3 border-bottom">
                <h3 class="fw-bold">{{ config('app.name', 'Government College') }}</h3>
                <h5 class="text-muted">Subject Enrollment Report</h5>
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
                            <th>Subject Name:</th>
                            <td><strong>{{ $subject->name }}</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Subject Code:</th>
                            <td><strong>{{ $subject->code }}</strong></td>
                        </tr>
                        <tr>
                            <th>Subject Type:</th>
                            <td><span class="badge {{ $subject->type == 'Compulsory' ? 'bg-info' : 'bg-warning' }}">{{ $subject->type }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            @if(isset($subject->id) && $subject->id === 'all_electives')
                <!-- All Students Elective Subjects Section -->
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    Student Elective Subjects List (Total Students: {{ $students->count() }})
                </h6>

                @if($students->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm align-middle print-table">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th class="text-center" style="width: 110px;">Roll No.</th>
                                    <th>Student Name</th>
                                    <th>Father Name</th>
                                    <th class="text-center" style="width: 70px;">Class</th>
                                    <th>Elective Subjects</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    @php
                                        $admission = $student->admissions->first();
                                        $electivesList = $student->electives->pluck('subject.name')->filter()->implode(', ');
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center font-monospace fw-bold">{{ $admission ? $admission->college_roll_no : 'N/A' }}</td>
                                        <td class="fw-semibold">{{ $student->first_name }} {{ $student->last_name }}</td>
                                        <td>{{ optional($student->father)->name ?? 'N/A' }}</td>
                                        <td class="text-center">{{ $admission ? $admission->current_class : '-' }}</td>
                                        <td>
                                            @if($student->electives->count() > 0)
                                                @foreach($student->electives as $e)
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1">
                                                        {{ optional($e->subject)->name ?? 'Unknown' }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted fst-italic">No electives assigned</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No students found for the selected admission year.
                    </div>
                @endif
            @else
                <!-- Roll Numbers Grid Section -->
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    Student Roll Numbers (Total: {{ $students->count() }})
                </h6>
                
                @if($students->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-top">
                            <tbody>
                                @foreach($students->chunk(10) as $chunk)
                                <tr>
                                    @foreach($chunk as $student)
                                    <td class="text-center p-2" style="width: 10%; vertical-align: top; height: 50px;">
                                        <div class="fw-bold fs-6 mb-1 text-primary">{{ $student->college_roll_no }}</div>
                                        @if(!empty($student->elective_subjects))
                                            <div class="text-secondary small lh-sm" style="font-size: 0.72rem; word-break: break-word;">
                                                {{ $student->elective_subjects }}
                                            </div>
                                        @endif
                                    </td>
                                    @endforeach
                                    @if($chunk->count() < 10)
                                        @for($i = $chunk->count(); $i < 10; $i++)
                                        <td class="text-center" style="width: 10%;">&nbsp;</td>
                                        @endfor
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No students enrolled in this subject for the selected admission year.
                    </div>
                @endif
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
        font-weight: normal !important;
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
