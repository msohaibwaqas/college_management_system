@extends('layouts.app')

@section('title', 'Bulk Announcements')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-bullhorn me-2"></i>Bulk Announcements</h1>
            <p class="page-subtitle">Draft and disparch messages to large stakeholder groups</p>
        </div>
        <div>
            <a href="{{ route('announcements.create') }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Compose Announcement
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Recipients</th>
                            <th>Status/Sent At</th>
                            <th>Author</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($announcements as $ann)
                        <tr>
                            <td>
                                <strong>{{ $ann->title }}</strong>
                                <div class="small text-muted text-truncate" style="max-width: 300px;">
                                    {{ Str::limit($ann->body, 50) }}
                                </div>
                            </td>
                            <td>
                                @foreach(explode(',', $ann->recipient_group) as $grp)
                                    <span class="badge bg-light text-dark border">{{ ucwords(str_replace('_', ' ', $grp)) }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($ann->sent_at)
                                    <span class="badge bg-success"><i class="fas fa-check-double me-1"></i> Sent: {{ $ann->sent_at->format('M d, Y H:i') }}</span>
                                @elseif($ann->scheduled_at && $ann->scheduled_at->isFuture())
                                    <span class="badge bg-info text-dark"><i class="fas fa-clock me-1"></i> Scheduled: {{ $ann->scheduled_at->format('M d, Y H:i') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Pending/Processing</span>
                                @endif
                            </td>
                            <td>{{ optional($ann->creator)->name ?? 'System' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <form action="{{ route('announcements.destroy', $ann->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement record?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Announcement">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No announcements have been dispatched yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $announcements->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
