@extends('layouts.app')

@section('title', 'BS Clerk Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">BS/ADP Clerk Dashboard</h1>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">BS Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $studentCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">New BS Adm.</div>
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
                    <h6 class="m-0 font-weight-bold text-primary">BS Record Management</h6>
                </div>
                    <div class="list-group">
                        <a href="{{ route('he.datesheets.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-check me-2"></i> Update BS Datesheets
                        </a>
                        <a href="{{ route('he.results.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-poll-h me-2"></i> Update Semester Results
                        </a>
                        <a href="{{ route('admissions.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-id-card me-2"></i> Verify BS Student Details
                        </a>
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection
