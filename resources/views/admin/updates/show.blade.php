@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Update: {{ $update->title }}</h1>
            <p class="text-muted">{{ \Carbon\Carbon::parse($update->date)->format('d M, Y') }}</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('updates.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Updates
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <p>Additional photos management for this update will be implemented here.</p>
        </div>
    </div>
</div>
@endsection
