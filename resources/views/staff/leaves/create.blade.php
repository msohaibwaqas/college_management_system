@extends('layouts.app')

@section('title', 'Apply for Leave')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-paper-plane me-2"></i>Apply for Leave</h1>
        <p class="page-subtitle">Submit a leave request for approval by your Department Head.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Please check your inputs:
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
                    <form action="{{ route('my-leaves.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Leave Type <span class="text-danger">*</span></label>
                                <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required id="leaveTypeSelect">
                                    <option value="">-- Select Category --</option>
                                    @foreach($leaveTypes as $type)
                                        <option value="{{ $type->id }}" data-doc="{{ $type->requires_document ? '1' : '0' }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }} ({{ $type->is_paid ? 'Paid' : 'Unpaid' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">From Date <span class="text-danger">*</span></label>
                                <input type="date" name="from_date" class="form-control" value="{{ old('from_date', date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">To Date <span class="text-danger">*</span></label>
                                <input type="date" name="to_date" class="form-control" value="{{ old('to_date', date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch bg-light p-3 border rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="is_half_day" id="halfDaySwitch" {{ old('is_half_day') ? 'checked' : '' }} style="transform: scale(1.2);">
                                    <label class="form-check-label fw-bold" for="halfDaySwitch">This is a Half-Day Leave</label>
                                    <p class="small text-muted ms-4 mb-0 mt-1">Standard office hours will apply for the remainder of the day.</p>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch bg-warning bg-opacity-10 p-3 border border-warning rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="is_special_case" id="specialCaseSwitch" value="1" {{ old('is_special_case') ? 'checked' : '' }} style="transform: scale(1.2);">
                                    <label class="form-check-label fw-bold text-dark" for="specialCaseSwitch"><i class="fas fa-star text-warning me-1"></i>Is Special Case Exception?</label>
                                    <p class="small text-muted ms-4 mb-0 mt-1">Check this if your request exceeds the normal monthly limit (allows up to special case limit of 7 days/month with justification).</p>
                                </div>
                            </div>

                            <div class="col-md-12" id="specialCaseReasonRow" style="display: {{ old('is_special_case') ? 'block' : 'none' }};">
                                <label class="form-label fw-bold text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Special Case Justification <span class="text-danger">*</span></label>
                                <textarea name="special_case_reason" rows="2" class="form-control border-warning" placeholder="Provide valid emergency or special reason for exceeding monthly leave limit...">{{ old('special_case_reason') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Reason Statement <span class="text-danger">*</span></label>
                                <textarea name="reason" rows="4" class="form-control" required minlength="10" placeholder="Please elaborate on your request...">{{ old('reason') }}</textarea>
                            </div>

                            <div class="col-md-12" id="documentUploadRow" style="display: none;">
                                <label class="form-label fw-bold text-info"><i class="fas fa-paperclip me-1"></i>Supporting Document Required</label>
                                <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text">This leave type (e.g. Medical) mandate a certificate. Max 2MB (PDF/JPG/PNG).</div>
                            </div>
                        </div>

                        <div class="mt-4 text-end border-top pt-3">
                            <a href="{{ route('my-leaves.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 bg-primary bg-opacity-10 py-2">
                <div class="card-body">
                    <h5 class="text-primary"><i class="fas fa-info-circle me-2"></i>Workflow Policy</h5>
                    <ul class="text-muted ps-3 small mt-3">
                        <li class="mb-2">Leaves must be submitted at least 24 hours prior unless it's an emergency/medical.</li>
                        <li class="mb-2">Your application will first be sent to your <strong>Department Head</strong>.</li>
                        <li class="mb-2">If approved by HOD, it goes to the <strong>Principal</strong> for final processing.</li>
                        <li class="mb-2">Half-day leaves only deduct 0.5 days from your annual quota.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const leaveSelect = document.getElementById('leaveTypeSelect');
        const docRow = document.getElementById('documentUploadRow');
        const specialSwitch = document.getElementById('specialCaseSwitch');
        const specialReasonRow = document.getElementById('specialCaseReasonRow');
        
        function checkDocRequirement() {
            const selected = leaveSelect.options[leaveSelect.selectedIndex];
            if(selected && selected.getAttribute('data-doc') === '1') {
                docRow.style.display = 'block';
            } else {
                docRow.style.display = 'none';
            }
        }
        
        if (specialSwitch && specialReasonRow) {
            specialSwitch.addEventListener('change', function() {
                specialReasonRow.style.display = this.checked ? 'block' : 'none';
            });
        }

        leaveSelect.addEventListener('change', checkDocRequirement);
        checkDocRequirement(); // Run on init for validation redirects
    });
</script>
@endsection
