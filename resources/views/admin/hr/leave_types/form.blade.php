@extends('layouts.app')

@section('title', isset($leaveType) ? 'Edit Leave Type' : 'Add Leave Type')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-tags me-2"></i>{{ isset($leaveType) ? 'Edit Leave Type: ' . $leaveType->name : 'Add New Leave Type' }}
        </h1>
        <p class="page-subtitle">Configure leave rules, quotas, and required documents</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ isset($leaveType) ? route('leave-types.update', $leaveType->id) : route('leave-types.store') }}" method="POST">
                        @csrf
                        @if(isset($leaveType))
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Leave Title/Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $leaveType->name ?? '') }}" required placeholder="e.g. Medical Leave">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Short Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $leaveType->code ?? '') }}" required placeholder="e.g. ML, CL">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Default Annual Quota (Days) <span class="text-danger">*</span></label>
                                <input type="number" name="default_days" class="form-control" value="{{ old('default_days', $leaveType->default_days ?? 0) }}" required min="0">
                                <div class="form-text">Days automatically allocated to new staff each year.</div>
                            </div>

                            <div class="col-md-12 border-top pt-3 mt-4">
                                <h5 class="mb-3 text-primary">Rules & Constraints</h5>
                                <div class="form-check form-switch mb-3 custom-switch-lg">
                                    <input class="form-check-input" type="checkbox" role="switch" id="isPaidSwitch" name="is_paid" {{ old('is_paid', $leaveType->is_paid ?? true) ? 'checked' : '' }} style="transform: scale(1.2); transform-origin: left center;">
                                    <label class="form-check-label ms-2 fw-bold" for="isPaidSwitch">Is Paid Leave?</label>
                                    <div class="form-text mt-0 ms-4">If checked, salary will not be deducted for approved leaves of this type.</div>
                                </div>
                                
                                <div class="form-check form-switch mb-3 custom-switch-lg">
                                    <input class="form-check-input" type="checkbox" role="switch" id="reqDocSwitch" name="requires_document" {{ old('requires_document', $leaveType->requires_document ?? false) ? 'checked' : '' }} style="transform: scale(1.2); transform-origin: left center;">
                                    <label class="form-check-label ms-2 fw-bold" for="reqDocSwitch">Requires Supporting Document?</label>
                                    <div class="form-text mt-0 ms-4">Require staff to upload proof (e.g. Medical Certificate) when applying.</div>
                                </div>

                                <div class="form-check form-switch mb-0 custom-switch-lg">
                                    <input class="form-check-input" type="checkbox" role="switch" id="isActiveSwitch" name="is_active" {{ old('is_active', $leaveType->is_active ?? true) ? 'checked' : '' }} style="transform: scale(1.2); transform-origin: left center;">
                                    <label class="form-check-label ms-2 fw-bold text-success" for="isActiveSwitch">Active</label>
                                </div>
                            </div>

                            <div class="col-md-12 border-top pt-3 mt-4">
                                <label class="form-label fw-bold">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Additional policies around this leave type">{{ old('description', $leaveType->description ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="{{ route('leave-types.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>{{ isset($leaveType) ? 'Update Configuration' : 'Save Leave Type' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card bg-primary bg-opacity-10 border-0 h-100">
                <div class="card-body">
                    <h5 class="text-primary"><i class="fas fa-info-circle me-2"></i>Notice</h5>
                    <p class="small text-muted mb-2">
                        Updating the <strong>Default Annual Quota</strong> only applies to <em>future</em> staff allocations. Existing staff leave balances will not be automatically updated to reflect this new quota.
                    </p>
                    <p class="small text-muted mb-0">
                        To update balances for current staff, you must adjust them from their respective profile's leave balance tab.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
