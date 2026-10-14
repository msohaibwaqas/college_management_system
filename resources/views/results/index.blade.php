@extends('layouts.app')
@section('title', 'Exam Results')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-poll me-2"></i>Exam Results Management</h2>
        <h2><i class="fas fa-poll me-2"></i>Exam Results Management</h2>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Results</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('results.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Admission Year</label>
                    <select name="academic_year" class="form-select">
                        <option value="">All Years</option>
                        @foreach($admissionYears as $year)
                            <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class Level</label>
                    <select name="class_level" class="form-select">
                        <option value="">All Classes</option>
                        <option value="11" {{ request('class_level') == '11' ? 'selected' : '' }}>11th</option>
                        <option value="12" {{ request('class_level') == '12' ? 'selected' : '' }}>12th</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-select">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Exam Type</label>
                    <select name="exam_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="Oct_test" {{ request('exam_type') == 'Oct_test' ? 'selected' : '' }}>October Test</option>
                        <option value="Dec_test" {{ request('exam_type') == 'Dec_test' ? 'selected' : '' }}>December Test</option>
                        <option value="Send_up" {{ request('exam_type') == 'Send_up' ? 'selected' : '' }}>Send Up</option>
                        <option value="Final" {{ request('exam_type') == 'Final' ? 'selected' : '' }}>Final</option>
                        <option value="Class_test" {{ request('exam_type') == 'Class_test' ? 'selected' : '' }}>Class Test</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Year</th>
                            <th>Class</th>
                            <th>Exam Name</th>
                            <th>Subject</th>
                            <th>Total Marks</th>
                            <th>Students</th>
                            <th>Avg Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tests as $test)
                            <tr>
                                <td>{{ $test->academic_year }}</td>
                                <td><span class="badge bg-info">{{ $test->class_level }}th</span></td>
                                <td>
                                    <strong>{{ $test->exam_name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ str_replace('_', ' ', $test->exam_type) }}</small>
                                </td>
                                <td>{{ $test->subject->name }}</td>
                                <td>{{ $test->total_marks }}</td>
                                <td>{{ $test->student_count }}</td>
                                <td>
                                    {{ number_format($test->average_marks, 1) }}
                                    <div class="progress" style="height: 5px; width: 100px;">
                                        @php $percent = ($test->total_marks > 0) ? ($test->average_marks / $test->total_marks) * 100 : 0; @endphp
                                        <div class="progress-bar {{ $percent >= 50 ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ $percent }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No results found. Click "Add New Result" to create one.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $tests->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete all marks for this test? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    let deleteData = {};

    $('.delete-test-btn').click(function() {
        deleteData = {
            academic_year: $(this).data('year'),
            class_level: $(this).data('class'),
            subject_id: $(this).data('subject'),
            exam_type: $(this).data('type'),
            exam_name: $(this).data('name'),
            _token: "{{ csrf_token() }}"
        };
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').click(function() {
        $.ajax({
            url: "{{ route('results.destroy') }}",
            type: 'DELETE',
            data: deleteData,
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Error deleting records');
                }
            },
            error: function() {
                alert('Error deleting records');
            }
        });
    });
});
</script>
@endsection
@endsection
