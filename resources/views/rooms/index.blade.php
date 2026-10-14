@extends('layouts.app')
@section('title', 'Manage Rooms')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-door-open me-2"></i>Rooms Management</h2>
        <a href="{{ route('rooms.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Room
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Location</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td class="fw-bold">{{ $room->name }}</td>
                                <td>{{ $room->capacity }}</td>
                                <td>{{ $room->location ?? 'N/A' }}</td>
                                <td>
                                    @if($room->purpose === 'lecture')
                                        <span class="badge bg-info text-dark"><i class="fas fa-chalkboard-teacher me-1"></i>Lecture</span>
                                    @elseif($room->purpose === 'exam')
                                        <span class="badge bg-warning text-dark"><i class="fas fa-file-alt me-1"></i>Exam</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="fas fa-exchange-alt me-1"></i>Both</span>
                                    @endif
                                </td>
                                <td>
                                    @if($room->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('rooms.edit', $room) }}" class="btn btn-sm btn-warning text-white">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No rooms found.
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
