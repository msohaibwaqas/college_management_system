@extends('layouts.app')

@section('title', 'Salary Structures')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-file-invoice-dollar me-2"></i>Salary Structures</h1>
            <p class="page-subtitle">Define pay scales, allowances, and deductions for different staff roles.</p>
        </div>
        <a href="{{ route('salary-structures.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Create New Structure
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Structure Name</th>
                            <th>Base Salary</th>
                            <th class="text-center">Components</th>
                            <th class="text-center">Assigned Staff</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($structures as $structure)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $structure->name }}</div>
                            </td>
                            <td>{{ number_format($structure->base_salary) }} /-</td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark">{{ $structure->components_count }} Items</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ $structure->staff_count }} Staff</span>
                            </td>
                            <td class="text-center">
                                @if($structure->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('salary-structures.edit', $structure->id) }}" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit me-1"></i>Edit / Manage
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No salary structures defined. Start by creating one for your staff.
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
