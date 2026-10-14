@extends('layouts.app')
@section('title', 'Manage Date Sheet')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-alt me-2"></i>Test Date Sheet</h2>
        <div>
            <a href="{{ route('datesheets.summary') }}" class="btn btn-info text-white me-2">
                <i class="fas fa-list-alt me-2"></i>Summary View
            </a>
            <a href="{{ route('datesheets.generate') }}" class="btn btn-success me-2">
                <i class="fas fa-magic me-2"></i>Auto Generate
            </a>
            <a href="{{ route('datesheets.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Schedule New Test
            </a>
            <button type="button" id="bulkDeleteBtn" class="btn btn-danger ms-2" style="display: none;" onclick="confirmBulkDelete()">
                <i class="fas fa-trash me-2"></i>Delete Selected
            </button>
        </div>
    </div>

    <form id="bulkDeleteForm" action="{{ route('datesheets.bulk_destroy') }}" method="POST" style="display: none;">
        @csrf
    </form>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="mb-0">Scheduled Tests</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Admission Year</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datesheets as $datesheet)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $datesheet->id }}" class="form-check-input select-item">
                                </td>
                                <td>{{ $datesheet->test_date->format('d M, Y') }}</td>
                                <td>{{ $datesheet->test_date->format('l') }}</td>
                                <td>{{ $datesheet->admission_year }}</td>
                                <td>{{ $datesheet->subject->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $datesheet->subject->type === 'Compulsory' ? 'primary' : 'info' }}">
                                        {{ $datesheet->subject->type }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('datesheets.destroy', $datesheet) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this test?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No tests scheduled yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.select-item');
        checkboxes.forEach(cb => cb.checked = this.checked);
        toggleBulkDeleteBtn();
    });

    document.querySelectorAll('.select-item').forEach(cb => {
        cb.addEventListener('change', toggleBulkDeleteBtn);
    });

    function toggleBulkDeleteBtn() {
        const checkedCount = document.querySelectorAll('.select-item:checked').length;
        document.getElementById('bulkDeleteBtn').style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    function confirmBulkDelete() {
        if (confirm('Are you sure you want to delete the selected tests?')) {
            const form = document.getElementById('bulkDeleteForm');
            const checkedItems = document.querySelectorAll('.select-item:checked');
            
            // Clear existing inputs
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            
            checkedItems.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = item.value;
                form.appendChild(input);
            });
            
            form.submit();
        }
    }
</script>
@endsection
@endsection
