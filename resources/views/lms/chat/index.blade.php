@extends('layouts.app')

@section('title', 'Messenger')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Teachers</h1>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Chat with Your Subject Teachers</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse($teachers as $teacher)
                            @if($teacher->user)
                            <a href="{{ route('lms.messages.show', ['recipient' => $teacher->user->id]) }}" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                                <img src="{{ $teacher->user->profile_picture_url ?? asset('uploads/default.jpg') }}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 font-weight-bold">{{ $teacher->full_name }}</h6>
                                    <p class="mb-0 text-muted small">Subject Teacher</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            @endif
                        @empty
                            <div class="text-center py-5">
                                <i class="fas fa-user-slash fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted">No teachers assigned to your classes yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
