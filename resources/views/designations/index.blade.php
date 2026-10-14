@extends('layouts.app')

@section('title', 'Designation Management')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Designation Management</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Add/Edit Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="designation-form" method="POST" action="{{ route('designations.store') }}">
                @csrf
                <input type="hidden" name="designation_id" id="designation_id">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="designation_name">Designation Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="designation_name" class="form-control" placeholder="e.g., Lecturer" required>
                        @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="designation_bps">BPS (Basic Pay Scale) <span class="text-danger">*</span></label>
                        <input type="number" name="bps" id="designation_bps" class="form-control" min="1" max="22" placeholder="e.g., 17" required>
                        @error('bps') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-success" id="form-submit">Add Designation</button>
                <button type="button" class="btn btn-secondary" id="form-cancel" style="display:none;">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('designations.index') }}" class="mb-3 d-flex">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search designations or BPS" class="form-control me-2" />
        <button class="btn btn-primary" type="submit">Search</button>
    </form>

    <!-- Designations Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Designation Name</th>
                    <th>BPS</th>
                    <th>Total Staff</th>
                    <th>Active</th>
                    <th>Left</th>
                    <th>Transferred</th>
                    <th>Retired</th>
                    <th>Suspended</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($designations as $designation)
                <tr>
                    <td>{{ $designation->id }}</td>
                    <td><strong>{{ $designation->name }}</strong></td>
                    <td><span class="badge bg-primary">BPS-{{ $designation->bps }}</span></td>
                    <td><span class="badge bg-secondary">{{ $designation->total_staff_count }}</span></td>
                    <td>
                        @if($designation->active_staff_count > 0)
                            <span class="badge bg-success">{{ $designation->active_staff_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        @if($designation->left_staff_count > 0)
                            <span class="badge bg-warning text-dark">{{ $designation->left_staff_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        @if($designation->transferred_staff_count > 0)
                            <span class="badge bg-info">{{ $designation->transferred_staff_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        @if($designation->retired_staff_count > 0)
                            <span class="badge bg-dark">{{ $designation->retired_staff_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        @if($designation->suspended_staff_count > 0)
                            <span class="badge bg-danger">{{ $designation->suspended_staff_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" 
                            data-id="{{ $designation->id }}" 
                            data-name="{{ $designation->name }}"
                            data-bps="{{ $designation->bps }}">Edit</button>
                        <form action="{{ route('designations.destroy', $designation) }}" method="POST" class="d-inline-block">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this designation?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">No designations found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $designations->withQueryString()->links('pagination::bootstrap-5') }}
</div>

<script>
    const form = document.getElementById('designation-form');
    const nameInput = document.getElementById('designation_name');
    const bpsInput = document.getElementById('designation_bps');
    const idInput = document.getElementById('designation_id');
    const submitBtn = document.getElementById('form-submit');
    const cancelBtn = document.getElementById('form-cancel');

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const bps = btn.dataset.bps;

            nameInput.value = name;
            bpsInput.value = bps;
            idInput.value = id;
            form.action = `/designations/${id}`;
            submitBtn.textContent = 'Update Designation';
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
        bpsInput.value = '';
        idInput.value = '';
        form.action = "{{ route('designations.store') }}";
        submitBtn.textContent = 'Add Designation';
        cancelBtn.style.display = 'none';
        const methodInput = form.querySelector('input[name="_method"]');
        if(methodInput) methodInput.remove();
    });
</script>
@endsection
