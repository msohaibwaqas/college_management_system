@extends('layouts.app')

@section('title', 'Public Holidays Calendar')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-calendar-alt me-2"></i>Public Holidays</h1>
            <p class="page-subtitle">Configure Gazetted and Local holidays that automatically bypass absence flags</p>
        </div>
        <div>
            <a href="{{ route('holidays.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Holiday
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
                            <th>Holiday Title</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays as $holiday)
                        <tr>
                            <td>
                                <strong>{{ $holiday->title }}</strong>
                                @if($holiday->description)
                                    <div class="small text-muted text-truncate" style="max-width: 200px;">{{ $holiday->description }}</div>
                                @endif
                            </td>
                            <td>
                                <h5><span class="badge {{ $holiday->date >= now()->startOfDay() ? 'bg-primary' : 'bg-light text-dark' }}"><i class="far fa-calendar me-1"></i>{{ $holiday->date->format('l, d M Y') }}</span></h5>
                            </td>
                            <td>
                                @if($holiday->type === 'public')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Public</span>
                                @elseif($holiday->type === 'gazetted')
                                    <span class="badge bg-purple bg-opacity-10 text-purple border border-purple">Gazetted</span>
                                @else
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">Local</span>
                                @endif
                            </td>
                            <td>
                                @if($holiday->is_active)
                                    <span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i>Active</span>
                                @else
                                    <span class="text-muted small fw-bold"><i class="fas fa-times-circle me-1"></i>Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('holidays.edit', $holiday->id) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('holidays.destroy', $holiday->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this holiday configuration? Daily attendance logic relies on this.');" style="display:inline;">
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
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3 text-light"></i><br>
                                No Public Holidays defined. <br>Create holidays so that absent staff are not penalized on these days.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
