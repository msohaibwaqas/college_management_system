@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" id="custom-reports-hub">

    {{-- Printable Letterhead (Visible only when printed) --}}
    <div class="d-none d-print-block mb-4 text-center border-bottom pb-3">
        <h2 class="fw-bold mb-1 text-uppercase text-dark">{{ $instituteSettings->institute_title ?? 'College Management System' }}</h2>
        <p class="mb-1 text-muted small">{{ $instituteSettings->address ?? 'Institutional Academic & Administrative Management' }}</p>
        <h4 class="fw-bold text-primary mt-2">
            {{ $viewMode === 'summary' ? 'EXECUTIVE SUMMARY & AGGREGATE REPORT' : 'DETAILED CUSTOM STUDENT REPORT' }}
        </h4>
        <div class="d-flex justify-content-between text-muted small mt-2 px-3">
            <span><strong>Academic Year:</strong> {{ $selectedYear ?? 'All' }}</span>
            <span><strong>Grouped By:</strong> {{ ucfirst($groupBy) }}</span>
            <span><strong>Generated On:</strong> {{ date('d M Y, h:i A') }}</span>
        </div>
    </div>

    {{-- Header Banner Card --}}
    <div class="row justify-content-center mb-4 d-print-none">
        <div class="col-12">
            <div class="card border-0 shadow-sm" class="card border-0 shadow-sm report-hero-card" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <span class="badge bg-white text-primary px-3 py-1 mb-2 fw-bold text-uppercase" style="letter-spacing: 0.5px; border-radius: 20px;">
                                <i class="fas fa-layer-group me-1"></i> Unified Analytics
                            </span>
                            <h3 class="fw-bold text-white mb-1">
                                <i class="fas fa-sliders-h me-2"></i>Dynamic Custom Report & Query Builder
                            </h3>
                            <p class="text-white-50 mb-0 small">
                                Summarize, aggregate, and drill down across Demographics, Academics, Attendance, and Collections in one place.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if(!empty($summaryRows) || !empty($detailedRows))
                            <a href="{{ route('reports.custom-student-report.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm rounded-pill fw-semibold">
                                <i class="fas fa-file-excel me-1"></i> Export to Excel
                            </a>
                            <button onclick="window.print()" class="btn btn-light btn-sm px-3 shadow-sm rounded-pill fw-semibold text-primary">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                            @endif
                            <a href="{{ route('reports.custom-student-report') }}" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Executive KPI Metrics Row --}}
    @if(!empty($summaryRows) || !empty($detailedRows))
    <div class="row g-3 mb-4 d-print-none">
        {{-- Total Students --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--col-primary) !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Total Enrolled</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1">{{ number_format($kpi['total_students']) }}</h3>
                        </div>
                        <div class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                            <i class="fas fa-user-graduate fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top small text-muted d-flex justify-content-between">
                        <span>Active: <strong class="text-success">{{ number_format($kpi['active_students']) }}</strong></span>
                        <span>Year: <strong>{{ $selectedYear }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Overall Pass Rate --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--col-secondary) !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Exam Pass Rate</span>
                            <h3 class="fw-bold text-success mb-0 mt-1">{{ $kpi['pass_percentage'] }}%</h3>
                        </div>
                        <div class="badge bg-success-subtle text-success p-2 rounded-circle">
                            <i class="fas fa-award fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top small text-muted d-flex justify-content-between">
                        <span>Passed: <strong class="text-success">{{ $kpi['pass_count'] }}</strong></span>
                        <span>Appeared: <strong>{{ $kpi['appeared_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Average Attendance --}}
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--col-highlight) !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Average Attendance</span>
                            <h3 class="fw-bold {{ $kpi['avg_attendance'] >= 75 ? 'text-primary' : 'text-warning' }} mb-0 mt-1">
                                {{ $kpi['avg_attendance'] }}%
                            </h3>
                        </div>
                        <div class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                            <i class="fas fa-calendar-check fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top small text-muted d-flex justify-content-between">
                        <span class="text-danger" title="Students with >10 absents">
                            <i class="fas fa-exclamation-triangle me-1"></i><strong>{{ $kpi['struck_off_risk_count'] }}</strong> at risk
                        </span>
                        <span class="text-secondary">Low (&lt;75%): <strong>{{ $kpi['low_attendance_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fee Recovery --}}
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--col-primary) !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Fee Recovery</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1">{{ $kpi['fee_recovery_rate'] }}%</h3>
                        </div>
                        <div class="badge bg-purple-subtle text-purple p-2 rounded-circle" style="color: #8b5cf6; background: #f3e8ff;">
                            <i class="fas fa-receipt fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top small text-muted d-flex justify-content-between">
                        <span>Recvd: <strong class="text-success">PKR {{ number_format($kpi['total_paid']) }}</strong></span>
                        <span>Due: <strong class="text-danger">PKR {{ number_format($kpi['total_balance']) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Struck-Off Count --}}
        <div class="col-xl-2 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--col-accent) !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Struck Off</span>
                            <h3 class="fw-bold text-danger mb-0 mt-1">{{ number_format($kpi['struck_off_students']) }}</h3>
                        </div>
                        <div class="badge bg-danger-subtle text-danger p-2 rounded-circle">
                            <i class="fas fa-user-slash fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top small text-muted d-flex justify-content-between">
                        <span>Status: <strong class="text-danger">Restricted</strong></span>
                        <a href="{{ route('struck-off.index') }}" class="text-primary text-decoration-none">Manage &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Filter & Query Console Card --}}
    <div class="card border-0 shadow-sm mb-4 d-print-none" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-filter text-primary"></i>
                <h6 class="mb-0 fw-bold text-dark">Query & Filter Console</h6>
            </div>
            <button class="btn btn-link btn-sm text-decoration-none p-0 text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#filterConsoleCollapse" aria-expanded="true">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div class="collapse show" id="filterConsoleCollapse">
            <div class="card-body p-4 bg-light bg-opacity-50">
                <form action="{{ route('reports.custom-student-report') }}" method="GET" id="reportForm">

                    {{-- Row 1: Primary Dimensions --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Admission Year <span class="text-danger">*</span></label>
                            <select name="admission_year" class="form-select form-select-sm shadow-sm" required onchange="this.form.submit()">
                                @foreach($admissionYears as $yr)
                                    <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Class Level</label>
                            <select name="class_level" class="form-select form-select-sm shadow-sm">
                                <option value="">All Classes</option>
                                <option value="11" {{ request('class_level') == '11' ? 'selected' : '' }}>11th (First Year)</option>
                                <option value="12" {{ request('class_level') == '12' ? 'selected' : '' }}>12th (Second Year)</option>
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Subject Group / Program</label>
                            <select name="subject_group_id" class="form-select form-select-sm shadow-sm">
                                <option value="">All Subject Groups</option>
                                @foreach($subjectGroups as $grp)
                                    <option value="{{ $grp->id }}" {{ request('subject_group_id') == $grp->id ? 'selected' : '' }}>{{ $grp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Section</label>
                            <select name="section" class="form-select form-select-sm shadow-sm">
                                <option value="">All Sections</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>{{ $sec }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Admission Status</label>
                            <select name="status" class="form-select form-select-sm shadow-sm">
                                <option value="">All Admission Statuses</option>
                                @foreach($admissionStatuses as $st)
                                    <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Secondary / Module Specific Filters --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Subject</label>
                            <select name="subject_id" class="form-select form-select-sm shadow-sm">
                                <option value="">All Subjects</option>
                                @foreach($subjects as $sub)
                                    <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }} ({{ $sub->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Teacher (Staff)</label>
                            <select name="staff_id" class="form-select form-select-sm shadow-sm">
                                <option value="">All Teachers</option>
                                @foreach($teachers as $tch)
                                    <option value="{{ $tch->id }}" {{ request('staff_id') == $tch->id ? 'selected' : '' }}>{{ $tch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Fee Status</label>
                            <select name="fee_status" class="form-select form-select-sm shadow-sm">
                                <option value="">All Fee Statuses</option>
                                <option value="paid" {{ request('fee_status') == 'paid' ? 'selected' : '' }}>Paid in Full</option>
                                <option value="partial" {{ request('fee_status') == 'partial' ? 'selected' : '' }}>Partially Paid</option>
                                <option value="unpaid" {{ request('fee_status') == 'unpaid' ? 'selected' : '' }}>Unpaid Only</option>
                                <option value="has_dues" {{ request('fee_status') == 'has_dues' ? 'selected' : '' }}>Has Outstanding Dues</option>
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Attendance Filter</label>
                            <select name="attendance_filter" class="form-select form-select-sm shadow-sm">
                                <option value="">All Attendance Levels</option>
                                <option value="low" {{ request('attendance_filter') == 'low' ? 'selected' : '' }}>Low Attendance (&lt;75%)</option>
                                <option value="risk" {{ request('attendance_filter') == 'risk' ? 'selected' : '' }}>Absence Alert (&gt;10 Absents)</option>
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Result Status</label>
                            <select name="result_status_filter" class="form-select form-select-sm shadow-sm">
                                <option value="">All Result Statuses</option>
                                <option value="pass" {{ request('result_status_filter') == 'pass' ? 'selected' : '' }}>Passed (Clear & Grace)</option>
                                <option value="reappear" {{ request('result_status_filter') == 'reappear' ? 'selected' : '' }}>Reappear Only</option>
                                <option value="absent" {{ request('result_status_filter') == 'absent' ? 'selected' : '' }}>Absent in Exam</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 3: Text Search & Fast Preset Chips --}}
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-md-6">
                            <div class="input-group input-group-sm shadow-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by student name, roll number, or father's name..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group input-group-sm shadow-sm">
                                <span class="input-group-text bg-white small text-muted">Pass % Threshold</span>
                                <input type="number" name="pass_percentage" class="form-control" value="{{ $passPercentage }}" min="1" max="100">
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <div class="d-flex flex-wrap gap-1 justify-content-md-end">
                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 small" onclick="applyPreset('attendance_filter', 'risk')">
                                    <i class="fas fa-bolt"></i> &gt;10 Absents
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2 small text-dark" onclick="applyPreset('fee_status', 'has_dues')">
                                    <i class="fas fa-coins"></i> Defaulters
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 small" onclick="applyPreset('status', 'Struck Off')">
                                    <i class="fas fa-ban"></i> Struck Off
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3 opacity-10">

                    {{-- Row 4: View Mode & Group By Selector --}}
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase d-block mb-1">Select Report View Mode</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="view_mode" id="vm_summary" value="summary" {{ $viewMode === 'summary' ? 'checked' : '' }} onchange="toggleViewMode('summary')">
                                <label class="btn btn-outline-primary btn-sm px-3 fw-bold" for="vm_summary">
                                    <i class="fas fa-chart-pie me-1"></i> Summary & Analytics View
                                </label>

                                <input type="radio" class="btn-check" name="view_mode" id="vm_detailed" value="detailed" {{ $viewMode === 'detailed' ? 'checked' : '' }} onchange="toggleViewMode('detailed')">
                                <label class="btn btn-outline-primary btn-sm px-3 fw-bold" for="vm_detailed">
                                    <i class="fas fa-list-alt me-1"></i> Detailed Records View
                                </label>
                            </div>
                        </div>

                        {{-- Group By Selector (Active when View Mode is Summary) --}}
                        <div class="col-md-3" id="groupByContainer" style="{{ $viewMode === 'summary' ? '' : 'display:none;' }}">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1">Group Summary By</label>
                            <select name="group_by" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                                <option value="group" {{ $groupBy == 'group' ? 'selected' : '' }}>Subject Group / Program</option>
                                <option value="class" {{ $groupBy == 'class' ? 'selected' : '' }}>Class Level (11th vs 12th)</option>
                                <option value="section" {{ $groupBy == 'section' ? 'selected' : '' }}>Section</option>
                                <option value="status" {{ $groupBy == 'status' ? 'selected' : '' }}>Admission Status</option>
                                <option value="result_status" {{ $groupBy == 'result_status' ? 'selected' : '' }}>Exam Performance</option>
                                <option value="fee_status" {{ $groupBy == 'fee_status' ? 'selected' : '' }}>Fee Status</option>
                                <option value="teacher" {{ $groupBy == 'teacher' ? 'selected' : '' }}>Teacher Assignment</option>
                            </select>
                        </div>

                        {{-- Detailed Column Picker Trigger (Active when View Mode is Detailed) --}}
                        <div class="col-md-3 text-md-end" id="columnsTriggerContainer" style="{{ $viewMode === 'detailed' ? '' : 'display:none;' }}">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1 d-block">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" data-bs-toggle="collapse" data-bs-target="#columnPickerCollapse">
                                <i class="fas fa-columns me-1"></i> Choose Columns ({{ count($selectedColumns) }})
                            </button>
                        </div>

                        <div class="col-md-3 text-end ms-auto">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1 d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm rounded-pill fw-bold">
                                <i class="fas fa-sync-alt me-1"></i> Generate Report
                            </button>
                        </div>
                    </div>

                    {{-- Collapsible Column Picker (Detailed View) --}}
                    <div class="collapse mt-3 {{ $viewMode === 'detailed' && request()->has('open_columns') ? 'show' : '' }}" id="columnPickerCollapse">
                        <div class="card border border-primary border-opacity-25 shadow-sm p-3 bg-white" style="border-radius: 12px;">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-check-square me-2"></i>Select Table Columns for Detailed View</h6>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkAllColumns(true)">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkAllColumns(false)">Clear All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkDefaultColumns()">Recommended</button>
                                </div>
                            </div>

                            @php
                                $categories = [];
                                foreach ($columnDefinitions as $k => $def) {
                                    $categories[$def['category']][$k] = $def;
                                }
                            @endphp

                            <div class="row g-3">
                                @foreach($categories as $categoryName => $cols)
                                <div class="col-md-4 col-sm-6">
                                    <div class="p-2 border rounded bg-light bg-opacity-50 h-100">
                                        <div class="fw-bold text-uppercase text-secondary small border-bottom pb-1 mb-2">
                                            <i class="fas fa-chevron-circle-right text-primary me-1"></i> {{ $categoryName }}
                                        </div>
                                        @foreach($cols as $colKey => $colDef)
                                        <div class="form-check form-check-sm mb-1">
                                            <input class="form-check-input col-checkbox" type="checkbox" name="columns[]" value="{{ $colKey }}" id="col_{{ $colKey }}" {{ in_array($colKey, $selectedColumns) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="col_{{ $colKey }}">
                                                {{ $colDef['label'] }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- REPORT CONTENT PRESENTATION --}}
    @if($viewMode === 'summary')

        {{-- SUMMARY & AGGREGATE VIEW TABLE --}}
        <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>Executive Summary: Grouped by {{ ucwords(str_replace('_', ' ', $groupBy)) }}
                    </h6>
                    <span class="text-muted small">Showing aggregated statistics for {{ number_format($kpi['total_students']) }} total students.</span>
                </div>
                <div class="d-print-none">
                    <span class="badge bg-light text-dark border px-2 py-1">
                        {{ count($summaryRows) }} Categories
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" id="summaryTable">
                        <thead class="bg-light text-uppercase fw-bold text-muted small border-bottom">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Category / Group</th>
                                <th class="text-center">Enrolled</th>
                                <th class="text-center">M / F</th>
                                <th class="text-center">Struck Off</th>
                                <th class="text-center">Avg Attendance</th>
                                <th class="text-center">Exam Passed / Appeared</th>
                                <th class="text-center">Pass %</th>
                                <th class="text-end">Fee Billed</th>
                                <th class="text-end">Fee Collected</th>
                                <th class="text-end">Balance Due</th>
                                <th class="text-center">Recovery %</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse($summaryRows as $idx => $row)
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                                <td class="fw-bold text-dark">
                                    {{ $row['label'] }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">{{ number_format($row['total_students']) }}</span>
                                </td>
                                <td class="text-center text-muted">
                                    <span title="Male">{{ $row['male_count'] }}M</span> / <span title="Female">{{ $row['female_count'] }}F</span>
                                </td>
                                <td class="text-center">
                                    @if($row['struck_off_count'] > 0)
                                        <span class="badge bg-danger text-white px-2 py-1">{{ $row['struck_off_count'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold {{ $row['avg_attendance'] >= 75 ? 'text-success' : 'text-danger' }}">
                                        {{ $row['avg_attendance'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong>{{ $row['pass_count'] }}</strong> / <span class="text-muted">{{ $row['appeared_count'] }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <span class="badge {{ $row['pass_rate'] >= 80 ? 'bg-success' : ($row['pass_rate'] >= 50 ? 'bg-primary' : 'bg-danger') }} px-2">
                                            {{ $row['pass_rate'] }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end text-muted">PKR {{ number_format($row['total_billed']) }}</td>
                                <td class="text-end fw-bold text-success">PKR {{ number_format($row['total_paid']) }}</td>
                                <td class="text-end {{ $row['total_balance'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    PKR {{ number_format($row['total_balance']) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $row['recovery_rate'] >= 90 ? 'bg-success' : ($row['recovery_rate'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') }} px-2 py-1">
                                        {{ $row['recovery_rate'] }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center py-5 text-muted">
                                    <i class="fas fa-chart-pie fa-3x text-light mb-3 d-block"></i>
                                    <h5>No records found for the selected query filters.</h5>
                                    <p class="small text-muted mb-0">Try adjusting your filters or selecting a different admission year.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($summaryRows))
                        <tfoot class="bg-light fw-bold small border-top-2">
                            <tr>
                                <td colspan="2" class="text-uppercase text-center">OVERALL TOTALS / AVERAGES</td>
                                <td class="text-center text-primary">{{ number_format($kpi['total_students']) }}</td>
                                <td class="text-center text-muted">--</td>
                                <td class="text-center text-danger">{{ number_format($kpi['struck_off_students']) }}</td>
                                <td class="text-center text-primary">{{ $kpi['avg_attendance'] }}%</td>
                                <td class="text-center">{{ $kpi['pass_count'] }} / {{ $kpi['appeared_count'] }}</td>
                                <td class="text-center text-success">{{ $kpi['pass_percentage'] }}%</td>
                                <td class="text-end text-muted">PKR {{ number_format($kpi['total_billed']) }}</td>
                                <td class="text-end text-success">PKR {{ number_format($kpi['total_paid']) }}</td>
                                <td class="text-end text-danger">PKR {{ number_format($kpi['total_balance']) }}</td>
                                <td class="text-center text-success">{{ $kpi['fee_recovery_rate'] }}%</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    @else

        {{-- DETAILED RECORDS VIEW TABLE --}}
        <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-table text-primary me-2"></i>Detailed Student Records ({{ count($detailedRows) }} Students)
                    </h6>
                    <span class="text-muted small">Showing customized column selection for individual student tracking.</span>
                </div>
                <div class="d-flex align-items-center gap-2 d-print-none">
                    <input type="text" id="detailedTableSearch" class="form-control form-control-sm" placeholder="Live filter rows..." style="max-width: 200px;">
                    <span class="badge bg-light text-muted border py-1" id="recordCountBadge">
                        Showing {{ count($detailedRows) }}
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" id="detailedStudentTable">
                        <thead class="bg-light text-uppercase fw-bold text-muted small text-center">
                            <tr>
                                <th style="width: 45px;">#</th>
                                @foreach($selectedColumns as $colKey)
                                    <th>{{ $columnDefinitions[$colKey]['label'] ?? ucfirst($colKey) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse($detailedRows as $index => $row)
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                @foreach($selectedColumns as $colKey)
                                    @php $val = $row[$colKey] ?? '--'; @endphp
                                    <td>
                                        @if($colKey === 'status')
                                            <span class="badge {{ $val === 'Active' ? 'bg-success' : ($val === 'Struck Off' ? 'bg-danger' : 'bg-secondary') }} px-2">
                                                {{ $val }}
                                            </span>
                                        @elseif($colKey === 'result_status')
                                            <span class="badge {{ in_array($val, ['Pass', 'Pass (Grace)']) ? 'bg-success' : ($val === 'Reappear' ? 'bg-danger' : 'bg-secondary') }} px-2">
                                                {{ $val }}
                                            </span>
                                        @elseif($colKey === 'fee_status')
                                            <span class="badge {{ $val === 'Paid' ? 'bg-success' : ($val === 'Partially Paid' ? 'bg-warning text-dark' : ($val === 'Unpaid' ? 'bg-danger' : 'bg-light text-muted border')) }} px-2">
                                                {{ $val }}
                                            </span>
                                        @elseif($colKey === 'attendance_pct')
                                            <span class="fw-bold {{ $val >= 75 ? 'text-success' : ($val > 0 ? 'text-warning' : 'text-muted') }}">
                                                {{ $val }}%
                                            </span>
                                        @elseif($colKey === 'struck_off_risk')
                                            @if($row['struck_off_risk'])
                                                <span class="badge bg-danger-subtle text-danger border border-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> &gt;10 Absents
                                                </span>
                                            @else
                                                <span class="text-success"><i class="fas fa-check-circle"></i> Normal</span>
                                            @endif
                                        @elseif(in_array($colKey, ['fee_billed', 'fee_paid', 'fee_balance']))
                                            PKR {{ number_format($val) }}
                                        @else
                                            {{ $val }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ count($selectedColumns) + 1 }}" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-3x text-light mb-3 d-block"></i>
                                    <h5>No student records matched the specified query criteria.</h5>
                                    <p class="small text-muted mb-0">Select another admission year or clear specific filters above.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endif

</div>

{{-- Custom Scripts --}}
@push('scripts')
<script>
    function toggleViewMode(mode) {
        const groupByCont = document.getElementById('groupByContainer');
        const colTriggerCont = document.getElementById('columnsTriggerContainer');
        const colPickerCollapse = document.getElementById('columnPickerCollapse');

        if (mode === 'summary') {
            if (groupByCont) groupByCont.style.display = 'block';
            if (colTriggerCont) colTriggerCont.style.display = 'none';
            if (colPickerCollapse) {
                var bsCollapse = bootstrap.Collapse.getInstance(colPickerCollapse);
                if (bsCollapse) bsCollapse.hide();
            }
        } else {
            if (groupByCont) groupByCont.style.display = 'none';
            if (colTriggerCont) colTriggerCont.style.display = 'block';
        }
    }

    function checkAllColumns(status) {
        document.querySelectorAll('.col-checkbox').forEach(cb => cb.checked = status);
    }

    function checkDefaultColumns() {
        const defaults = ['college_roll_no', 'name', 'father_name', 'class_level', 'subject_group', 'status', 'attendance_pct', 'result_status', 'fee_status'];
        document.querySelectorAll('.col-checkbox').forEach(cb => {
            cb.checked = defaults.includes(cb.value);
        });
    }

    function applyPreset(field, value) {
        const form = document.getElementById('reportForm');
        if (form && form.elements[field]) {
            form.elements[field].value = value;
            form.submit();
        }
    }

    // Live search filter for detailed student records table
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('detailedTableSearch');
        const table = document.getElementById('detailedStudentTable');
        const badge = document.getElementById('recordCountBadge');

        if (searchInput && table) {
            searchInput.addEventListener('keyup', function () {
                const term = searchInput.value.toLowerCase().trim();
                const rows = table.querySelectorAll('tbody tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (badge) {
                    badge.textContent = 'Showing ' + visibleCount;
                }
            });
        }
    });
</script>
@endpush

{{-- Print Styles --}}
<style>
@media print {
    body {
        background-color: #fff !important;
        color: #000 !important;
        font-size: 11px !important;
    }
    .d-print-none,
    #sidebar,
    .navbar,
    .main-header,
    footer,
    .btn,
    .card-header .btn {
        display: none !important;
    }
    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table {
        width: 100% !important;
        border: 1px solid #ddd !important;
        font-size: 10px !important;
    }
    .table th, .table td {
        padding: 4px 6px !important;
        border: 1px solid #ddd !important;
    }
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: transparent !important;
        font-weight: bold !important;
    }
}
</style>
@endsection
