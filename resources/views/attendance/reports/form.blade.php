@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center"><span class="badge bg-primary-subtle text-primary me-2 p-2 rounded-circle"><i class="fas fa-calendar-alt fa-fw"></i></span><h4 class="mb-0 fw-bold text-dark">Generate HSSC Attendance Report</h4></div>
                <div class="card-body p-4">
                    <form action="{{ route('attendance.reports.generate') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Admission Year <span class="text-danger">*</span></label>
                                <select name="admission_year" id="admission_year" class="form-control select2" required>
                                    <option value="">-- Select Year --</option>
                                    @foreach($admissionYears as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Class <span class="text-danger">*</span></label>
                                <select name="current_class" id="current_class" class="form-control select2" required>
                                    <option value="">-- Select Class --</option>
                                    <option value="11">Class 11 (1st Year)</option>
                                    <option value="12">Class 12 (2nd Year)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                                <select name="subject_id" id="subject_id" class="form-control select2" required>
                                    <option value="">-- Select Subject --</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-01') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-bold">
                                <i class="fas fa-file-invoice me-2"></i>Generate Attendance Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5'
    });
});
</script>
@endsection
