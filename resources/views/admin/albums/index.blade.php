@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Photo Albums</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('albums.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create New Album
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
                            <th>Title</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Photos</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($albums as $album)
                        <tr>
                            <td>{{ $album->id }}</td>
                            <td>{{ $album->title }}</td>
                            <td>{{ \Carbon\Carbon::parse($album->date)->format('d M, Y') }}</td>
                            <td>
                                @if($album->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $album->photos->count() }}</td>
                            <td>
                                <a href="{{ route('albums.show', $album->id) }}" class="btn btn-info btn-sm" title="Manage Photos">
                                    <i class="fas fa-images"></i>
                                </a>
                                <a href="{{ route('albums.edit', $album->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('albums.destroy', $album->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this album?');">
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
                            <td colspan="6" class="text-center">No albums found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $albums->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
