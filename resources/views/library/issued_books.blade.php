@extends('layouts.app')
@section('title', 'Issued Books')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Issued Books</h1>
        <a href="{{ route('library.issue_book') }}" class="btn btn-success btn-sm shadow-sm"><i class="fas fa-hand-holding fa-sm mr-1"></i> Issue New Book</a>
    </div>

    {{-- Filter --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('library.issued_books') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by book title..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="issued" {{ request('status') == 'issued' ? 'selected' : '' }}>Issued</option>
                        <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Returned</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Book</th>
                            <th>Borrower</th>
                            <th>Type</th>
                            <th>Issued Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issues as $issue)
                        <tr class="{{ $issue->is_overdue ? 'table-danger' : '' }}">
                            <td>{{ $loop->iteration + ($issues->currentPage() - 1) * $issues->perPage() }}</td>
                            <td><strong>{{ $issue->book->title ?? 'N/A' }}</strong></td>
                            <td>{{ $issue->borrower_name }}</td>
                            <td><span class="badge bg-{{ $issue->issued_to_type == 'student' ? 'primary' : 'info' }}">{{ ucfirst($issue->issued_to_type) }}</span></td>
                            <td>{{ $issue->issued_at->format('M d, Y') }}</td>
                            <td class="{{ $issue->is_overdue ? 'text-danger font-weight-bold' : '' }}">{{ $issue->due_date->format('M d, Y') }}</td>
                            <td>
                                @if($issue->status == 'returned')
                                    <span class="badge bg-success">Returned {{ $issue->returned_at->format('M d') }}</span>
                                @elseif($issue->is_overdue)
                                    <span class="badge bg-danger">Overdue {{ now()->diffInDays($issue->due_date) }}d</span>
                                @else
                                    <span class="badge bg-warning text-dark">Issued</span>
                                @endif
                            </td>
                            <td>
                                @if($issue->status == 'issued')
                                <form action="{{ route('library.return_book', $issue) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="fine_amount" value="0">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark as returned?')"><i class="fas fa-undo me-1"></i> Return</button>
                                </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">No issued books found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">{{ $issues->links() }}</div>
        </div>
    </div>
</div>
@endsection
