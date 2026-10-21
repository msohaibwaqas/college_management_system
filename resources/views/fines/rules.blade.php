@extends('layouts.app')
@section('title', 'Fine Policy Rules & Rates — Admin Panel')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-sliders-h text-primary me-2"></i>Fine Policy Rules &amp; Rates
            </h1>
            <p class="text-muted small mb-0 mt-1">
                Configure institutional fine amounts and passing criteria for lecture absences, test absences, and test failures.
            </p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="{{ route('fines.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Ledger
            </a>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addRuleModal">
                <i class="fas fa-plus me-1"></i> Add Policy Rule
            </button>
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

    {{-- Rules Table Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-list me-2"></i>Active &amp; Configured Rules
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Rule Name</th>
                            <th>Category</th>
                            <th>Exam / Target</th>
                            <th>Rate (Rs. / unit)</th>
                            <th>Pass Criteria</th>
                            <th>Year</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $i => $rule)
                        <tr>
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-bold">{{ $rule->name }}</div>
                                @if($rule->description)
                                    <small class="text-muted">{{ $rule->description }}</small>
                                @endif
                            </td>
                            <td>
                                @if($rule->fine_type === 'lecture_absent')
                                    <span class="badge bg-primary">Lecture Absence</span>
                                @elseif($rule->fine_type === 'test_absent')
                                    <span class="badge bg-danger">Test Absence</span>
                                @else
                                    <span class="badge bg-warning text-dark">Test Failure</span>
                                @endif
                            </td>
                            <td>
                                @if($rule->exam_name)
                                    <code>{{ $rule->exam_name }}</code>
                                @else
                                    <span class="text-muted">— (Daily Attendance)</span>
                                @endif
                            </td>
                            <td class="fw-bold text-success">
                                Rs. {{ number_format($rule->amount_per_unit, 2) }}
                            </td>
                            <td>
                                @if($rule->fine_type === 'test_fail')
                                    <span class="badge bg-info text-dark">&lt; {{ $rule->pass_percentage }}% Marks</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($rule->academic_year)
                                    <span class="badge bg-light text-dark border">{{ $rule->academic_year }}</span>
                                @else
                                    <span class="badge bg-secondary">All Years</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rule->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#editRuleModal{{ $rule->id }}">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="editRuleModal{{ $rule->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('fines.rules.update', $rule->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Fine Policy Rule</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Rule Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $rule->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Fine Category <span class="text-danger">*</span></label>
                                                        <select name="fine_type" class="form-select" required>
                                                            <option value="lecture_absent" {{ $rule->fine_type === 'lecture_absent' ? 'selected' : '' }}>Lecture Absence</option>
                                                            <option value="test_absent" {{ $rule->fine_type === 'test_absent' ? 'selected' : '' }}>Test Absence</option>
                                                            <option value="test_fail" {{ $rule->fine_type === 'test_fail' ? 'selected' : '' }}>Test Failure</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Exam Name <span class="text-muted">(for tests)</span></label>
                                                        <input type="text" name="exam_name" class="form-control" value="{{ $rule->exam_name }}" placeholder="e.g. October Test, December Test">
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold small">Rate Amount (Rs.) <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" name="amount_per_unit" class="form-control" value="{{ $rule->amount_per_unit }}" min="0.01" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold small">Pass % <span class="text-muted">(for failures)</span></label>
                                                            <input type="number" step="0.5" name="pass_percentage" class="form-control" value="{{ $rule->pass_percentage }}" min="1" max="100">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Academic Year</label>
                                                        <input type="number" name="academic_year" class="form-control" value="{{ $rule->academic_year }}" placeholder="Leave blank for all years">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Description</label>
                                                        <textarea name="description" class="form-control" rows="2">{{ $rule->description }}</textarea>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSw{{ $rule->id }}" {{ $rule->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold small" for="activeSw{{ $rule->id }}">Rule is Active &amp; Enforced</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Rule Modal --}}
<div class="modal fade" id="addRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('fines.rules.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Fine Policy Rule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Rule Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. October Test - Absent Fine" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Fine Category <span class="text-danger">*</span></label>
                        <select name="fine_type" class="form-select" required>
                            <option value="lecture_absent">Lecture Absence</option>
                            <option value="test_absent">Test Absence</option>
                            <option value="test_fail">Test Failure</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Exam Name <span class="text-muted">(leave empty for daily lectures)</span></label>
                        <input type="text" name="exam_name" class="form-control" list="addExamList" placeholder="e.g. October Test, December Test">
                        <datalist id="addExamList">
                            @foreach($availableExamNames as $en)
                                <option value="{{ $en }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Rate Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount_per_unit" class="form-control" placeholder="e.g. 100.00" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Pass % <span class="text-muted">(for failures)</span></label>
                            <input type="number" step="0.5" name="pass_percentage" class="form-control" value="33.0" min="1" max="100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Academic Year</label>
                        <input type="number" name="academic_year" class="form-control" value="{{ date('Y') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Description <span class="text-muted">(optional)</span></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. Implemented on students missing October test papers..."></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSwNew" checked>
                        <label class="form-check-label fw-bold small" for="activeSwNew">Rule is Active &amp; Enforced</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Create Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
