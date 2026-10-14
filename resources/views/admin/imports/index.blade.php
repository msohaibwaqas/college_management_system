@extends('layouts.app')

@section('title', 'Bulk Data Import')

@section('content')
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-file-import me-2"></i>Global Data Importer</h1>
        <p class="page-subtitle">Batch upload records for Students and Staff using Excel or CSV templates.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Staff Import -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-user-tie me-2"></i>Import Staff Members</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Expected Columns: <code>first_name, last_name, email, phone, designation, gender</code></p>
                    <form action="{{ route('imports.staff') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Select Excel File (.xlsx, .csv)</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Upload & Process Staff <i class="fas fa-upload ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Student Import -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="fas fa-user-graduate me-2"></i>Import Students</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Expected Columns: <code>first_name, last_name, email, phone, roll_no, gender</code></p>
                    <form action="{{ route('imports.students') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Select Excel File (.xlsx, .csv)</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            Upload & Process Students <i class="fas fa-upload ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm mt-4">
        <h6 class="fw-bold"><i class="fas fa-info-circle me-2 text-info"></i>Developer Note:</h6>
        <p class="mb-0 small">The importer uses <strong>Header Row</strong> detection. Ensure your first row contains the exact column names mentioned above. Duplicate emails or roll numbers will be rejected by the validation engine.</p>
    </div>
</div>
@endsection
