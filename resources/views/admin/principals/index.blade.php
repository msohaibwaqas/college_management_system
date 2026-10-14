@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Principals History</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('principals.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Principal Record
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Tenure</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($principals as $principal)
                        <tr class="{{ $principal->is_current ? 'table-primary' : '' }}">
                            <td>
                                <img src="{{ $principal->getImageUrl() }}" alt="Photo" style="height: 50px; width: 50px; object-fit: cover; border-radius: 50%;">
                            </td>
                            <td>
                                <strong>{{ $principal->title }} {{ $principal->name }}</strong>
                                @if($principal->is_current)
                                    <span class="badge bg-primary ms-2">Current</span>
                                @endif
                            </td>
                            <td>{{ $principal->designation }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($principal->from_date)->format('M Y') }} - 
                                {{ $principal->to_date ? \Carbon\Carbon::parse($principal->to_date)->format('M Y') : 'Present' }}
                            </td>
                            <td>
                                @if($principal->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('principals.edit', $principal->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('principals.destroy', $principal->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No principal records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $principals->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
