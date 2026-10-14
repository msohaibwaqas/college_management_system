@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Album: {{ $album->title }}</h1>
            <p class="text-muted">{{ \Carbon\Carbon::parse($album->date)->format('d M, Y') }}</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('albums.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Albums
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Photos</h6>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                <i class="fas fa-upload me-1"></i> Upload Photos
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse($album->photos as $photo)
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <img src="{{ $photo->getImageUrl() }}" class="card-img-top" alt="{{ $photo->title }}" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-2">
                            <p class="card-text small mb-2">{{ $photo->title }}</p>
                            <form action="{{ route('photos.destroy', $photo->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this photo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No photos in this album yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Photos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('photos.store', $album->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="photos" class="form-label">Select Photos</label>
                        <input type="file" class="form-control" id="photos" name="photos[]" multiple accept="image/*" required>
                        <small class="text-muted">You can select multiple files. Max size per file: 2MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
