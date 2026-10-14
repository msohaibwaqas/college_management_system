@extends('layouts.app')
@section('title', 'Library Management')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-book-reader me-2"></i>Library Management</h1>
        <a href="{{ route('library.books.create') }}" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Add New Book
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="row">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Books</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalBooks }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Copies</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalCopies }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $availableCopies }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Issued</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $issuedCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Overdue</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $overdueCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Authors</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalAuthors }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('library.books') }}" class="btn btn-outline-primary me-2 mb-2"><i class="fas fa-search me-1"></i> Search Books</a>
                    <a href="{{ route('library.issue_book') }}" class="btn btn-outline-success me-2 mb-2"><i class="fas fa-hand-holding me-1"></i> Issue Book</a>
                    <a href="{{ route('library.issued_books') }}" class="btn btn-outline-warning me-2 mb-2"><i class="fas fa-list me-1"></i> Issued Books</a>
                    <a href="{{ route('library.authors') }}" class="btn btn-outline-info me-2 mb-2"><i class="fas fa-user-edit me-1"></i> Authors</a>
                    <a href="{{ route('library.genres') }}" class="btn btn-outline-secondary me-2 mb-2"><i class="fas fa-tags me-1"></i> Genres</a>
                    <a href="{{ route('library.locations') }}" class="btn btn-outline-dark me-2 mb-2"><i class="fas fa-map-marker-alt me-1"></i> Locations</a>
                    <a href="{{ route('library.import_export') }}" class="btn btn-outline-primary me-2 mb-2"><i class="fas fa-file-csv me-1"></i> Import / Export</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Issues --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Borrower</th>
                            <th>Type</th>
                            <th>Issued</th>
                            <th>Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentIssues as $issue)
                        <tr>
                            <td>{{ $issue->book->title ?? 'N/A' }}</td>
                            <td>{{ $issue->borrower_name }}</td>
                            <td><span class="badge bg-{{ $issue->issued_to_type == 'student' ? 'primary' : 'info' }}">{{ ucfirst($issue->issued_to_type) }}</span></td>
                            <td>{{ $issue->issued_at->format('M d, Y') }}</td>
                            <td class="{{ $issue->is_overdue ? 'text-danger font-weight-bold' : '' }}">{{ $issue->due_date->format('M d, Y') }}</td>
                            <td>
                                @if($issue->status == 'returned')
                                    <span class="badge bg-success">Returned</span>
                                @elseif($issue->is_overdue)
                                    <span class="badge bg-danger">Overdue</span>
                                @else
                                    <span class="badge bg-warning text-dark">Issued</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No recent activity.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
