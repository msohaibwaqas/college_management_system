@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user-edit me-2"></i>Edit User</h2>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="staff_id" class="form-label">Staff Member <span class="text-danger">*</span></label>
                        <select name="staff_id" id="staff_id" class="form-control" required>
                            <option value="">-- Select Staff Member --</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" {{ old('staff_id', $user->staff_id) == $member->id ? 'selected' : '' }}>
                                    {{ $member->staff_id }} - {{ $member->full_name }} ({{ $member->designation }})@if($member->status !== 'active') [{{ ucfirst($member->status) }}]@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ old('status', $user->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="pending" {{ old('status', $user->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>

                    {{-- === DEMO MODE: Password change fields hidden === --}}
                    {{-- <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                    </div> --}}
                </div>
            </div>
        </div>

        @if(Auth::user()->is_super_admin || Auth::user()->hasRole('admin'))
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Roles & Permissions</h5>
            </div>
            <div class="card-body">
                @if(Auth::user()->is_super_admin)
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_super_admin" id="is_super_admin" class="form-check-input" value="1" {{ old('is_super_admin', $user->is_super_admin) ? 'checked' : '' }}>
                        <label for="is_super_admin" class="form-check-label">
                            <strong>Super Administrator</strong>
                            <small class="text-muted d-block">Has full access to all system features</small>
                        </label>
                    </div>
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Assign Roles <span class="text-danger">*</span></label>
                    <div class="row">
                        @foreach($roles as $role)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           name="roles[]" 
                                           value="{{ $role->id }}" 
                                           id="role_{{ $role->id }}" 
                                           class="form-check-input"
                                           {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                    <label for="role_{{ $role->id }}" class="form-check-label">
                                        {{ ucfirst($role->name) }}
                                        @if($role->description)
                                            <small class="text-muted d-block">{{ $role->description }}</small>
                                        @endif
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="mb-3 d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Update User
            </button>
            @if(Auth::user()->is_super_admin || Auth::user()->hasRole('admin') || Auth::user()->hasRole('principal'))
                <button type="button" class="btn btn-warning" onclick="if(confirm('Are you sure you want to reset password for {{ $user->username }} to 123?')) { document.getElementById('reset-password-form').submit(); }">
                    <i class="fas fa-key me-1"></i>Reset Password to 123
                </button>
            @endif
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i>Cancel
            </a>
        </div>
    </form>
    @if(Auth::user()->is_super_admin || Auth::user()->hasRole('admin') || Auth::user()->hasRole('principal'))
        <form action="{{ route('users.reset_password', $user) }}" method="POST" id="reset-password-form" class="d-none">
            @csrf
        </form>
    @endif
</div>
@endsection
