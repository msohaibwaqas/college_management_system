@extends('layouts.app')
@section('title', 'Book Locations')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item active">Locations</li></ol></nav>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-dark text-white"><h6 class="m-0 font-weight-bold">Add Location</h6></div>
                <div class="card-body">
                    <form action="{{ route('library.locations.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Location Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Shelf A-3, Room 12">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Optional description">
                        </div>
                        <button class="btn btn-dark w-100"><i class="fas fa-plus me-1"></i> Add Location</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Locations ({{ $locations->total() }})</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light"><tr><th>#</th><th>Name</th><th>Description</th><th>Books</th><th>Action</th></tr></thead>
                            <tbody>
                                @forelse($locations as $l)
                                <tr>
                                    <td>{{ $loop->iteration + ($locations->currentPage()-1) * $locations->perPage() }}</td>
                                    <td><strong>{{ $l->name }}</strong></td>
                                    <td>{{ $l->description ?? '—' }}</td>
                                    <td><span class="badge bg-primary">{{ $l->books_count }}</span></td>
                                    <td>
                                        <form action="{{ route('library.locations.destroy', $l) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete location?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty<tr><td colspan="5" class="text-center">No locations yet.</td></tr>@endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $locations->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
