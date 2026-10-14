@extends('layouts.app')

@section('title', 'Notification Templates')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-file-alt me-2"></i>Message Templates</h1>
            <p class="page-subtitle">Manage automated notification templates for Email, SMS, and In-App</p>
        </div>
        <div>
            <a href="{{ route('notification-templates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add New Template
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
                            <th>Type/Code</th>
                            <th>Subject</th>
                            <th>Channels</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $template->type }}</span>
                            </td>
                            <td>
                                <strong>{{ $template->subject ?: '(No Subject)' }}</strong>
                                <div class="small text-muted text-truncate" style="max-width: 300px;">
                                    {{ Str::limit($template->body, 50) }}
                                </div>
                            </td>
                            <td>
                                @foreach(explode(',', $template->channel) as $ch)
                                    @if(trim($ch) === 'email') <span class="badge bg-primary">Email</span> @endif
                                    @if(trim($ch) === 'sms') <span class="badge bg-info text-dark">SMS</span> @endif
                                    @if(trim($ch) === 'in-app' || trim($ch) === 'database') <span class="badge bg-warning text-dark">In-App</span> @endif
                                @endforeach
                            </td>
                            <td>
                                @if($template->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('notification-templates.edit', $template->id) }}" class="btn btn-outline-primary" title="Edit Template">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('notification-templates.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this template?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Template">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No communication templates found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
