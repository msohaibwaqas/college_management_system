@extends('layouts.app')

@section('title', isset($selectedSubject) ? "{$selectedSubject->name} - Subject Final Result Report" : 'Subject Final Result Report')

@section('styles')
<style>
    .report-card {
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        border: none;
    }
    .metric-card {
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.08);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }
    .table-report th {
        vertical-align: middle;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .table-report td {
        vertical-align: middle;
    }
    .teacher-badge {
        display: inline-flex;
        align-items: center;
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 3px 8px;
        font-size: 0.8rem;
    }
    .marks-badge {
        font-size: 0.95rem;
        font-weight: 700;
    }
    .part-badge {
        font-size: 0.76rem;
        padding: 3px 7px;
        border-radius: 4px;
    }
    @media print {
        #sidebar, .filter-section, .btn-print, .btn-export, .nav, footer, .app-header {
            display: none !important;
        }
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table-responsive {
            overflow: visible !important;
        }
        .table {
            font-size: 11px !important;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-3">
    <!-- Header Card -->
    <div class="card report-card mb-4">
        <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center py-3">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-file-invoice me-2"></i>Subject Final Result Report
                </h4>
                <small class="text-white-50">
                    BISE 11th &amp; 12th Examination Marks &amp; Assigned Subject Teacher
                </small>
            </div>
            @if(!empty($selectedSubject))
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-light text-primary fs-6 px-3 py-2 me-2">
                        <i class="fas fa-calendar-alt me-1"></i> Year: {{ $selectedYear }}
                    </span>
                    <span class="badge bg-info text-white fs-6 px-3 py-2">
                        <i class="fas fa-book me-1"></i> {{ $selectedSubject->name }} ({{ $selectedSubject->code ?? 'N/A' }})
                    </span>
                </div>
            @endif
        </div>

        <div class="card-body filter-section bg-light border-bottom">
            <form action="{{ route('reports.subject-final-result-report.index') }}" method="GET" id="reportFilterForm" class="row g-3 align-items-end">
                <!-- Admission Year -->
                <div class="col-md-3">
                    <label for="admission_year" class="form-label fw-bold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i>Admission Year
                    </label>
                    <select name="admission_year" id="admission_year" class="form-select" required>
                        <option value="">-- Select Year --</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ (string)$selectedYear === (string)$year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Subject Selection -->
                <div class="col-md-3">
                    <label for="subject_id" class="form-label fw-bold">
                        <i class="fas fa-book text-primary me-1"></i>Subject
                    </label>
                    <select name="subject_id" id="subject_id" class="form-select" required {{ empty($selectedYear) && $subjects->isEmpty() ? 'disabled' : '' }}>
                        <option value="">{{ $subjects->isNotEmpty() ? '-- Select Subject --' : '-- First Select Year --' }}</option>
                        @php
                            $compulsoryList = $subjects->where('type', 'Compulsory');
                            $electiveList = $subjects->where('type', '!=', 'Compulsory');
                        @endphp

                        @if($compulsoryList->isNotEmpty())
                            <optgroup label="Compulsory Subjects">
                                @foreach($compulsoryList as $sub)
                                    <option value="{{ $sub->id }}" {{ (!empty($selectedSubject) && $selectedSubject->id == $sub->id) ? 'selected' : '' }}>
                                        {{ $sub->name }} {{ $sub->code ? "({$sub->code})" : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if($electiveList->isNotEmpty())
                            <optgroup label="Elective Subjects">
                                @foreach($electiveList as $sub)
                                    <option value="{{ $sub->id }}" {{ (!empty($selectedSubject) && $selectedSubject->id == $sub->id) ? 'selected' : '' }}>
                                        {{ $sub->name }} {{ $sub->code ? "({$sub->code})" : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                <!-- Optional Teacher Filter -->
                <div class="col-md-3">
                    <label for="staff_id" class="form-label fw-bold">
                        <i class="fas fa-chalkboard-teacher text-primary me-1"></i>Teacher (Optional)
                    </label>
                    <select name="staff_id" id="staff_id" class="form-select" {{ $teachers->isEmpty() ? 'disabled' : '' }}>
                        <option value="">-- All Teachers --</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ (string)$selectedStaffId === (string)$teacher->id ? 'selected' : '' }}>
                                {{ $teacher->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search me-1"></i> Load
                        </button>
                        @if(!empty($selectedSubject) && $reportData->isNotEmpty())
                            <button type="submit" name="export" value="excel" class="btn btn-success" title="Download Excel Sheet">
                                <i class="fas fa-file-excel"></i> Export
                            </button>
                            <button type="button" onclick="window.print()" class="btn btn-secondary" title="Print Report">
                                <i class="fas fa-print"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        @if(!empty($selectedSubject))
            <div class="card-body">
                <!-- Summary Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-2">
                        <div class="p-3 bg-white metric-card text-center">
                            <div class="text-muted small text-uppercase fw-bold">Total Enrolled</div>
                            <div class="fs-4 fw-bold text-primary">{{ $summary['total_students'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="p-3 bg-white metric-card text-center">
                            <div class="text-muted small text-uppercase fw-bold">Appeared</div>
                            <div class="fs-4 fw-bold text-dark">{{ $summary['appeared'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="p-3 bg-white metric-card text-center">
                            <div class="text-muted small text-uppercase fw-bold">Passed</div>
                            <div class="fs-4 fw-bold text-success">{{ $summary['passed'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="p-3 bg-white metric-card text-center">
                            <div class="text-muted small text-uppercase fw-bold">Failed</div>
                            <div class="fs-4 fw-bold text-danger">{{ $summary['failed'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="p-3 bg-white metric-card text-center">
                            <div class="text-muted small text-uppercase fw-bold">Absent</div>
                            <div class="fs-4 fw-bold text-warning">{{ $summary['absent'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="p-3 bg-white metric-card text-center">
                            <div class="text-muted small text-uppercase fw-bold">Pass Rate</div>
                            <div class="fs-4 fw-bold text-info">{{ $summary['pass_percentage'] }}%</div>
                        </div>
                    </div>
                </div>

                <!-- Table Controls: Search & Split-Column View Toggle -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="max-width: 320px;">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="tableSearch" class="form-control" placeholder="Search roll no, name, teacher...">
                        </div>
                        <span id="recordCountBadge" class="badge bg-secondary">
                            {{ $reportData->count() }} records
                        </span>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="toggleSplitColumns">
                        <label class="form-check-label small fw-bold text-muted" for="toggleSplitColumns">
                            Split 11th, 12th &amp; Teacher Columns
                        </label>
                    </div>
                </div>

                @if($reportData->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle table-report text-center" id="subjectResultTable">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 7%;">Roll No</th>
                                    <th style="width: 10%;">BISE 11th Roll No</th>
                                    <th style="width: 10%;">BISE 12th Roll No</th>
                                    <th style="width: 20%;" class="text-start">Student Name</th>
                                    <th style="width: 18%;" class="text-start">Father Name</th>
                                    
                                    <!-- Combined Column (Default) -->
                                    <th class="col-combined text-center" style="width: 35%;">
                                        <div class="fw-bold fs-6">{{ $selectedSubject->name }}</div>
                                        <div class="text-warning small" style="font-size: 0.75rem; font-weight: normal;">
                                            (11th &amp; 12th BISE Marks | Teacher Name)
                                        </div>
                                    </th>

                                    <!-- Split Columns (Hidden by default, toggled via switch) -->
                                    <th class="col-split text-center d-none" style="width: 8%;">
                                        <div class="fw-bold">11th BISE</div>
                                    </th>
                                    <th class="col-split text-center d-none" style="width: 11%;">
                                        <div class="fw-bold">12th BISE</div>
                                    </th>
                                    <th class="col-split text-center d-none" style="width: 8%;">
                                        <div class="fw-bold">Total BISE</div>
                                    </th>
                                    <th class="col-split text-center d-none" style="width: 12%;">
                                        <div class="fw-bold">Teacher Name</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData as $row)
                                    <tr class="report-row">
                                        <!-- Roll No -->
                                        <td class="fw-bold text-dark">{{ $row['roll_no'] }}</td>

                                        <!-- BISE 11th Roll No -->
                                        <td class="fw-semibold text-primary font-monospace">{{ $row['bise_roll_11'] }}</td>

                                        <!-- BISE 12th Roll No -->
                                        <td class="fw-bold text-primary font-monospace">{{ $row['bise_roll_12'] }}</td>

                                        <!-- Student Name -->
                                        <td class="text-start fw-semibold text-dark">{{ $row['student_name'] }}</td>

                                        <!-- Father Name -->
                                        <td class="text-start text-secondary">{{ $row['father_name'] }}</td>

                                        <!-- Combined Column: Selected Subject Name (Both 11th & 12th Marks | Teacher) -->
                                        <td class="col-combined text-center">
                                            <div class="d-flex flex-column align-items-center justify-content-center py-1">
                                                <!-- Total Marks -->
                                                @if($row['status'] === 'Absent')
                                                    <span class="badge bg-danger px-2 py-1 mb-1">Absent</span>
                                                @elseif($row['combined_marks'] !== '-')
                                                    <div class="mb-1">
                                                        <span class="marks-badge {{ $row['status'] === 'Pass' ? 'text-success' : 'text-danger' }}">
                                                            Total: {{ $row['combined_marks'] }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="text-muted fw-bold mb-1">-</span>
                                                @endif

                                                <!-- 11th & 12th Marks Badges -->
                                                @if($row['marks_11'] !== '-' || $row['marks_12'] !== '-')
                                                    <div class="d-flex flex-wrap justify-content-center gap-1 mb-1" style="font-size: 0.75rem;">
                                                        @if($row['marks_11'] !== '-')
                                                            <span class="badge bg-light text-dark border part-badge">
                                                                <span class="text-secondary">11th:</span>
                                                                <strong class="{{ $row['is_absent_11'] ? 'text-danger' : 'text-primary' }}">{{ $row['marks_11'] }}</strong>
                                                            </span>
                                                        @endif
                                                        @if($row['marks_12'] !== '-')
                                                            <span class="badge bg-light text-dark border part-badge">
                                                                <span class="text-secondary">12th:</span>
                                                                <strong class="{{ $row['is_absent_12'] ? 'text-danger' : 'text-primary' }}">{{ $row['marks_12'] }}</strong>
                                                                @if(!empty($row['details_12']))
                                                                    <small class="text-muted">({{ $row['details_12'] }})</small>
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif

                                                <!-- Teacher Name -->
                                                <div class="pt-1 mt-1 border-top w-100 text-center" style="border-color: rgba(0,0,0,0.08) !important;">
                                                    <span class="teacher-badge">
                                                        <i class="fas fa-chalkboard-teacher text-primary me-1"></i>
                                                        <span class="teacher-text">{{ $row['teacher_name'] }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Split Column: 11th Marks -->
                                        <td class="col-split text-center d-none">
                                            @if($row['is_absent_11'])
                                                <span class="badge bg-danger">Absent</span>
                                            @elseif($row['marks_11'] !== '-')
                                                <strong class="text-primary">{{ $row['marks_11'] }}</strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <!-- Split Column: 12th Marks -->
                                        <td class="col-split text-center d-none">
                                            @if($row['is_absent_12'])
                                                <span class="badge bg-danger">Absent</span>
                                            @elseif($row['marks_12'] !== '-')
                                                <div>
                                                    <strong class="text-primary">{{ $row['marks_12'] }}</strong>
                                                    @if(!empty($row['details_12']))
                                                        <small class="d-block text-muted" style="font-size: 0.72rem;">{{ $row['details_12'] }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <!-- Split Column: Total BISE Marks -->
                                        <td class="col-split text-center d-none">
                                            @if($row['status'] === 'Absent')
                                                <span class="badge bg-danger">Absent</span>
                                            @elseif($row['combined_marks'] !== '-')
                                                <strong class="{{ $row['status'] === 'Pass' ? 'text-success' : 'text-danger' }} fs-6">
                                                    {{ $row['combined_marks'] }}
                                                </strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <!-- Split Column: Teacher -->
                                        <td class="col-split text-center d-none">
                                            <span class="teacher-badge">
                                                <i class="fas fa-chalkboard-teacher text-primary me-1"></i>
                                                <span class="teacher-text">{{ $row['teacher_name'] }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info text-center py-4 my-3">
                        <i class="fas fa-info-circle fa-2x mb-2 text-info d-block"></i>
                        <h5>No Students Found</h5>
                        <p class="mb-0 text-muted">
                            No students were found enrolled in <strong>{{ $selectedSubject->name }}</strong> for admission year <strong>{{ $selectedYear }}</strong>.
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="card-body text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary opacity-50"></i>
                    <h5>Please Select Admission Year and Subject</h5>
                    <p class="mb-0">
                        Choose an admission year and a subject from the filters above, then click <strong>Load</strong> to generate the report.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Dynamic AJAX loading of subjects when Admission Year changes
        $('#admission_year').on('change', function() {
            const year = $(this).val();
            const subjectSelect = $('#subject_id');
            const staffSelect = $('#staff_id');

            if (!year) {
                subjectSelect.html('<option value="">-- First Select Year --</option>').prop('disabled', true);
                staffSelect.html('<option value="">-- All Teachers --</option>').prop('disabled', true);
                return;
            }

            subjectSelect.html('<option value="">Loading subjects...</option>').prop('disabled', true);
            staffSelect.html('<option value="">-- All Teachers --</option>').prop('disabled', true);

            $.ajax({
                url: '{{ route("reports.subject-final-result-report.get-subjects") }}',
                method: 'GET',
                data: { admission_year: year },
                success: function(subjects) {
                    subjectSelect.empty();
                    subjectSelect.append('<option value="">-- Select Subject --</option>');

                    if (subjects.length > 0) {
                        let compulsoryGroup = $('<optgroup label="Compulsory Subjects"></optgroup>');
                        let electiveGroup = $('<optgroup label="Elective Subjects"></optgroup>');
                        let hasComp = false;
                        let hasElec = false;

                        subjects.forEach(function(sub) {
                            const option = $('<option></option>')
                                .val(sub.id)
                                .text(sub.name + (sub.code ? ' (' + sub.code + ')' : ''));

                            if (sub.type === 'Compulsory') {
                                compulsoryGroup.append(option);
                                hasComp = true;
                            } else {
                                electiveGroup.append(option);
                                hasElec = true;
                            }
                        });

                        if (hasComp) subjectSelect.append(compulsoryGroup);
                        if (hasElec) subjectSelect.append(electiveGroup);

                        subjectSelect.prop('disabled', false);
                    } else {
                        subjectSelect.append('<option value="">No subjects found for this year</option>');
                    }
                },
                error: function() {
                    subjectSelect.html('<option value="">Error loading subjects</option>');
                }
            });
        });

        // Instant Live Table Search
        $('#tableSearch').on('keyup', function() {
            const query = $(this).val().toLowerCase().trim();
            let visibleCount = 0;

            $('#subjectResultTable tbody tr.report-row').each(function() {
                const rowText = $(this).text().toLowerCase();
                if (rowText.indexOf(query) !== -1) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            $('#recordCountBadge').text(visibleCount + ' records');
        });

        // Toggle Split vs Combined Column view
        $('#toggleSplitColumns').on('change', function() {
            if ($(this).is(':checked')) {
                $('.col-combined').addClass('d-none');
                $('.col-split').removeClass('d-none');
            } else {
                $('.col-combined').removeClass('d-none');
                $('.col-split').addClass('d-none');
            }
        });
    });
</script>
@endsection
