@extends('layouts.app')

@section('title', 'Teacher Workload Report')

@section('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .tier-badge-high {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }
    .tier-badge-moderate {
        background-color: #cfe2ff;
        color: #084298;
        border: 1px solid #b6d4fe;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }
    .tier-badge-light {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }
    .tier-badge-unassigned {
        background-color: #e2e3e5;
        color: #41464b;
        border: 1px solid #d3d6d8;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
    }
    .col-teacher-name {
        white-space: nowrap !important;
    }
    @media print {
        body {
            background-color: #fff !important;
        }
        .sidebar, .navbar, .btn-print-hide, .filter-card, .btn-group, footer {
            display: none !important;
        }
        .card {
            border: 1px solid #ccc !important;
            box-shadow: none !important;
        }
        .table {
            font-size: 0.85rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-briefcase text-primary me-2"></i>Teacher Workload Report
            </h1>
            <p class="text-muted small mb-0">Overview of weekly classes scheduled, active students taught, and assigned subject workloads.</p>
        </div>
        <div class="mt-2 mt-sm-0 d-flex gap-2 btn-print-hide">
            <a href="{{ route('reports.teacher_attendance_status') }}" class="btn btn-outline-warning btn-sm text-dark">
                <i class="fas fa-clipboard-check me-1"></i> Teacher Attendance Status
            </a>
            <button onclick="window.print();" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-print me-1"></i> Print / PDF
            </button>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card shadow-sm mb-4 border-0 filter-card">
        <div class="card-body bg-light rounded-3 p-3">
            <form action="{{ route('reports.teacher_workload') }}" method="GET" id="workloadFilterForm" class="row g-3 align-items-end">
                
                {{-- Search --}}
                <div class="col-md-3 col-sm-6">
                    <label for="search" class="form-label fw-bold text-dark small">
                        <i class="fas fa-search text-primary me-1"></i>Search Teacher
                    </label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" 
                           placeholder="Teacher name or ID..." value="{{ $search }}">
                </div>

                {{-- Designation Filter --}}
                <div class="col-md-3 col-sm-6">
                    <label for="designation_filter" class="form-label fw-bold text-dark small">
                        <i class="fas fa-user-tie text-primary me-1"></i>Designation
                    </label>
                    <select name="designation_filter" id="designation_filter" class="form-select form-select-sm" onchange="document.getElementById('workloadFilterForm').submit();">
                        <option value="">All Designations</option>
                        @foreach($designations as $desig)
                            <option value="{{ $desig }}" {{ $designationFilter == $desig ? 'selected' : '' }}>{{ $desig }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- BPS Filter --}}
                <div class="col-md-2 col-sm-4">
                    <label for="bps_filter" class="form-label fw-bold text-dark small">
                        <i class="fas fa-award text-primary me-1"></i>BPS Grade
                    </label>
                    <select name="bps_filter" id="bps_filter" class="form-select form-select-sm" onchange="document.getElementById('workloadFilterForm').submit();">
                        <option value="">All BPS</option>
                        @foreach($bpsList as $bps)
                            <option value="{{ $bps }}" {{ $bpsFilter == $bps ? 'selected' : '' }}>BPS-{{ $bps }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tier Filter --}}
                <div class="col-md-4 col-sm-8">
                    <label class="form-label fw-bold text-dark small">
                        <i class="fas fa-filter text-primary me-1"></i>Workload Tier
                    </label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="tier_filter" id="tier_all" value="all" {{ $tierFilter == 'all' ? 'checked' : '' }} onchange="document.getElementById('workloadFilterForm').submit();">
                        <label class="btn btn-outline-primary btn-sm" for="tier_all">All</label>

                        <input type="radio" class="btn-check" name="tier_filter" id="tier_high" value="high" {{ $tierFilter == 'high' ? 'checked' : '' }} onchange="document.getElementById('workloadFilterForm').submit();">
                        <label class="btn btn-outline-danger btn-sm" for="tier_high" title=">= 25 classes/week">Heavy (≥25)</label>

                        <input type="radio" class="btn-check" name="tier_filter" id="tier_moderate" value="moderate" {{ $tierFilter == 'moderate' ? 'checked' : '' }} onchange="document.getElementById('workloadFilterForm').submit();">
                        <label class="btn btn-outline-info btn-sm" for="tier_moderate" title="15-24 classes/week">Moderate (15-24)</label>

                        <input type="radio" class="btn-check" name="tier_filter" id="tier_light" value="light" {{ $tierFilter == 'light' ? 'checked' : '' }} onchange="document.getElementById('workloadFilterForm').submit();">
                        <label class="btn btn-outline-success btn-sm" for="tier_light" title="1-14 classes/week">Light (1-14)</label>

                        <input type="radio" class="btn-check" name="tier_filter" id="tier_unassigned" value="unassigned" {{ $tierFilter == 'unassigned' ? 'checked' : '' }} onchange="document.getElementById('workloadFilterForm').submit();">
                        <label class="btn btn-outline-secondary btn-sm" for="tier_unassigned">0 Load</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Teachers --}}
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0 border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small">Teaching Staff</span>
                            <h2 class="mb-0 font-weight-bold text-dark mt-1">{{ $totalTeachersCount }}</h2>
                        </div>
                        <div class="rounded-circle bg-primary-subtle p-3 text-primary">
                            <i class="fas fa-chalkboard-teacher fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Weekly Classes --}}
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0 border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small">Weekly Classes</span>
                            <h2 class="mb-0 font-weight-bold text-info mt-1">{{ $totalScheduledClasses }}</h2>
                        </div>
                        <div class="rounded-circle bg-info-subtle p-3 text-info">
                            <i class="fas fa-calendar-alt fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Students Taught --}}
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small">Students Taught</span>
                            <h2 class="mb-0 font-weight-bold text-success mt-1">{{ $totalStudentsTaughtSum }}</h2>
                        </div>
                        <div class="rounded-circle bg-success-subtle p-3 text-success">
                            <i class="fas fa-user-graduate fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Avg Load --}}
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold small">Avg Weekly Load</span>
                            <h2 class="mb-0 font-weight-bold text-dark mt-1">{{ $avgWeeklyClasses }} <small class="text-muted fs-6">classes/wk</small></h2>
                        </div>
                        <div class="rounded-circle bg-warning-subtle p-3 text-warning">
                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="m-0 font-weight-bold text-dark d-inline-block me-3">
                    <i class="fas fa-list text-primary me-2"></i>Teacher Workload List
                </h6>
                <span class="badge bg-secondary">Showing {{ $reportItems->count() }} Teacher(s)</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="4%" class="text-center">#</th>
                            <th class="col-teacher-name">Teacher Name</th>
                            <th>Designation</th>
                            <th class="text-center">Weekly Classes</th>
                            <th class="text-center">Students Taught</th>
                            <th class="text-center">Assigned Subjects</th>
                            <th class="text-center">Workload Tier</th>
                            <th class="text-center btn-print-hide">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportItems as $index => $item)
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                <td class="col-teacher-name">
                                    <div class="fw-bold text-dark">{{ $item->teacher_name }}</div>
                                    @if(!empty($item->employee_id))
                                        <small class="text-muted">ID: {{ $item->employee_id }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $item->designation }}</span>
                                    @if(!empty($item->bps))
                                        <span class="badge bg-light text-dark border ms-1">BPS-{{ $item->bps }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge fs-6 {{ $item->weekly_classes >= 25 ? 'bg-danger' : ($item->weekly_classes >= 15 ? 'bg-primary' : ($item->weekly_classes > 0 ? 'bg-success' : 'bg-secondary')) }}">
                                        {{ $item->weekly_classes }} Classes/Wk
                                    </span>
                                    @if($item->he_weekly_classes > 0 && $item->hssc_weekly_classes > 0)
                                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                            HSSC: {{ $item->hssc_weekly_classes }} | BS: {{ $item->he_weekly_classes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-dark fs-6">{{ $item->total_students_taught }}</span>
                                    @if($item->he_students_count > 0 && $item->hssc_students_count > 0)
                                        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                            HSSC: {{ $item->hssc_students_count }} | BS: {{ $item->he_students_count }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-outline-info text-dark border fw-bold">
                                        <i class="fas fa-book me-1"></i>{{ $item->subjects_count }} Subject(s)
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($item->workload_tier === 'high')
                                        <span class="tier-badge-high"><i class="fas fa-fire me-1"></i>Heavy ({{ $item->weekly_classes }})</span>
                                    @elseif($item->workload_tier === 'moderate')
                                        <span class="tier-badge-moderate"><i class="fas fa-check-circle me-1"></i>Moderate ({{ $item->weekly_classes }})</span>
                                    @elseif($item->workload_tier === 'light')
                                        <span class="tier-badge-light"><i class="fas fa-leaf me-1"></i>Light ({{ $item->weekly_classes }})</span>
                                    @else
                                        <span class="tier-badge-unassigned"><i class="fas fa-minus-circle me-1"></i>No Timetable</span>
                                    @endif
                                </td>
                                <td class="text-center btn-print-hide">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#teacherDetailsModal{{ $item->staff_id }}">
                                        <i class="fas fa-eye me-1"></i>View Breakdown
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-2 d-block text-secondary"></i>
                                    No teacher workload records match the selected search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Detail Modals for Each Teacher --}}
@foreach($reportItems as $item)
    <div class="modal fade" id="teacherDetailsModal{{ $item->staff_id }}" tabindex="-1" aria-labelledby="teacherDetailsModalLabel{{ $item->staff_id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="teacherDetailsModalLabel{{ $item->staff_id }}">
                        <i class="fas fa-user-tie me-2"></i>{{ $item->teacher_name }} — Workload Breakdown
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3 g-2">
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light text-center">
                                <small class="text-muted d-block">Designation</small>
                                <strong>{{ $item->designation }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light text-center">
                                <small class="text-muted d-block">Weekly Classes</small>
                                <strong class="text-primary">{{ $item->weekly_classes }} Slots/Wk</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light text-center">
                                <small class="text-muted d-block">Students Taught</small>
                                <strong class="text-success">{{ $item->total_students_taught }} Active</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light text-center">
                                <small class="text-muted d-block">Assigned Subjects</small>
                                <strong>{{ $item->subjects_count }} Subjects</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mt-3 mb-2"><i class="fas fa-layer-group text-primary me-2"></i>Assigned Teaching Blocks & Sections</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">Type</th>
                                    <th>Subject</th>
                                    <th>Class / Program & Section</th>
                                    <th class="text-center">Lecture / Slot No.</th>
                                    <th class="text-center">Weekly Slots</th>
                                    <th class="text-center">Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($item->assigned_blocks as $blk)
                                    <tr>
                                        <td>
                                            <span class="badge {{ $blk->type === 'HSSC' ? 'bg-primary' : 'bg-info text-dark' }}">
                                                {{ $blk->type }}
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            {{ $blk->subject_name }}
                                            @if(!empty($blk->subject_code))
                                                <small class="text-muted">({{ $blk->subject_code }})</small>
                                            @endif
                                        </td>
                                        <td>{{ $blk->description }}</td>
                                        <td class="text-center">
                                            @if(!empty($blk->slot_numbers))
                                                <span class="badge bg-light text-dark border fw-bold" title="{{ $blk->slot_names ?? '' }}">
                                                    <i class="fas fa-list-ol me-1 text-primary"></i>Slot #{{ $blk->slot_numbers }}
                                                </span>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!empty($blk->slots_count) && $blk->slots_count > 0)
                                                <span class="badge bg-primary-subtle text-primary border border-primary fw-bold">
                                                    <i class="fas fa-clock me-1"></i>{{ $blk->slots_count }} Slot(s)/Wk
                                                </span>
                                            @else
                                                <span class="badge bg-light text-secondary border">0 Slots</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold text-success">{{ $blk->student_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">
                                            No active student-teacher subject assignments found for this teacher.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
