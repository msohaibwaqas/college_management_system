@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-table me-2"></i>Generate HE Tabulation Sheet</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('he.reports.tabulation_sheet.generate') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Program <span class="text-danger">*</span></label>
                                <select name="program_id" class="form-control select2" required>
                                    <option value="">-- Select Program --</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Admission Year <span class="text-danger">*</span></label>
                                <select name="admission_year" class="form-control select2" required>
                                    <option value="">-- Select Year --</option>
                                    @foreach($admissionYears as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Semester <span class="text-danger">*</span></label>
                                <select name="semester_no" class="form-control select2" required>
                                    <option value="">-- Select Semester --</option>
                                    @for($i=1; $i<=8; $i++)
                                        <option value="{{ $i }}">Semester {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Exam Type <span class="text-danger">*</span></label>
                                <select name="exam_type" class="form-control select2" required>
                                    <option value="Mid">Mid Term</option>
                                    <option value="Final">Final Term</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-file-excel me-2"></i>Generate Tabulation Sheet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5'
    });
});
</script>
@endsection
@endsection
