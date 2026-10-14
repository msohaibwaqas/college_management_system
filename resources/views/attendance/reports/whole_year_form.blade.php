@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Admin Consolidated Whole-Year Attendance Report</h4>
                    <span class="badge bg-light text-primary fw-bold">Admin Only</span>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('attendance.reports.whole_year.generate') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Admission Year <span class="text-danger">*</span></label>
                                <select name="admission_year" id="admission_year" class="form-control select2" required>
                                    <option value="">-- Select Admission Year --</option>
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
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-01-01') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> This report generates a multi-subject matrix showing <strong>Attended / Delivered</strong> lecture counts and percentage for all subjects enrolled by students in the selected class and date range.
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                <i class="fas fa-table me-2"></i>Generate Whole-Year Attendance Matrix
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
