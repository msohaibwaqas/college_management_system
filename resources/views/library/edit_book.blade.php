@extends('layouts.app')
@section('title', 'Edit Book')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item"><a href="{{ route('library.books') }}">Books</a></li><li class="breadcrumb-item active">Edit</li></ol></nav>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white"><h6 class="m-0 font-weight-bold">Edit Book: {{ $book->title }}</h6></div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('library.books.update', $book) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">Book Title (English) <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ $book->title }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">Book Title (Urdu)</label>
                        <input type="text" name="title_urdu" class="form-control" value="{{ $book->title_urdu }}" dir="rtl">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Book Ref#</label>
                        <input type="text" name="ref_no" class="form-control" value="{{ $book->ref_no }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Language <span class="text-danger">*</span></label>
                        <select name="language" class="form-select" required>
                            @foreach(['English','Urdu','Arabic','Other'] as $lang)
                                <option value="{{ $lang }}" {{ $book->language == $lang ? 'selected' : '' }}>{{ $lang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Author</label>
                        <select name="author_id" class="form-select">
                            <option value="">Select Author...</option>
                            @foreach($authors as $a)<option value="{{ $a->id }}" {{ $book->author_id == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Book Type</label>
                        <input type="text" name="book_type" class="form-control" value="{{ $book->book_type }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Genre</label>
                        <select name="genre_id" class="form-select">
                            <option value="">Select Genre...</option>
                            @foreach($genres as $g)<option value="{{ $g->id }}" {{ $book->genre_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">ISBN#</label>
                        <input type="text" name="isbn" class="form-control" value="{{ $book->isbn }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Pages</label>
                        <input type="number" name="pages" class="form-control" value="{{ $book->pages }}" min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Price (PKR)</label>
                        <input type="number" name="price" class="form-control" value="{{ $book->price }}" min="0" step="0.01">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Acc#</label>
                        <input type="text" name="acc_no" class="form-control" value="{{ $book->acc_no }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Location</label>
                        <select name="location_id" class="form-select">
                            <option value="">Select Location...</option>
                            @foreach($locations as $l)<option value="{{ $l->id }}" {{ $book->location_id == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Volumes / Copies <span class="text-danger">*</span></label>
                        <input type="number" name="volumes" class="form-control" value="{{ $book->volumes }}" required min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Publication Year</label>
                        <input type="number" name="publication_year" class="form-control" value="{{ $book->publication_year }}" min="1800" max="{{ date('Y') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ $book->remarks }}</textarea>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('library.books') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Book</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
