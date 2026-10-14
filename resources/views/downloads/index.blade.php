@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Downloads Management</h1>
                <a href="{{ route('downloads.create') }}" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Upload New File
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>File</th>
                            <th>Size</th>
                            <th>Published Date</th>
                            <th>Uploaded By</th>
                            <th>Downloads</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($downloads as $download)
                        <tr>
                            <td>
                                <strong>{{ $download->title }}</strong>
                            </td>
                            <td>
                                <small class="text-muted">{{ Str::limit($download->description, 50) }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-file me-1"></i>{{ strtoupper($download->file_extension) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ $download->formatted_file_size }}</small>
                            </td>
                            <td>
                                <small>{{ $download->formatted_date }}</small>
                            </td>
                            <td>
                                <small>{{ $download->uploader->username ?? 'Unknown' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $download->download_count }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('download.file', $download->id) }}" 
                                       class="btn btn-outline-primary" 
                                       title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form action="{{ route('downloads.destroy', $download->id) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this file?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No files uploaded yet.</p>
                                <a href="{{ route('downloads.create') }}" class="btn btn-primary btn-sm mt-3">
                                    Upload Your First File
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($downloads->hasPages())
            <div class="mt-4">
                {{ $downloads->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
