@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Upload New File</h1>
                <a href="{{ route('downloads.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Downloads
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('downloads.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}" 
                                   placeholder="Enter file title"
                                   required>
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Give your file a descriptive title</small>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Enter file description (optional)">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="file" class="form-label fw-bold">
                                File <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   id="file" 
                                   name="file" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                   required>
                            @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Allowed formats: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max: 10MB)
                            </small>
                        </div>

                        <div class="mb-4">
                            <label for="published_date" class="form-label fw-bold">Published Date</label>
                            <input type="date" 
                                   class="form-control @error('published_date') is-invalid @enderror" 
                                   id="published_date" 
                                   name="published_date" 
                                   value="{{ old('published_date', date('Y-m-d')) }}">
                            @error('published_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('downloads.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload File
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>Upload Guidelines
                </h6>
                <ul class="mb-0 small">
                    <li>Ensure the file name is descriptive and professional</li>
                    <li>Maximum file size is 10MB</li>
                    <li>Uploaded files will be publicly accessible for download</li>
                    <li>You can delete files later from the downloads management page</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
