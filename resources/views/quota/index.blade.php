@extends('layouts.app')

@section('title', 'Quota Management')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Quota Management</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Add/Edit Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="quota-form" method="POST" action="{{ route('quota.store') }}">
                @csrf
                <input type="hidden" name="quota_id" id="quota_id">
                <div class="mb-3">
                    <input type="text" name="name" id="quota_name" class="form-control" placeholder="Quota Name" required>
                    @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-success" id="form-submit">Add Quota</button>
                <button type="button" class="btn btn-secondary" id="form-cancel" style="display:none;">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('quota.index') }}" class="mb-3 d-flex">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search Quota" class="form-control me-2" />
        <button class="btn btn-primary" type="submit">Search</button>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Formatted Name</th>
                    <th>Special Quota?</th>
                    <th>Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotas as $quota)
                <tr>
                    <td>{{ $quota->id }}</td>
                    <td>{{ $quota->name }}</td>
                    <td>{{ $quota->formatted_name }}</td>
                    <td>{{ $quota->is_special_quota ? 'Yes' : 'No' }}</td>
                    <td>{{ $quota->quota_code }}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" 
                            data-id="{{ $quota->id }}" 
                            data-name="{{ $quota->name }}">Edit</button>
                        <form action="{{ route('quota.destroy', $quota) }}" method="POST" class="d-inline-block">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this Quota?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $quotas->withQueryString()->links('pagination::bootstrap-5') }}
</div>

<script>
    const form = document.getElementById('quota-form');
    const nameInput = document.getElementById('quota_name');
    const idInput = document.getElementById('quota_id');
    const submitBtn = document.getElementById('form-submit');
    const cancelBtn = document.getElementById('form-cancel');

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;

            nameInput.value = name;
            idInput.value = id;
            form.action = `/quota/${id}`;
            submitBtn.textContent = 'Update Quota';
            cancelBtn.style.display = 'inline-block';

            if(!form.querySelector('input[name="_method"]')) {
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                form.appendChild(methodInput);
            }
        });
    });

    cancelBtn.addEventListener('click', () => {
        nameInput.value = '';
        idInput.value = '';
        form.action = "{{ route('quota.store') }}";
        submitBtn.textContent = 'Add Quota';
        cancelBtn.style.display = 'none';
        const methodInput = form.querySelector('input[name="_method"]');
        if(methodInput) methodInput.remove();
    });
</script>
@endsection
