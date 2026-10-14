@extends('layouts.app')
@section('title', 'Staff Management')

@section('content')
@php
    $activeRole = session('active_role');
    if (!$activeRole && Auth::check()) {
        if (Auth::user()->is_super_admin) {
            $activeRole = 'admin';
        } elseif (Auth::user()->roles->isNotEmpty()) {
            if (Auth::user()->hasRole('admin')) $activeRole = 'admin';
            elseif (Auth::user()->hasRole('principal')) $activeRole = 'principal';
            elseif (Auth::user()->hasRole('teacher')) $activeRole = 'teacher';
            else $activeRole = Auth::user()->roles->first()->name;
        }
    }
@endphp

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-users me-2"></i>Staff Management</h2>
            @if($activeRole != 'teacher')
            <a href="{{ route('staff.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add New Staff
            </a>
            @endif
            <a href="{{ route('staff.export', request()->query()) }}" class="btn btn-success ms-2">
                <i class="fas fa-download me-1"></i>Export CSV
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="category" class="form-label fw-semibold">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="teaching" {{ request('category') == 'teaching' ? 'selected' : '' }}>Teaching</option>
                        <option value="non_teaching" {{ request('category') == 'non_teaching' ? 'selected' : '' }}>Non-Teaching</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="left" {{ request('status') == 'left' ? 'selected' : '' }}>Left</option>
                        <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                        <option value="retired" {{ request('status') == 'retired' ? 'selected' : '' }}>Retired</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="search" class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Name, Staff ID, or CNIC" value="{{ request('search') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff List -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Staff Members ({{ $staff->total() }} total)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            @if($activeRole != 'teacher')
                            <th>Staff ID</th>
                            @endif
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Contact</th>
                            <th>Category</th>
                            <th>Designation</th>
                            <th>BPS</th>
                            <th>Status</th>
                            @if($activeRole != 'teacher')
                            <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                            <tr>
                                @if($activeRole != 'teacher')
                                <td><strong>{{ $member->staff_id }}</strong></td>
                                @endif
                                <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                                <td>{{ $member->cnic }}</td>
                                <td>{{ $member->contact_number ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $member->category == 'teaching' ? 'bg-info' : 'bg-secondary' }}">
                                        {{ ucfirst($member->category) }}
                                    </span>
                                </td>
                                <td>{{ $member->designation }}</td>
                                <td>{{ $member->bps }}</td>
                                <td>
                                    <span class="badge 
                                        @if($member->status == 'active') bg-success
                                        @elseif($member->status == 'transferred') bg-warning
                                        @elseif($member->status == 'retired') bg-secondary
                                        @else bg-danger
                                        @endif">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                </td>
                                @if($activeRole != 'teacher')
                                <td>
                                    <a href="{{ route('staff.show', $member) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('staff.edit', $member) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    @if(!$member->user)
                                        <form action="{{ route('staff.create-account', $member) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Create User Account">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-primary" title="Has Account">
                                            <i class="fas fa-user-check"></i>
                                        </span>
                                    @endif
                                    
                                    <form action="{{ route('staff.destroy', $member) }}" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No staff members found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($staff->hasPages())
            <div class="card-footer">
                {{ $staff->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
