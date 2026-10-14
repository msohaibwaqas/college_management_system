@extends('layouts.app')

@section('title', 'Create Salary Structure')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-plus me-2"></i>Create Salary Structure</h1>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 600px;">
        <div class="card-body">
            <form action="{{ route('salary-structures.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Structure Name (Internal Label)</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Admin Scale BPS-17" required>
                    <div class="form-text">Used to identify this scale when assigning to staff.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Standard Monthly Base Salary</label>
                    <div class="input-group">
                        <span class="input-group-text">PKR</span>
                        <input type="number" name="base_salary" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Create & Configure Components</button>
                    <a href="{{ route('salary-structures.index') }}" class="btn btn-light ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
