@extends('layouts.app')
@section('title', 'Import / Export Books')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item active">Import / Export</li></ol></nav>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row">
        {{-- Export --}}
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white"><h6 class="m-0 font-weight-bold"><i class="fas fa-download me-2"></i>Export Books</h6></div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-csv fa-3x text-primary mb-3"></i>
                    <p>Download all books as a CSV file. The export includes all book details, author names, genres, and locations.</p>
                    <a href="{{ route('library.export_books') }}" class="btn btn-primary btn-lg"><i class="fas fa-download me-2"></i> Export CSV</a>
                </div>
            </div>
        </div>

        {{-- Import --}}
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-success text-white"><h6 class="m-0 font-weight-bold"><i class="fas fa-upload me-2"></i>Import Books</h6></div>
                <div class="card-body">
                    <form action="{{ route('library.import_books') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">CSV File <span class="text-danger">*</span></label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        </div>
                        <div class="alert alert-info small">
                            <strong>CSV Format:</strong> The file should have a header row with columns in this exact order:<br>
                            <code>Title, Title (Urdu), Ref#, Language, Author, Book Type, Genre, ISBN, Pages, Price, Acc#, Location, Volumes/Copies, Publication Year, Remarks</code>
                            <br><br>
                            <strong>Tip:</strong> First export existing books to see the exact format, then modify the exported file and re-import.
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-upload me-2"></i> Import Books</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
