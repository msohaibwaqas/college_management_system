@extends('layouts.app')

@section('title', 'Bulk BS/ADP Fee Vouchers')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-university me-2"></i>Generate Bulk BS/ADP Semester Fee Vouchers</h4>
        <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Vouchers
        </a>
    </div>
    <div class="card-body">
        @if($studentsData->isEmpty())
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-check-circle fa-3x mb-3 d-block"></i>
                <h5>All BS/ADP students already have fee vouchers for current semester!</h5>
                <p class="mb-0">There are no eligible Higher Education students without existing semester fee vouchers.</p>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                <strong>{{ $studentsData->count() }}</strong> BS/ADP student(s) found without a semester fee voucher. Select the students below and click "Generate Vouchers".
            </div>

            <form action="{{ route('vouchers.bulk-he.store') }}" method="POST" id="bulkForm">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>#</th>
                                <th>Student</th>
                                <th>Father's Name</th>
                                <th>Program & Semester</th>
                                <th class="text-end">Estimated Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studentsData as $i => $data)
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="{{ $data->student->id }}" class="student-checkbox"></td>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('students.show', $data->student) }}">{{ $data->student->full_name }}</a>
                                </td>
                                <td>{{ $data->student->father?->name ?? '' }}</td>
                                <td><span class="badge bg-primary">{{ $data->program }}</span> <span class="badge bg-secondary">Sem {{ $data->semester }}</span></td>
                                <td class="text-end"><strong>Rs. {{ number_format($data->estimated_fee) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span id="selectedCount">0</span> student(s) selected
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" id="generateBtn" disabled>
                        <i class="fas fa-file-invoice-dollar me-1"></i>Generate BS/ADP Vouchers
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const generateBtn = document.getElementById('generateBtn');

    function updateCount() {
        const checked = document.querySelectorAll('.student-checkbox:checked').length;
        if (selectedCount) selectedCount.textContent = checked;
        if (generateBtn) generateBtn.disabled = checked === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateCount();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (selectAll) selectAll.checked = document.querySelectorAll('.student-checkbox:checked').length === checkboxes.length;
            updateCount();
        });
    });
});
</script>
@endsection
