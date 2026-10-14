@extends('layouts.app')

@section('title', 'Manage Salary Structure')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-edit me-2"></i>Manage: {{ $structure->name }}</h1>
        </div>
        <a href="{{ route('salary-structures.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Basic Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Basic Settings</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('salary-structures.update', $structure->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-bold">Structure Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $structure->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Monthly Base Salary</label>
                            <input type="number" name="base_salary" class="form-control" value="{{ $structure->base_salary }}" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $structure->is_active ? 'checked' : '' }}>
                                <label class="form-check-label">Active Scale</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Components Management -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Allowances & Deductions</h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addComponentModal">
                        <i class="fas fa-plus me-1"></i> Add Component
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Component Name</th>
                                <th>Type</th>
                                <th>Calculation</th>
                                <th class="text-end">Value</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $totalEarnings = 0; 
                                $totalDeductions = 0; 
                            @endphp
                            @forelse($structure->components as $comp)
                                @php
                                    $calculated = ($comp->amount_type == 'percentage') ? ($structure->base_salary * $comp->amount / 100) : $comp->amount;
                                    if($comp->type == 'earning') $totalEarnings += $calculated;
                                    else $totalDeductions += $calculated;
                                @endphp
                                <tr>
                                    <td>{{ $comp->name }}</td>
                                    <td>
                                        <span class="badge {{ $comp->type == 'earning' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($comp->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($comp->amount_type == 'percentage')
                                            {{ $comp->amount }}% of Base
                                        @else
                                            Fixed Amount
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($calculated) }}
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('salary-structures.remove-component', $comp->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger" onclick="return confirm('Remove component?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No specific components added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Gross Salary:</td>
                                <td class="text-end fw-bold text-success">{{ number_format($structure->base_salary + $totalEarnings) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total Deductions:</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($totalDeductions) }}</td>
                                <td></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="3" class="text-end fw-bold">Estimated Monthly Net:</td>
                                <td class="text-end fw-bold fs-5">{{ number_format(($structure->base_salary + $totalEarnings) - $totalDeductions) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addComponentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('salary-structures.add-component', $structure->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name (e.g. Utility Allowance, Income Tax)</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="earning">Earning (+)</option>
                            <option value="deduction">Deduction (-)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Value Type</label>
                        <select name="amount_type" class="form-select">
                            <option value="fixed">Fixed PKR</option>
                            <option value="percentage">% of Base</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount / Percentage Value</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Component</button>
            </div>
        </form>
    </div>
</div>
@endsection
