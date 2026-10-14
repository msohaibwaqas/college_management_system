@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Notifications</h1>
        @if(Auth::user()->unreadNotifications->isNotEmpty())
            <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary shadow-sm">
                    <i class="fas fa-check-double fa-sm text-white-50 me-1"></i> Mark All as Read
                </button>
            </form>
        @endif
        @if(Auth::user()->notifications->isNotEmpty())
            <form action="{{ route('notifications.destroyAll') }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to delete ALL notifications?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger shadow-sm">
                    <i class="fas fa-trash fa-sm text-white-50 me-1"></i> Delete All
                </button>
            </form>
        @endif
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if($notifications->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-gray-300 mb-3"></i>
                    <p class="text-gray-500 mb-0">No notifications found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Details</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                                <tr class="{{ $notification->unread() ? 'table-primary-soft border-start border-4 border-primary' : '' }}">
                                    <td class="text-nowrap text-muted" style="width: 15%;">
                                        {{ $notification->created_at->format('M d, Y') }}<br>
                                        <small>{{ $notification->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        @php $data = $notification->data; @endphp
                                        <div class="d-flex flex-column">
                                            @if(($data['type'] ?? '') === 'mark_change')
                                                {{-- Mark Change Notification --}}
                                                <span class="fw-bold">
                                                    <span class="badge bg-warning text-dark me-1">Marks Changed</span>
                                                    {{ $data['student_name'] ?? 'N/A' }}
                                                    @if(!empty($data['student_roll_no']))
                                                        <small class="text-muted fw-normal">(Roll: {{ $data['student_roll_no'] }})</small>
                                                    @endif
                                                    <small class="text-muted fw-normal">—</small>
                                                    {{ $data['subject'] ?? 'N/A' }}
                                                </span>
                                                <small class="text-muted">
                                                    <strong>Theory:</strong> {{ $data['prev_theory'] ?? 'N/A' }} → {{ $data['new_theory'] ?? 'N/A' }} |
                                                    <strong>Practical:</strong> {{ $data['prev_practical'] ?? 'N/A' }} → {{ $data['new_practical'] ?? 'N/A' }} |
                                                    <strong>Changed By:</strong> {{ $data['user_name'] ?? 'System' }}
                                                </small>
                                            @else
                                                {{-- Teacher Assignment Notification --}}
                                                <span class="fw-bold">
                                                    @if(($data['action'] ?? '') == 'Assigned')
                                                        <span class="badge bg-success me-1">Assigned</span>
                                                    @else
                                                        <span class="badge bg-danger me-1">Unassigned</span>
                                                    @endif
                                                    {{ $data['student_name'] ?? 'N/A' }} 
                                                    <small class="text-muted fw-normal">to</small> 
                                                    {{ $data['teacher_name'] ?? 'N/A' }}
                                                </span>
                                                <small class="text-muted">
                                                    <strong>Roll No:</strong> {{ $data['student_roll_no'] ?? 'N/A' }} |
                                                    <strong>Adm Year:</strong> {{ $data['admission_year'] ?? 'N/A' }} |
                                                    <strong>Subject:</strong> {{ $data['subject_name'] ?? 'N/A' }} | 
                                                    <strong>By:</strong> {{ $data['performed_by'] ?? 'System' }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if($notification->unread())
                                            <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Mark as Read">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 d-flex justify-content-center">
                    {{ $notifications->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .table-primary-soft {
        background-color: rgba(13, 110, 253, 0.05);
    }
    .rotated {
        transform: rotate(90deg);
    }
</style>
@endsection
