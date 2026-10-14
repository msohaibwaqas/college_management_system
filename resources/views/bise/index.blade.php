@extends('layouts.app')

@section('title', 'BISE Management')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">BISE Management</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Add/Edit Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="bise-form" method="POST" action="{{ route('bise.bisestore') }}">
                @csrf
                <input type="hidden" name="bise_id" id="bise_id">
                <div class="mb-3">
                    <input type="text" name="name" id="bise_name" class="form-control" placeholder="BISE Name" required>
                    @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-success" id="form-submit">Add BISE</button>
                <button type="button" class="btn btn-secondary" id="form-cancel" style="display:none;">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('bise.biseindex') }}" class="mb-3 d-flex">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search BISE" class="form-control me-2" />
        <button class="btn btn-primary" type="submit">Search</button>
    </form>

    <!-- BISE Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Short Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bises as $bise)
                <tr>
                    <td>{{ $bise->id }}</td>
                    <td>{{ $bise->formatted_name }}</td>
                    <td>{{ $bise->short_name }}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" 
                            data-id="{{ $bise->id }}" 
                            data-name="{{ $bise->name }}">Edit</button>
                        <form action="{{ route('bise.bisedestroy', $bise) }}" method="POST" class="d-inline-block">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this BISE?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $bises->withQueryString()->links('pagination::bootstrap-5') }}
</div>

<script>
    const form = document.getElementById('bise-form');
    const nameInput = document.getElementById('bise_name');
    const idInput = document.getElementById('bise_id');
    const submitBtn = document.getElementById('form-submit');
    const cancelBtn = document.getElementById('form-cancel');

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;

            nameInput.value = name;
            idInput.value = id;
            form.action = `/bise/${id}`;
            submitBtn.textContent = 'Update BISE';
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
        form.action = "{{ route('bise.bisestore') }}";
        submitBtn.textContent = 'Add BISE';
        cancelBtn.style.display = 'none';
        const methodInput = form.querySelector('input[name="_method"]');
        if(methodInput) methodInput.remove();
    });
</script>
@endsection
