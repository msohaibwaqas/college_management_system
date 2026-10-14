@extends('layouts.app')
@section('title', 'Create User')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user-plus me-2"></i>Create New User</h2>
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

    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="staff_id" class="form-label">Staff Member <span class="text-danger">*</span></label>
                        <select name="staff_id" id="staff_id" class="form-control" required>
                            <option value="">Select Staff Member</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" {{ old('staff_id') == $member->id ? 'selected' : '' }}>
                                    {{ $member->staff_id }} - {{ $member->full_name }} ({{ $member->designation }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select the staff member for this user account</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required>
                        <small class="text-muted">Unique username for login</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    </div>
                </div>
            </div>
        </div>

        @if(Auth::user()->is_super_admin || Auth::user()->hasRole('admin'))
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Roles & Permissions</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_super_admin" id="is_super_admin" class="form-check-input" value="1" {{ old('is_super_admin') ? 'checked' : '' }}>
                        <label for="is_super_admin" class="form-check-label">
                            <strong>Super Administrator</strong>
                            <small class="text-muted d-block">Has full access to all system features</small>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assign Roles <span class="text-danger">*</span></label>
                    <small class="text-muted d-block mb-2">Select one or more roles for this user</small>
                    <div class="row">
                        @foreach($roles as $role)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           name="roles[]" 
                                           value="{{ $role->id }}" 
                                           id="role_{{ $role->id }}" 
                                           class="form-check-input"
                                           {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
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

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Create User
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i>Cancel
            </a>
        </div>
    </form>
</div>
@endsection
