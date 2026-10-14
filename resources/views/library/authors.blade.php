@extends('layouts.app')
@section('title', 'Authors')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item active">Authors</li></ol></nav>

    <div class="row">
        {{-- Add Author --}}
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-info text-white"><h6 class="m-0 font-weight-bold">Add Author</h6></div>
                <div class="card-body">
                    <form action="{{ route('library.authors.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Allama Iqbal">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name (Urdu)</label>
                            <input type="text" name="name_urdu" class="form-control" dir="rtl" placeholder="اردو نام">
                        </div>
                        <button class="btn btn-info text-white w-100"><i class="fas fa-plus me-1"></i> Add Author</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Authors List --}}
        <div class="col-md-8">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Authors ({{ $authors->total() }})</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light"><tr><th>#</th><th>Name</th><th>Name (Urdu)</th><th>Books</th><th>Action</th></tr></thead>
                            <tbody>
                                @forelse($authors as $a)
                                <tr>
                                    <td>{{ $loop->iteration + ($authors->currentPage()-1) * $authors->perPage() }}</td>
                                    <td><strong>{{ $a->name }}</strong></td>
                                    <td>{{ $a->name_urdu ?? '—' }}</td>
                                    <td><span class="badge bg-primary">{{ $a->books_count }}</span></td>
                                    <td>
                                        <form action="{{ route('library.authors.destroy', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete author?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty<tr><td colspan="5" class="text-center">No authors yet.</td></tr>@endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $authors->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
