@extends('layouts.app')

@section('title', 'HSSC Admin Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">HSSC Admin Dashboard</h1>
    </div>

    <!-- System Status & Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-{{ $systemHealth['integrity'] ? 'success' : 'danger' }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 border-right">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-3 {{ $systemHealth['integrity'] ? 'text-success' : 'text-danger' }}">
                                    <i class="fas {{ $systemHealth['integrity'] ? 'fa-shield-alt' : 'fa-exclamation-triangle' }} fa-2x"></i>
                                </div>
                                <div class="ml-3">
                                    <h6 class="text-xs font-weight-bold text-uppercase mb-1">System Status</h6>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $systemHealth['status'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 border-right px-4">
                            <h6 class="text-xs font-weight-bold text-uppercase mb-1">Data Points</h6>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 text-truncate">
                                {{ number_format($systemHealth['total_data']) }} Records
                            </div>
                        </div>
                        <div class="col-md-3 border-right px-4">
                            <h6 class="text-xs font-weight-bold text-uppercase mb-1">Engine V</h6>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $systemHealth['version'] }}
                            </div>
                        </div>
                        <div class="col-md-3 px-4 text-right">
                            <h6 class="text-xs font-weight-bold text-uppercase mb-1">Heartbeat</h6>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $systemHealth['heartbeat'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Total HSSC Students Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total HSSC Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $studentCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HSSC Admissions (Yearly) Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                HSSC Admissions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $newAdmissions }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subjects (HSSC) Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">HSSC Subjects
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $subjectCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Pending Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Result Announcements</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">3</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bullhorn fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HSSC Specific Management -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Inter (HSSC) Management</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('students.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-graduate me-2"></i> Manage Students
                        </a>
                        <a href="{{ route('admission-applications.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-door-open me-2"></i> Manage Admissions
                        </a>
                        <a href="{{ route('results.index') }}?section=hssc" class="list-group-item list-group-item-action">
                            <i class="fas fa-poll me-2"></i> Results Management
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Class-wise Distribution</h6>
                </div>
                <div class="card-body">
                    <p>Class 11: 450 Students</p>
                    <p>Class 12: 400 Students</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
