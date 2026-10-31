@extends('layouts.app')
@section('title', 'Calculate & Generate Fines — Accounts Panel')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-calculator text-primary me-2"></i>Calculate &amp; Generate Fines
            </h1>
            <p class="text-muted small mb-0 mt-1">
                Calculate fines dynamically from attendance records and examination results, preview items, and generate dues in bulk.
            </p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="{{ route('fines.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Ledger
            </a>
            <a href="{{ route('fines.rules') }}" class="btn btn-outline-primary btn-sm shadow-sm">
                <i class="fas fa-sliders-h me-1"></i> Policy Rules
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Calculation Criteria Card --}}
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white py-3">
            <h6 class="m-0 fw-bold">
                <i class="fas fa-cogs me-2"></i>Step 1: Select Fine Criteria &amp; Rate
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('fines.generate') }}" id="calcForm">
                <input type="hidden" name="preview" value="1">

                <div class="row g-3">
                    {{-- Preset Rule Selection --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">
                            <i class="fas fa-magic text-warning me-1"></i>Apply Preset Policy Rule <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select name="fine_rule_id" id="ruleSelect" class="form-select">
                            <option value="">-- Choose a standard policy rule or configure below --</option>
                            @foreach($rules as $r)
                                <option value="{{ $r->id }}"
                                        data-type="{{ $r->fine_type }}"
                                        data-rate="{{ $r->amount_per_unit }}"
                                        data-exam="{{ $r->exam_name }}"
                                        data-pass="{{ $r->pass_percentage }}"
                                        {{ request('fine_rule_id') == $r->id ? 'selected' : '' }}>
                                    {{ $r->name }} — Rs. {{ number_format($r->amount_per_unit, 2) }} / unit
                                    @if($r->exam_name) ({{ $r->exam_name }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Selecting a preset automatically configures fine type, rate, and exam settings.</small>
                    </div>

                    {{-- Fine Type --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">
                            <i class="fas fa-tag text-primary me-1"></i>Fine Type Trigger <span class="text-danger">*</span>
                        </label>
                        <select name="fine_type" id="fineTypeSelect" class="form-select" required>
                            <option value="lecture_absent" {{ request('fine_type', 'lecture_absent') === 'lecture_absent' ? 'selected' : '' }}>
                                Lecture Absence (e.g. Rs. 1 per absent)
                            </option>
                            <option value="test_absent" {{ request('fine_type') === 'test_absent' ? 'selected' : '' }}>
                                Test Absence (e.g. Oct: Rs. 100, Dec: Rs. 150 per paper)
                            </option>
                            <option value="test_fail" {{ request('fine_type') === 'test_fail' ? 'selected' : '' }}>
                                Test Failure (e.g. Oct: Rs. 50, Dec: Rs. 100 per failed subject)
                            </option>
                        </select>
                    </div>

                    {{-- Attendance Month Picker (For Lecture Absences) --}}
                    <div class="col-md-4" id="periodBox">
                        <label class="form-label fw-bold small text-dark">
                            <i class="fas fa-calendar-alt text-primary me-1"></i>Attendance Month <span class="text-danger">*</span>
                        </label>
                        <select name="period" id="periodInput" class="form-select">
                            @foreach($availableMonths as $m)
                                <option value="{{ $m }}" {{ request('period', now()->format('Y-m')) === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($m . '-01')->format('F Y') }} ({{ $m }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Absences in this calendar month will be counted.</small>
                    </div>

                    {{-- Exam Name Picker (Loaded from Test Attendance & Datesheets) --}}
                    <div class="col-md-4" id="examBox" style="display: none;">
                        <label class="form-label fw-bold small text-dark">
                            <i class="fas fa-file-alt text-danger me-1"></i>Exam / Test Name <span class="text-danger">*</span>
                        </label>
                        <select name="exam_name" id="examNameSelect" class="form-select">
                            <option value="">-- Select Exam / Test Name (Preset) --</option>
                            @foreach($availableExamsWithDetails as $ex)
                                @php
                                    $reqName = strtolower(trim((string) request('exam_name')));
                                    $exName = strtolower(trim((string) $ex['exam_name']));
                                    $isSelected = ($reqName !== '' && $reqName === $exName)
                                        || ($reqName === '' && str_contains($exName, 'october test'));
                                @endphp
                                <option value="{{ $ex['exam_name'] }}"
                                        data-start="{{ $ex['start_date'] }}"
                                        data-end="{{ $ex['end_date'] }}"
                                        {{ $isSelected ? 'selected' : '' }}>
                                    {{ $ex['exam_name'] }}
                                    @if(!empty($ex['range_text'])) ({{ $ex['range_text'] }}) @endif
                                    @if(($ex['total_absent'] ?? 0) > 0) [{{ $ex['total_absent'] }} absent] @endif
                                </option>
                            @endforeach
                            <option value="__custom__" {{ request('exam_name') && !$availableExamsWithDetails->contains(fn($e) => strtolower(trim($e['exam_name'])) === strtolower(trim(request('exam_name')))) ? 'selected' : '' }}>
                                -- Custom / Type Other Name... --
                            </option>
                        </select>
                        <input type="text" id="customExamInput" class="form-control mt-2"
                               placeholder="Enter custom exam name..."
                               value="{{ request('exam_name') }}" style="display: none;">
                        <small class="text-muted">Loaded directly from Datesheet &amp; Test Attendance.</small>
                    </div>

                    {{-- Date Range Picker (Optional filter for test absences/failures) --}}
                    <div class="col-md-4" id="dateRangeBox" style="display: none;">
                        <label class="form-label fw-bold small text-dark">
                            <i class="fas fa-calendar-alt text-primary me-1"></i>Test Date Range <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <div class="row g-1">
                            <div class="col-6">
                                <input type="date" name="start_date" id="startDateInput" class="form-control"
                                       value="{{ request('start_date') }}" placeholder="Start Date">
                            </div>
                            <div class="col-6">
                                <input type="date" name="end_date" id="endDateInput" class="form-control"
                                       value="{{ request('end_date') }}" placeholder="End Date">
                            </div>
                        </div>
                        <small class="text-muted">Auto-filled from selected test datesheet range.</small>
                    </div>

                    {{-- Academic Year --}}
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label fw-bold small text-dark">Academic Year <span class="text-danger">*</span></label>
                        <input type="number" name="academic_year" class="form-control"
                               value="{{ request('academic_year', $currentYear) }}" min="2020" max="2035" required>
                    </div>

                    {{-- Class Level --}}
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label fw-bold small text-dark">Class Level</label>
                        <select name="class_level" class="form-select">
                            <option value="">All Classes (11th &amp; 12th)</option>
                            <option value="11" {{ request('class_level') == '11' ? 'selected' : '' }}>11th Class Only</option>
                            <option value="12" {{ request('class_level') == '12' ? 'selected' : '' }}>12th Class Only</option>
                        </select>
                    </div>

                    {{-- Fine Rate per Unit --}}
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label fw-bold small text-dark">
                            Fine Rate (Rs./unit) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" step="0.01" name="rate" id="rateInput" class="form-control"
                                   value="{{ request('rate', '1.00') }}" min="0.01" required>
                        </div>
                    </div>

                    {{-- Pass Percentage (Only for test_fail) --}}
                    <div class="col-md-2 col-sm-6" id="passBox" style="display: none;">
                        <label class="form-label fw-bold small text-dark">
                            Pass Mark % <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.5" name="pass_percentage" id="passInput" class="form-control"
                                   value="{{ request('pass_percentage', '33.0') }}" min="1" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                    <a href="{{ route('fines.generate') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo me-1"></i>Reset Form
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fas fa-search me-1"></i>Preview Calculations
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Step 2: Preview Results --}}
    @if($hasPreview)
        <div class="card shadow-sm mb-4 border-success">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 fw-bold text-success">
                        <i class="fas fa-list-check me-2"></i>Step 2: Preview &amp; Confirm Generation
                    </h6>
                    <small class="text-muted">
                        Total eligible students: <strong>{{ number_format($previewItems->count()) }}</strong> |
                        Grand fine total: <strong class="text-danger">Rs. {{ number_format($previewItems->sum('total_amount'), 2) }}</strong>
                    </small>
                </div>
                @if($previewItems->isNotEmpty())
                    <button type="button" class="btn btn-success btn-sm px-3 shadow-sm" id="btnSubmitGeneration">
                        <i class="fas fa-save me-1"></i>Generate &amp; Save Fines (<span id="selectedCountBadge">{{ $previewItems->count() }}</span>)
                    </button>
                @endif
            </div>

            <div class="card-body p-0">
                @if($previewItems->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                        <p class="mb-0">No eligible students found meeting these fine criteria.</p>
                        <small>Check if attendance or test marks have been entered for this period/exam.</small>
                    </div>
                @else
                    <form id="generationForm" action="{{ route('fines.generate.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="selected_items" id="selectedItemsInput" value="">

                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">
                                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input" checked>
                                        </th>
                                        <th>Student</th>
                                        <th>Roll No</th>
                                        <th>Class</th>
                                        <th>Type &amp; Criteria</th>
                                        <th>Details / Breakdown</th>
                                        <th class="text-center">Units &times; Rate</th>
                                        <th class="text-end">Total Fine</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewItems as $idx => $item)
                                    <tr class="{{ $item['already_exists'] ? 'table-warning-subtle' : '' }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input item-checkbox"
                                                   data-index="{{ $idx }}"
                                                   data-item="{{ json_encode($item) }}" checked>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $item['student_name'] }}</div>
                                        </td>
                                        <td><code>{{ $item['college_roll_no'] }}</code></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $item['class_level'] }}th</span>
                                        </td>
                                        <td>
                                            @if($item['fine_type'] === 'lecture_absent')
                                                <span class="badge bg-primary">Lecture Absent</span>
                                                <div class="small text-muted">{{ $item['period'] }}</div>
                                            @elseif($item['fine_type'] === 'test_absent')
                                                <span class="badge bg-danger">Test Absent</span>
                                                <div class="small fw-semibold">{{ $item['exam_name'] }}</div>
                                            @else
                                                <span class="badge bg-warning text-dark">Test Fail</span>
                                                <div class="small fw-semibold">{{ $item['exam_name'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small text-dark" style="max-width: 320px;">{{ $item['reason'] }}</div>
                                        </td>
                                        <td class="text-center small">
                                            <span class="fw-bold">{{ $item['units_count'] }}</span> &times; Rs. {{ number_format($item['amount_per_unit'], 2) }}
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            Rs. {{ number_format($item['total_amount'], 2) }}
                                        </td>
                                        <td class="text-center">
                                            @if($item['already_exists'])
                                                <span class="badge bg-warning text-dark" title="Fine already generated. Generating will update it if unpaid.">
                                                    <i class="fas fa-redo me-1"></i>Existing Fine
                                                </span>
                                            @else
                                                <span class="badge bg-success">
                                                    <i class="fas fa-plus me-1"></i>New Fine
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- Dynamic Criteria Form Script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fineTypeSelect = document.getElementById('fineTypeSelect');
    const ruleSelect = document.getElementById('ruleSelect');
    const periodBox = document.getElementById('periodBox');
    const examBox = document.getElementById('examBox');
    const dateRangeBox = document.getElementById('dateRangeBox');
    const passBox = document.getElementById('passBox');
    const rateInput = document.getElementById('rateInput');
    const examNameSelect = document.getElementById('examNameSelect');
    const customExamInput = document.getElementById('customExamInput');
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');
    const passInput = document.getElementById('passInput');

    function syncTypeUI() {
        const type = fineTypeSelect.value;
        if (type === 'lecture_absent') {
            periodBox.style.display = 'block';
            examBox.style.display = 'none';
            if (dateRangeBox) dateRangeBox.style.display = 'none';
            passBox.style.display = 'none';
        } else if (type === 'test_absent') {
            periodBox.style.display = 'none';
            examBox.style.display = 'block';
            if (dateRangeBox) dateRangeBox.style.display = 'block';
            passBox.style.display = 'none';
        } else if (type === 'test_fail') {
            periodBox.style.display = 'none';
            examBox.style.display = 'block';
            if (dateRangeBox) dateRangeBox.style.display = 'block';
            passBox.style.display = 'block';
        }
    }

    function onExamChange() {
        if (!examNameSelect) return;
        const opt = examNameSelect.options[examNameSelect.selectedIndex];
        if (!opt) return;

        if (opt.value === '__custom__') {
            customExamInput.style.display = 'block';
            customExamInput.setAttribute('name', 'exam_name');
            examNameSelect.removeAttribute('name');
        } else {
            customExamInput.style.display = 'none';
            customExamInput.removeAttribute('name');
            examNameSelect.setAttribute('name', 'exam_name');

            const start = opt.getAttribute('data-start');
            const end = opt.getAttribute('data-end');
            if (start && startDateInput && (!startDateInput.value || startDateInput.dataset.autofilled === 'true')) {
                startDateInput.value = start;
                startDateInput.dataset.autofilled = 'true';
            }
            if (end && endDateInput && (!endDateInput.value || endDateInput.dataset.autofilled === 'true')) {
                endDateInput.value = end;
                endDateInput.dataset.autofilled = 'true';
            }
        }
    }

    if (examNameSelect) {
        examNameSelect.addEventListener('change', onExamChange);
        onExamChange();
    }

    fineTypeSelect.addEventListener('change', syncTypeUI);
    syncTypeUI();

    ruleSelect.addEventListener('change', function () {
        const opt = ruleSelect.options[ruleSelect.selectedIndex];
        if (!opt.value) return;

        const type = opt.getAttribute('data-type');
        const rate = opt.getAttribute('data-rate');
        const exam = opt.getAttribute('data-exam');
        const pass = opt.getAttribute('data-pass');

        if (type) {
            fineTypeSelect.value = type;
            syncTypeUI();
        }
        if (rate) rateInput.value = rate;
        if (pass && passInput) passInput.value = pass;

        if (exam && examNameSelect) {
            let matched = false;
            for (let i = 0; i < examNameSelect.options.length; i++) {
                const optVal = examNameSelect.options[i].value.toLowerCase().trim();
                const examLower = exam.toLowerCase().trim();
                if (optVal === examLower || optVal.includes(examLower) || examLower.includes(optVal)) {
                    examNameSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                for (let i = 0; i < examNameSelect.options.length; i++) {
                    if (examNameSelect.options[i].value === '__custom__') {
                        examNameSelect.selectedIndex = i;
                        customExamInput.value = exam;
                        break;
                    }
                }
            }
            onExamChange();
        }
    });

    // Checkbox and Submission Logic
    const selectAll = document.getElementById('selectAllCheckbox');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const badge = document.getElementById('selectedCountBadge');
    const btnSubmit = document.getElementById('btnSubmitGeneration');
    const selectedInput = document.getElementById('selectedItemsInput');
    const genForm = document.getElementById('generationForm');

    function updateCount() {
        let count = 0;
        itemCheckboxes.forEach(cb => {
            if (cb.checked) count++;
        });
        if (badge) badge.innerText = count;
        if (btnSubmit) btnSubmit.disabled = (count === 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            itemCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateCount();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });

    if (btnSubmit && genForm) {
        btnSubmit.addEventListener('click', function () {
            const selectedItems = [];
            itemCheckboxes.forEach(cb => {
                if (cb.checked) {
                    try {
                        selectedItems.push(JSON.parse(cb.getAttribute('data-item')));
                    } catch(e) {}
                }
            });

            if (selectedItems.length === 0) {
                alert('Please select at least one fine item to generate.');
                return;
            }

            if (!confirm(`Are you sure you want to generate ${selectedItems.length} student fine(s)?`)) {
                return;
            }

            selectedInput.value = JSON.stringify(selectedItems);
            genForm.submit();
        });
    }
});
</script>
@endsection
