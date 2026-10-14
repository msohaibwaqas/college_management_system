@extends('layouts.app')
@section('title', 'View User')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user me-2"></i>View User</h2>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary ms-2">
                <i class="fas fa-edit me-1"></i>Edit User
            </a>
            @if(Auth::user()->is_super_admin || Auth::user()->hasRole('admin') || Auth::user()->hasRole('principal'))
                <form action="{{ route('users.reset_password', $user) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to reset password for {{ $user->username }} to 123?');">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Reset Password to 123
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="{{ $user->profile_picture_url }}" alt="Profile Picture" class="rounded-circle img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4>{{ $user->full_name }}</h4>
                    <p class="text-muted mb-1">{{ $user->staff->designation ?? 'Staff Member' }}</p>
                    <p class="text-muted mb-3">{{ $user->staff->staff_id ?? 'N/A' }}</p>
                    
                    <div class="d-grid gap-2">
                        @if($user->status == 'active')
                            <span class="badge bg-success p-2">Active Account</span>
                        @else
                            <span class="badge bg-danger p-2">{{ ucfirst($user->status) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Roles</h5>
                </div>
                <div class="card-body">
                    @if($user->is_super_admin)
                        <span class="badge bg-danger mb-2">Super Administrator</span>
                    @endif
                    
                    @foreach($user->roles as $role)
                        <span class="badge bg-info mb-2">{{ ucfirst($role->name) }}</span>
                    @endforeach

                    @if(!$user->is_super_admin && $user->roles->isEmpty())
                        <p class="text-muted mb-0">No roles assigned</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3 text-muted">Username</div>
                        <div class="col-sm-9 fw-bold">{{ $user->username }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 text-muted">Email</div>
                        <div class="col-sm-9">{{ $user->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 text-muted">Last Login</div>
                        <div class="col-sm-9">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('F j, Y g:i A') }}
                                <small class="text-muted">({{ $user->last_login_at->diffForHumans() }})</small>
                            @else
                                <span class="text-muted">Never logged in</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 text-muted">Created By</div>
                        <div class="col-sm-9">{{ $user->createdBy->username ?? 'System' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 text-muted">Created At</div>
                        <div class="col-sm-9">{{ $user->created_at->format('F j, Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Staff Information</h5>
                </div>
                <div class="card-body">
                    @if($user->staff)
                        <div class="row mb-3">
                            <div class="col-sm-3 text-muted">Full Name</div>
                            <div class="col-sm-9">{{ $user->staff->first_name }} {{ $user->staff->last_name }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 text-muted">Father Name</div>
                            <div class="col-sm-9">{{ $user->staff->father_name }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 text-muted">CNIC</div>
                            <div class="col-sm-9">{{ $user->staff->cnic }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 text-muted">Contact</div>
                            <div class="col-sm-9">{{ $user->staff->contact_number }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 text-muted">Category</div>
                            <div class="col-sm-9">{{ ucfirst($user->staff->category) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('staff.show', $user->staff) }}" class="btn btn-outline-primary btn-sm">
                                    View Full Staff Profile
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            No staff record linked to this user.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
