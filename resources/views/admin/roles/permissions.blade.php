@extends('layouts.app')

@section('title', 'Manage Role Permissions')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Role-Permission Mapping</h5>
            <button type="submit" form="permission-form" class="btn btn-light btn-sm">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
        <div class="card-body">
            <form action="{{ route('role-permissions.update') }}" method="POST" id="permission-form">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 150px;">Role</th>
                                @foreach($permissions as $permission)
                                    <th class="text-center" style="font-size: 0.75rem; min-width: 100px;">
                                        <span data-bs-toggle="tooltip" title="{{ $permission->name }}">
                                            {{ str_replace('.', ' ', $permission->name) }}
                                        </span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                                <tr>
                                    <td class="fw-bold">{{ ucfirst($role->name) }}</td>
                                    @foreach($permissions as $permission)
                                        <td class="text-center">
                                            <input type="checkbox" 
                                                   name="role_permissions[{{ $role->id }}][]" 
                                                   value="{{ $permission->id }}"
                                                   class="form-check-input"
                                                   {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        <div class="card-footer text-muted small">
            <i class="fas fa-info-circle me-1"></i> These assignments affect which menu items and routes are accessible for each role.
        </div>
    </div>
</div>

<style>
    .table th {
        vertical-align: middle;
        background-color: #f8f9fa;
    }
</style>

<script>
    $(document).ready(function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
@endsection
