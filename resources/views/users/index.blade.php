@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-users me-2"></i>User Management</h2>
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Create New User
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, username, email..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-redo me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Staff</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <strong>{{ $user->username }}</strong>
                                    @if($user->is_super_admin)
                                        <span class="badge bg-danger ms-1">Super Admin</span>
                                    @endif
                                </td>
                                <td>{{ $user->full_name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->staff)
                                        {{ $user->staff->staff_id }} - {{ $user->staff->designation }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-info">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge 
                                        @if($user->status == 'active') bg-success
                                        @elseif($user->status == 'inactive') bg-secondary
                                        @elseif($user->status == 'suspended') bg-danger
                                        @else bg-warning
                                        @endif">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->last_login_at)
                                        {{ $user->last_login_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if(Auth::user()->is_super_admin || Auth::user()->hasRole('admin') || Auth::user()->hasRole('principal'))
                                        <form action="{{ route('users.reset_password', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset password for {{ $user->username }} to 123?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" title="Reset Password to 123">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($user->id !== Auth::id())
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No users found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
