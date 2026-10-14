@extends('layouts.app')

@section('title', 'Leave Types Management')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-tags me-2"></i>Leave Types Configure</h1>
            <p class="page-subtitle">Manage categories of staff leave and their default limits</p>
        </div>
        <div>
            <a href="{{ route('leave-types.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Leave Type
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
                            <th>Leave Name (Code)</th>
                            <th>Default Quota (Days)</th>
                            <th>Attributes</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveTypes as $type)
                        <tr>
                            <td>
                                <strong>{{ $type->name }}</strong>
                                <br><small class="text-muted border rounded px-1 bg-light">{{ $type->code }}</small>
                            </td>
                            <td>
                                <h5><span class="badge bg-secondary">{{ $type->default_days }} Days/Year</span></h5>
                            </td>
                            <td>
                                @if($type->is_paid)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success mb-1">Paid Leave</span><br>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger mb-1">Unpaid Leave</span><br>
                                @endif
                                
                                @if($type->requires_document)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info"><i class="fas fa-paperclip me-1"></i>Doc Required</span>
                                @endif
                            </td>
                            <td>
                                @if($type->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('leave-types.edit', $type->id) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('leave-types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Leave Type? (Historical records may be affected)');" style="display:inline;">
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
                            <td colspan="5" class="text-center py-4 text-muted">No Leave Types configured. Create (e.g., Medical, Casual, Earned) to start handling staff leaves.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
