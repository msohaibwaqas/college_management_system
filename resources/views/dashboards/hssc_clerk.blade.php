@extends('layouts.app')

@section('title', 'HSSC Clerk Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">HSSC Clerk Dashboard</h1>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">HSSC Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $studentCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">New HSSC Adm.</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $newAdmissions }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Inter Record Management</h6>
                </div>
                    <div class="list-group">
                        <a href="{{ route('results.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit me-2"></i> Update Inter Results
                        </a>
                        <a href="{{ route('admission-applications.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt me-2"></i> Verify Admission Forms
                        </a>
                        <a href="{{ route('students.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-search me-2"></i> Student Search (Inter)
                        </a>
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection
