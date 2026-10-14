@extends('layouts.app')
@section('title', 'Issue a Book')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item active">Issue Book</li></ol></nav>

    @if(session('error'))
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-success text-white"><h6 class="m-0 font-weight-bold"><i class="fas fa-hand-holding me-2"></i>Issue Book to Borrower</h6></div>
        <div class="card-body">
            <form action="{{ route('library.store_issue') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">Select Book <span class="text-danger">*</span></label>
                        <select name="book_id" class="form-select" required id="book_select">
                            <option value="">Choose a book...</option>
                            @foreach($books as $book)
                                <option value="{{ $book->id }}">{{ $book->title }} ({{ $book->available_copies }} available)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control" required value="{{ old('due_date', now()->addDays(14)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label font-weight-bold">Borrower Type <span class="text-danger">*</span></label>
                        <select name="borrower_type" class="form-select" required id="borrower_type">
                            <option value="student">Student</option>
                            <option value="staff">Teacher / Staff</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">Search Borrower <span class="text-danger">*</span></label>
                        <input type="text" id="borrower_search" class="form-control" placeholder="Type name or ID to search...">
                        <input type="hidden" name="borrower_id" id="borrower_id" required>
                        <div id="search_results" class="list-group mt-1" style="position:absolute;z-index:100;max-height:200px;overflow-y:auto;"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">Selected Borrower</label>
                        <div id="selected_borrower" class="form-control bg-light" style="min-height:38px;">
                            <span class="text-muted">No borrower selected</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('library.issued_books') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Issue Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('borrower_search');
    const borrowerType = document.getElementById('borrower_type');
    const resultsDiv = document.getElementById('search_results');
    const borrowerId = document.getElementById('borrower_id');
    const selectedDiv = document.getElementById('selected_borrower');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        if (query.length < 2) { resultsDiv.innerHTML = ''; return; }

        debounceTimer = setTimeout(function() {
            fetch(`{{ route('library.search_borrower') }}?type=${borrowerType.value}&search=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action';
                        a.innerHTML = `<strong>${item.name}</strong> <small class="text-muted">${item.detail}</small>`;
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            borrowerId.value = item.id;
                            selectedDiv.innerHTML = `<strong>${item.name}</strong> — ${item.detail}`;
                            resultsDiv.innerHTML = '';
                            searchInput.value = '';
                        });
                        resultsDiv.appendChild(a);
                    });
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!resultsDiv.contains(e.target) && e.target !== searchInput) resultsDiv.innerHTML = '';
    });
});
</script>
@endpush
@endsection
