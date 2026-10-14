@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Create New Committee</h1>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('committees.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Committee Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="order_by" class="form-label">Order By <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('order_by') is-invalid @enderror" id="order_by" name="order_by" value="{{ old('order_by', 0) }}" required>
                    @error('order_by')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('committees.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Committee</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
