@extends('layouts.app')
@section('title', 'Subject Groups')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Subject Groups (HSSC)</h1>
        <a href="{{ route('subject-groups.create') }}" class="btn btn-primary">Add New Group</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('subject-groups.index') }}" method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Search groups..." value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Group Name</th>
                        <th>Subjects Count</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td>{{ $loop->iteration + ($groups->currentPage() - 1) * $groups->perPage() }}</td>
                            <td><strong>{{ $group->name }}</strong></td>
                            <td><span class="badge bg-info">{{ $group->subjects_count }} Subjects</span></td>
                            <td>{{ Str::limit($group->description, 50) }}</td>
                            <td class="text-end">
                                <a href="{{ route('subject-groups.show', $group) }}" class="btn btn-sm btn-outline-info">View</a>
                                <a href="{{ route('subject-groups.edit', $group) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                <form action="{{ route('subject-groups.destroy', $group) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this group?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No subject groups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($groups->hasPages())
            <div class="card-footer">
                {{ $groups->appends(['search' => $search])->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
