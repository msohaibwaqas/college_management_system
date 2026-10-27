@extends('layouts.app')
@section('title', 'Manage Date Sheet')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2><i class="fas fa-calendar-alt me-2"></i>Test Date Sheet</h2>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#nameExamModal">
                <i class="fas fa-tag me-1"></i>Name Exam by Range
            </button>
            <button type="button" id="bulkNameBtn" class="btn btn-primary" style="display: none;" onclick="openBulkNameModal()">
                <i class="fas fa-tags me-1"></i>Name Selected (<span id="selectedCountText">0</span>)
            </button>
            <a href="{{ route('datesheets.summary') }}" class="btn btn-info text-white">
                <i class="fas fa-list-alt me-1"></i>Summary View
            </a>
            <a href="{{ route('datesheets.generate') }}" class="btn btn-success">
                <i class="fas fa-magic me-1"></i>Auto Generate
            </a>
            <a href="{{ route('datesheets.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Schedule New Test
            </a>
            <button type="button" id="bulkDeleteBtn" class="btn btn-danger" style="display: none;" onclick="confirmBulkDelete()">
                <i class="fas fa-trash me-1"></i>Delete Selected
            </button>
        </div>
    </div>

    <form id="bulkDeleteForm" action="{{ route('datesheets.bulk_destroy') }}" method="POST" style="display: none;">
        @csrf
    </form>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-list me-2"></i>Scheduled Tests</h5>
            <small class="text-muted">Total Scheduled Tests: <strong>{{ $datesheets->count() }}</strong></small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Exam / Test Name</th>
                            <th>Admission Year</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datesheets as $datesheet)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $datesheet->id }}" class="form-check-input select-item">
                                </td>
                                <td class="fw-bold">{{ $datesheet->test_date->format('d M, Y') }}</td>
                                <td>{{ $datesheet->test_date->format('l') }}</td>
                                <td>
                                    @if($datesheet->exam_name)
                                        <span class="badge bg-secondary text-white px-2 py-1 fs-7">
                                            <i class="fas fa-bookmark me-1"></i>{{ $datesheet->exam_name }}
                                        </span>
                                    @else
                                        <span class="text-muted small fst-italic">Unassigned</span>
                                    @endif
                                </td>
                                <td>Batch {{ $datesheet->admission_year }}</td>
                                <td>{{ $datesheet->subject->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $datesheet->subject->type === 'Compulsory' ? 'primary' : 'info' }}">
                                        {{ $datesheet->subject->type }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('datesheets.destroy', $datesheet) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this test?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Test">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No tests scheduled yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal 1: Name Exam by Date Range --}}
<div class="modal fade" id="nameExamModal" tabindex="-1" aria-labelledby="nameExamModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('datesheets.name_exam') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="nameExamModalLabel">
                        <i class="fas fa-tag me-2"></i>Name Exam for Date Range
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Set a unified test/exam name (e.g. <strong>October Test 2026</strong>) across all tests falling in a date range. This preset name will be automatically loaded when taking test attendance and calculating fines.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Exam / Test Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" class="form-control" list="examSuggestions" placeholder="e.g. October Test 2026, December Test 2026..." required>
                        <datalist id="examSuggestions">
                            <option value="October Test 2026"></option>
                            <option value="December Test 2026"></option>
                            <option value="Pre-Board 2026"></option>
                            <option value="Send-Up Exam 2026"></option>
                            <option value="Class Test"></option>
                        </datalist>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="2026-10-30" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="2026-11-09" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Admission Year / Batch <span class="text-muted fw-normal">(Optional)</span></label>
                        <select name="admission_year" class="form-select">
                            <option value="">All Batches</option>
                            <option value="2025">Batch 2025 (Class 12)</option>
                            <option value="2026">Batch 2026 (Class 11)</option>
                        </select>
                        <small class="text-muted">Leave as "All Batches" to apply to both 11th and 12th class tests in this range.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>Apply Exam Name
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal 2: Name Selected Tests --}}
<div class="modal fade" id="nameSelectedModal" tabindex="-1" aria-labelledby="nameSelectedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkNameForm" action="{{ route('datesheets.name_exam') }}" method="POST">
                @csrf
                <div id="bulkNameIdsContainer"></div>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="nameSelectedModalLabel">
                        <i class="fas fa-tags me-2"></i>Name Selected Tests
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Assign the exam name to the <strong id="modalSelectedCount">0</strong> selected test(s).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Exam / Test Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" id="selectedExamNameInput" class="form-control" list="examSuggestions" placeholder="e.g. October Test 2026" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>Update Selected Tests
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
    const selectAllCb = document.getElementById('selectAll');
    const selectItems = document.querySelectorAll('.select-item');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkNameBtn = document.getElementById('bulkNameBtn');
    const selectedCountText = document.getElementById('selectedCountText');

    function updateSelectionUI() {
        const checkedItems = document.querySelectorAll('.select-item:checked');
        const count = checkedItems.length;
        if (selectedCountText) selectedCountText.innerText = count;
        if (bulkDeleteBtn) bulkDeleteBtn.style.display = count > 0 ? 'inline-block' : 'none';
        if (bulkNameBtn) bulkNameBtn.style.display = count > 0 ? 'inline-block' : 'none';
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            selectItems.forEach(cb => cb.checked = this.checked);
            updateSelectionUI();
        });
    }

    selectItems.forEach(cb => {
        cb.addEventListener('change', updateSelectionUI);
    });

    function openBulkNameModal() {
        const checkedItems = document.querySelectorAll('.select-item:checked');
        if (checkedItems.length === 0) return;

        const container = document.getElementById('bulkNameIdsContainer');
        container.innerHTML = '';
        checkedItems.forEach(item => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'ids[]';
            hidden.value = item.value;
            container.appendChild(hidden);
        });

        document.getElementById('modalSelectedCount').innerText = checkedItems.length;
        const modal = new bootstrap.Modal(document.getElementById('nameSelectedModal'));
        modal.show();
    }

    function confirmBulkDelete() {
        if (confirm('Are you sure you want to delete the selected tests?')) {
            const form = document.getElementById('bulkDeleteForm');
            const checkedItems = document.querySelectorAll('.select-item:checked');
            
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            
            checkedItems.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = item.value;
                form.appendChild(input);
            });
            
            form.submit();
        }
    }
</script>
@endsection
@endsection
