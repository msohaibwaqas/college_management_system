@extends('layouts.app')
@section('title', 'Seating Plans')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chair me-2"></i>Seating Plans</h2>
        <div>
            <a href="{{ route('seating-plans.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Generate New Plan
            </a>
            <button type="button" id="bulkDeleteBtn" class="btn btn-danger ms-2" style="display: none;" onclick="confirmBulkDelete()">
                <i class="fas fa-trash me-2"></i> Delete Selected
            </button>
        </div>
    </div>

    <form id="bulkDeleteForm" action="{{ route('seating-plans.bulk_destroy') }}" method="POST" style="display: none;">
        @csrf
    </form>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Test Date</th>
                            <th>Total Students</th>
                            <th>Rooms Used</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $plan->id }}" class="form-check-input select-item">
                                </td>
                                <td class="fw-bold">{{ $plan->test_date->format('d M Y') }}</td>
                                <td>{{ $plan->details->count() }}</td>
                                <td>{{ $plan->details->pluck('room_id')->unique()->count() }}</td>
                                <td>{{ $plan->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('seating-plans.show', $plan) }}" class="btn btn-sm btn-info text-white" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form action="{{ route('seating-plans.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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
                                    No seating plans found.
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
    document.getElementById('selectAll')?.addEventListener('change', function() {
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
        if (confirm('Are you sure you want to delete the selected seating plans?')) {
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
