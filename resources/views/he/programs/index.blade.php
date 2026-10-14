@extends('layouts.app')
@section('title', 'Programs List')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-university me-2"></i>Programs List</h2>
            <a href="{{ route('programs.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Add Program
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Total Semesters</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            <tr>
                                <td><strong>{{ $program->id }}</strong></td>
                                <td><strong>{{ $program->name }}</strong></td>
                                <td><span class="badge bg-info text-dark">{{ $program->type }}</span></td>
                                <td>{{ $program->total_semesters }}</td>
                                <td class="text-center">
                                    <a href="{{ route('programs.edit', $program) }}" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No programs found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
