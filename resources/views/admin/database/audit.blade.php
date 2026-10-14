@extends('layouts.app')

@section('title', 'Database Audit & Orphan Cleanup')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-database text-primary me-2"></i>Database Audit &amp; Orphan Cleanup</h1>
            <p class="text-muted small mb-0">Visually inspect, audit, and safely clean orphaned database records and invalid section strings across all CMS models.</p>
        </div>
        @if($totalIssues > 0)
            <form action="{{ route('admin.database.audit.clean_all') }}" method="POST" onsubmit="return confirm('Are you sure you want to clean all detected orphan records?');">
                @csrf
                <button type="submit" class="btn btn-danger font-weight-bold px-4 shadow-sm">
                    <i class="fas fa-broom me-1"></i> Clean All Orphan Records (1 Click)
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Overall Status Summary Banner -->
    <div class="card shadow mb-4 border-start border-4 {{ $totalIssues == 0 ? 'border-success' : 'border-danger' }}">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        @if($totalIssues == 0)
                            <i class="fas fa-shield-alt fa-3x text-success"></i>
                        @else
                            <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                        @endif
                    </div>
                    <div>
                        <h5 class="mb-1 font-weight-bold {{ $totalIssues == 0 ? 'text-success' : 'text-danger' }}">
                            {{ $totalIssues == 0 ? 'Database Status: Excellent & Clean' : "Database Alert: {$totalIssues} Orphan / Invalid Record(s) Detected" }}
                        </h5>
                        <p class="mb-0 text-muted small">
                            {{ $totalIssues == 0 ? 'All relational foreign keys and section attributes are healthy with 0 orphaned records.' : 'Orphan records can occur when students or subjects are removed. Use the tools below to clean them safely.' }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('admin.database.audit.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-sync-alt me-1"></i> Re-Scan Database
                </a>
            </div>
        </div>
    </div>

    <!-- Category Audit Cards -->
    <div class="row g-4">
        @foreach($categories as $key => $cat)
            <div class="col-lg-6">
                <div class="card shadow h-100 border-start border-3 {{ $cat['count'] == 0 ? 'border-success' : 'border-warning' }}">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h6 class="m-0 font-weight-bold text-dark">{{ $cat['title'] }}</h6>
                        </div>
                        <span class="badge {{ $cat['count'] == 0 ? 'bg-success' : 'bg-danger' }} fs-6 px-3 py-2">
                            {{ $cat['count'] }} {{ $cat['count'] == 1 ? 'Issue' : 'Issues' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">{{ $cat['description'] }}</p>

                        @if($cat['count'] > 0 && count($cat['samples']) > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered bg-light text-muted mb-0" style="font-size: 0.78rem;">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Sample Attributes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cat['samples'] as $sample)
                                            <tr>
                                                <td>#{{ $sample->id }}</td>
                                                <td>
                                                    @foreach((array)$sample as $prop => $val)
                                                        @if($prop != 'id')
                                                            <span class="me-2"><code>{{ $prop }}</code>: <strong>{{ $val ?? 'NULL' }}</strong></span>
                                                        @endif
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2">
                        <small class="text-muted">
                            @if($cat['count'] == 0)
                                <i class="fas fa-check-circle text-success me-1"></i> Clean
                            @else
                                <i class="fas fa-info-circle text-warning me-1"></i> Requires Action
                            @endif
                        </small>
                        @if($cat['count'] > 0)
                            <form action="{{ route('admin.database.audit.clean') }}" method="POST" onsubmit="return confirm('Clean records for {{ $cat['title'] }}?');">
                                @csrf
                                <input type="hidden" name="category" value="{{ $key }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger font-weight-bold">
                                    <i class="fas fa-broom me-1"></i> Clean This Category
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
