@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user-times me-2"></i>Students Without Teacher Report</h2>
            <p class="text-muted">View students enrolled in a subject but not assigned to any teacher</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Generate Report</h5>
                </div>
                <div class="card-body">
                    <form id="reportForm" method="POST" action="{{ route('reports.students-without-teacher.show') }}">
                        @csrf
                        
                        <!-- Step 1: Select Admission Year -->
                        <div class="mb-4">
                            <label for="admission_year" class="form-label fw-bold">
                                <i class="fas fa-calendar me-2"></i>Step 1: Select Admission Year
                            </label>
                            <select name="admission_year" id="admission_year" class="form-select form-select-lg" required>
                                <option value="">-- Select Admission Year --</option>
                                @foreach($admissionYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Step 2: Select Subject (Optional) -->
                        <div class="mb-4">
                            <label for="subject_id" class="form-label fw-bold">
                                <i class="fas fa-book me-2"></i>Step 2: Filter by Subject (Optional)
                            </label>
                            <select name="subject_id" id="subject_id" class="form-select form-select-lg">
                                <option value="all">-- All Subjects (Show All Students With Missing Teachers) --</option>
                            </select>
                            <div id="subjectLoading" class="text-center mt-2" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-warning" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2">Loading subjects...</span>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
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
        $('#admission_year').change(function() {
            const year = $(this).val();
            const subjectSelect = $('#subject_id');
            const loading = $('#subjectLoading');

            if (year) {
                loading.show();

                $.ajax({
                    url: '{{ route("reports.subject-enrollment.get-subjects") }}',
                    method: 'GET',
                    data: { admission_year: year },
                    success: function(subjects) {
                        loading.hide();
                        subjectSelect.html('<option value="all">-- All Subjects (Show All Students With Missing Teachers) --</option>');
                        
                        if (subjects.length > 0) {
                            let compulsoryGroup = '<optgroup label="Compulsory Subjects">';
                            let electiveGroup = '<optgroup label="Elective Subjects">';
                            let hasCompulsory = false;
                            let hasElective = false;

                            subjects.forEach(function(subject) {
                                const option = `<option value="${subject.id}">${subject.name} (${subject.code})</option>`;
                                if (subject.type === 'Compulsory') {
                                    compulsoryGroup += option;
                                    hasCompulsory = true;
                                } else {
                                    electiveGroup += option;
                                    hasElective = true;
                                }
                            });

                            compulsoryGroup += '</optgroup>';
                            electiveGroup += '</optgroup>';

                            let html = '';
                            if (hasCompulsory) html += compulsoryGroup;
                            if (hasElective) html += electiveGroup;

                            subjectSelect.append(html);
                        }
                    },
                    error: function() {
                        loading.hide();
                    }
                });
            } else {
                subjectSelect.html('<option value="all">-- All Subjects (Show All Students With Missing Teachers) --</option>');
            }
        });
    });
</script>
@endsection
