@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark py-3">
                    <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Generate HE Attendance Report</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('he.reports.attendance.generate') }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Program <span class="text-danger">*</span></label>
                                <select name="program_id" id="program_id" class="form-control select2" required>
                                    <option value="">-- Select Program --</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Semester <span class="text-danger">*</span></label>
                                <select name="semester_no" id="semester_no" class="form-control select2" required>
                                    <option value="">-- Select Semester --</option>
                                    @for($i=1; $i<=8; $i++)
                                        <option value="{{ $i }}">Semester {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                                <select name="bs_subject_id" id="bs_subject_id" class="form-control select2" required disabled>
                                    <option value="">-- Select Program & Semester First --</option>
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
                            <button type="submit" class="btn btn-warning btn-lg fw-bold">
                                <i class="fas fa-chart-bar me-2"></i>Generate Attendance Report
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

    function loadSubjects() {
        let programId = $('#program_id').val();
        let semesterNo = $('#semester_no').val();
        let subjectSelect = $('#bs_subject_id');

        if(programId && semesterNo) {
            subjectSelect.prop('disabled', true).html('<option value="">Loading...</option>');
            
            $.get("{{ route('he.get_subjects') }}", {
                program_id: programId,
                semester_no: semesterNo
            }, function(data) {
                let html = '<option value="">-- Select Subject --</option>';
                data.forEach(function(subject) {
                    html += `<option value="${subject.id}">${subject.name} (${subject.subject_code})</option>`;
                });
                subjectSelect.html(html).prop('disabled', false);
            });
        } else {
            subjectSelect.prop('disabled', true).html('<option value="">-- Select Program & Semester First --</option>');
        }
    }

    $('#program_id, #semester_no').on('change', loadSubjects);
});
</script>
@endsection
@endsection
