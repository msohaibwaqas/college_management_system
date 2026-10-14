@extends('layouts.app')
@section('title', 'Genres')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item active">Genres</li></ol></nav>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-secondary text-white"><h6 class="m-0 font-weight-bold">Add Genre</h6></div>
                <div class="card-body">
                    <form action="{{ route('library.genres.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Genre Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Science, Literature">
                        </div>
                        <button class="btn btn-secondary w-100"><i class="fas fa-plus me-1"></i> Add Genre</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Genres ({{ $genres->total() }})</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light"><tr><th>#</th><th>Name</th><th>Books</th><th>Action</th></tr></thead>
                            <tbody>
                                @forelse($genres as $g)
                                <tr>
                                    <td>{{ $loop->iteration + ($genres->currentPage()-1) * $genres->perPage() }}</td>
                                    <td><strong>{{ $g->name }}</strong></td>
                                    <td><span class="badge bg-primary">{{ $g->books_count }}</span></td>
                                    <td>
                                        <form action="{{ route('library.genres.destroy', $g) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete genre?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty<tr><td colspan="4" class="text-center">No genres yet.</td></tr>@endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $genres->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
