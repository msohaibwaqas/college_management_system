@extends('layouts.app')

@section('title', 'Asset Categories')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-tags me-2"></i>Asset Categories</h1>
            <p class="page-subtitle">Group your assets by type (e.g., Electronics, Furniture).</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus me-1"></i> New Category
        </button>
    </div>

    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Category Name</th>
                                <th>Description</th>
                                <th class="text-center pe-4">Assets Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $category->name }}</td>
                                <td class="text-muted small">{{ $category->description ?? 'No description' }}</td>
                                <td class="text-center pe-4">
                                    <span class="badge bg-primary rounded-pill">{{ $category->assets_count }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Quick Add Category</h6>
                    <form action="{{ route('assets.store_category') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. IT Equipment">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
