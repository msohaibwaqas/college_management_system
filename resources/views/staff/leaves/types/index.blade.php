@extends('layouts.app')

@section('title', 'Manage Leave Types & Limits')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-sliders-h me-2"></i>Leave Types & Policy Limits</h1>
            <p class="page-subtitle">Configure annual leave quotas, regular monthly limits, special case exceptions, and valid time periods.</p>
        </div>
        <div>
            <a href="{{ route('my-leaves.index') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Leaves
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLeaveTypeModal">
                <i class="fas fa-plus me-1"></i> Create Leave Type
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Please check the errors below:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Leave Type</th>
                            <th>Code</th>
                            <th>Annual Quota</th>
                            <th>Monthly Limit</th>
                            <th>Special Case Limit</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveTypes as $type)
                        <tr>
                            <td>
                                <strong>{{ $type->name }}</strong>
                                @if($type->requires_document)
                                    <span class="badge bg-info ms-1"><i class="fas fa-paperclip"></i> Doc Required</span>
                                @endif
                            </td>
                            <td><code>{{ $type->code }}</code></td>
                            <td><span class="badge bg-primary fs-6">{{ $type->default_days }} days/yr</span></td>
                            <td><span class="badge bg-info text-dark fs-6">{{ $type->max_leaves_per_month ?? 2 }} days/mo</span></td>
                            <td><span class="badge bg-warning text-dark fs-6">{{ $type->special_case_max_leaves_per_month ?? 7 }} days/mo</span></td>
                            <td>
                                @if($type->period_start_date && $type->period_end_date)
                                    <small class="text-muted"><i class="far fa-calendar-alt me-1"></i>{{ $type->period_start_date->format('d-m-Y') }} to {{ $type->period_end_date->format('d-m-Y') }}</small>
                                @else
                                    <span class="text-muted small">No date restriction</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $type->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editLeaveTypeModal{{ $type->id }}">
                                    <i class="fas fa-edit"></i> Edit Limits
                                </button>
                                <form action="{{ route('leave-types.destroy', $type->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this leave type?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Leave Type Modal -->
                        <div class="modal fade" id="editLeaveTypeModal{{ $type->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form action="{{ route('leave-types.update', $type->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Leave Type & Limits: {{ $type->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Leave Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ old('name', $type->name) }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Code</label>
                                                    <input type="text" name="code" class="form-control" value="{{ old('code', $type->code) }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">Annual Quota (Days)</label>
                                                    <input type="number" name="default_days" class="form-control" value="{{ old('default_days', $type->default_days) }}" required min="0">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold text-primary">Max Regular Leaves / Month</label>
                                                    <input type="number" name="max_leaves_per_month" class="form-control border-primary" value="{{ old('max_leaves_per_month', $type->max_leaves_per_month ?? 2) }}" required min="1">
                                                    <div class="form-text small">Standard limit (e.g. 2 days)</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold text-warning">Special Case Max / Month</label>
                                                    <input type="number" name="special_case_max_leaves_per_month" class="form-control border-warning" value="{{ old('special_case_max_leaves_per_month', $type->special_case_max_leaves_per_month ?? 7) }}" required min="1">
                                                    <div class="form-text small">Special case limit (e.g. 7 days)</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Period Start Date</label>
                                                    <input type="date" name="period_start_date" class="form-control" value="{{ old('period_start_date', $type->period_start_date ? $type->period_start_date->format('Y-m-d') : '') }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Period End Date</label>
                                                    <input type="date" name="period_end_date" class="form-control" value="{{ old('period_end_date', $type->period_end_date ? $type->period_end_date->format('Y-m-d') : '') }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mt-3">
                                                        <input class="form-check-input" type="checkbox" name="is_paid" value="1" id="isPaidEdit{{ $type->id }}" {{ $type->is_paid ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="isPaidEdit{{ $type->id }}">Is Paid Leave</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mt-3">
                                                        <input class="form-check-input" type="checkbox" name="requires_document" value="1" id="reqDocEdit{{ $type->id }}" {{ $type->requires_document ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="reqDocEdit{{ $type->id }}">Requires Supporting Document</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label fw-bold">Description</label>
                                                    <textarea name="description" class="form-control" rows="2">{{ old('description', $type->description) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No leave types found. Click "Create Leave Type" above to add one.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Leave Type Modal -->
<div class="modal fade" id="createLeaveTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('leave-types.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create New Leave Type & Policy Limits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Leave Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Casual Leave" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. CASUAL" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Annual Quota (Days) <span class="text-danger">*</span></label>
                            <input type="number" name="default_days" class="form-control" value="{{ old('default_days', 25) }}" required min="0">
                            <div class="form-text small">e.g. 25 days in a year</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-primary">Max Regular Leaves / Month <span class="text-danger">*</span></label>
                            <input type="number" name="max_leaves_per_month" class="form-control border-primary" value="{{ old('max_leaves_per_month', 2) }}" required min="1">
                            <div class="form-text small">Default max 2 leaves/month</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-warning">Special Case Max / Month <span class="text-danger">*</span></label>
                            <input type="number" name="special_case_max_leaves_per_month" class="form-control border-warning" value="{{ old('special_case_max_leaves_per_month', 7) }}" required min="1">
                            <div class="form-text small">Special case max 7 leaves/month</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Period Start Date</label>
                            <input type="date" name="period_start_date" class="form-control" value="{{ old('period_start_date', date('Y') . '-01-01') }}">
                            <div class="form-text small">e.g. 01-01-2026</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Period End Date</label>
                            <input type="date" name="period_end_date" class="form-control" value="{{ old('period_end_date', date('Y') . '-12-31') }}">
                            <div class="form-text small">e.g. 31-12-2026</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="is_paid" value="1" id="isPaidNew" checked>
                                <label class="form-check-label fw-bold" for="isPaidNew">Is Paid Leave</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="requires_document" value="1" id="reqDocNew">
                                <label class="form-check-label fw-bold" for="reqDocNew">Requires Supporting Document</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Details about this leave policy...">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Leave Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
