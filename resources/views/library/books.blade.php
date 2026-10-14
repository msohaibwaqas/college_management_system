@extends('layouts.app')
@section('title', 'Book Records')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Book Records</h1>
        <a href="{{ route('library.books.create') }}" class="btn btn-primary btn-sm shadow-sm"><i class="fas fa-plus fa-sm mr-1"></i> Add Book</a>
    </div>

    {{-- Search & Filter --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('library.books') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by title, author, ISBN, Ref#, Acc#..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="genre_id" class="form-select">
                        <option value="">All Genres</option>
                        @foreach($genres as $g)
                            <option value="{{ $g->id }}" {{ request('genre_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="author_id" class="form-select">
                        <option value="">All Authors</option>
                        @foreach($authors as $a)
                            <option value="{{ $a->id }}" {{ request('author_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="language" class="form-select">
                        <option value="">All Languages</option>
                        <option value="English" {{ request('language') == 'English' ? 'selected' : '' }}>English</option>
                        <option value="Urdu" {{ request('language') == 'Urdu' ? 'selected' : '' }}>Urdu</option>
                        <option value="Arabic" {{ request('language') == 'Arabic' ? 'selected' : '' }}>Arabic</option>
                        <option value="Other" {{ request('language') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Books Table --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Genre</th>
                            <th>Language</th>
                            <th>Ref#</th>
                            <th>ISBN</th>
                            <th>Copies</th>
                            <th>Available</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($books as $book)
                        <tr>
                            <td>{{ $loop->iteration + ($books->currentPage() - 1) * $books->perPage() }}</td>
                            <td>
                                <strong>{{ $book->title }}</strong>
                                @if($book->title_urdu)<br><small class="text-muted">{{ $book->title_urdu }}</small>@endif
                            </td>
                            <td>{{ $book->author->name ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $book->genre->name ?? '—' }}</span></td>
                            <td>{{ $book->language }}</td>
                            <td>{{ $book->ref_no ?? '—' }}</td>
                            <td><small>{{ $book->isbn ?? '—' }}</small></td>
                            <td>{{ $book->volumes }}</td>
                            <td>
                                @php $avail = $book->volumes - ($book->active_issues_count ?? 0); @endphp
                                <span class="badge bg-{{ $avail > 0 ? 'success' : 'danger' }}">{{ $avail }}</span>
                            </td>
                            <td>{{ $book->location->name ?? '—' }}</td>
                            <td>
                                <a href="{{ route('library.books.edit', $book) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('library.books.destroy', $book) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this book?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center py-4">No books found. <a href="{{ route('library.books.create') }}">Add your first book</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">{{ $books->links() }}</div>
        </div>
    </div>
</div>
@endsection
