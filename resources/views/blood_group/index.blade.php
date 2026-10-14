@extends('layouts.app')

@section('title', 'Blood Group Management')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Blood Group Management</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Add/Edit Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="bg-form" method="POST" action="{{ route('bloodGroup.store') }}">
                @csrf
                <input type="hidden" name="bg_id" id="bg_id">
                <div class="mb-3">
                    <input type="text" name="code" id="bg_code" class="form-control" placeholder="Blood Group Code" required>
                    @error('code') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-success" id="form-submit">Add Blood Group</button>
                <button type="button" class="btn btn-secondary" id="form-cancel" style="display:none;">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('bloodGroup.index') }}" class="mb-3 d-flex">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search Blood Group" class="form-control me-2" />
        <button class="btn btn-primary" type="submit">Search</button>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Rh Factor</th>
                    <th>Main Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bloodGroups as $bg)
                <tr>
                    <td>{{ $bg->id }}</td>
                    <td>{{ $bg->code }}</td>
                    <td>{{ $bg->rh_factor }}</td>
                    <td>{{ $bg->main_type }}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" 
                            data-id="{{ $bg->id }}" 
                            data-code="{{ $bg->code }}">Edit</button>
                        <form action="{{ route('bloodGroup.destroy', $bg) }}" method="POST" class="d-inline-block">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this Blood Group?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $bloodGroups->withQueryString()->links('pagination::bootstrap-5') }}
</div>

<script>
    const form = document.getElementById('bg-form');
    const codeInput = document.getElementById('bg_code');
    const idInput = document.getElementById('bg_id');
    const submitBtn = document.getElementById('form-submit');
    const cancelBtn = document.getElementById('form-cancel');

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const code = btn.dataset.code;

            codeInput.value = code;
            idInput.value = id;
            form.action = `/blood-group/${id}`;
            submitBtn.textContent = 'Update Blood Group';
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
        codeInput.value = '';
        idInput.value = '';
        form.action = "{{ route('bloodGroup.store') }}";
        submitBtn.textContent = 'Add Blood Group';
        cancelBtn.style.display = 'none';
        const methodInput = form.querySelector('input[name="_method"]');
        if(methodInput) methodInput.remove();
    });
</script>
@endsection
