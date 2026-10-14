@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">News & Updates</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('updates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create New Update
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($updates as $update)
                        <tr>
                            <td>{{ $update->id }}</td>
                            <td>
                                @if($update->image)
                                    <img src="{{ $update->getImageUrl() }}" alt="Thumbnail" style="height: 50px; width: 50px; object-fit: cover;">
                                @else
                                    <span class="text-muted">No Image</span>
                                @endif
                            </td>
                            <td>{{ $update->title }}</td>
                            <td>
                                @if($update->type == 1)
                                    <span class="badge bg-primary">News</span>
                                @elseif($update->type == 2)
                                    <span class="badge bg-info">Event</span>
                                @else
                                    <span class="badge bg-warning text-dark">Announcement</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($update->date)->format('d M, Y') }}</td>
                            <td>
                                @if($update->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('updates.edit', $update->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('updates.destroy', $update->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this update?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No updates found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $updates->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
