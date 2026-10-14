@extends('layouts.app')

@section('title', 'Chat with ' . $recipient->full_name)

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex align-items-center bg-white">
                    <a href="{{ route('lms.messages.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <img src="{{ $recipient->profile_picture_url }}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                    <h6 class="m-0 font-weight-bold text-primary">{{ $recipient->full_name }}</h6>
                </div>
                @if(session('success'))
                    <div class="alert alert-success m-3 mb-0">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger m-3 mb-0">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="card-body chat-container" style="height: 450px; overflow-y: auto; background-color: #f8f9fc; padding: 1.5rem;" id="chatBody">
                    @forelse($messages as $msg)
                        <div class="d-flex {{ $msg->sender_id == auth()->id() ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                            <div class="p-3 {{ $msg->sender_id == auth()->id() ? 'bg-primary text-white shadow' : 'bg-white shadow-sm border' }}" 
                                 style="max-width: 75%; border-radius: 15px; {{ $msg->sender_id == auth()->id() ? 'border-bottom-right-radius: 2px;' : 'border-bottom-left-radius: 2px;' }}">
                                <div class="message-text mb-1">{{ $msg->message }}</div>
                                <div class="small {{ $msg->sender_id == auth()->id() ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.7rem;">
                                    {{ \Carbon\Carbon::parse($msg->created_at)->format('H:i') }}
                                    @if($msg->sender_id == auth()->id())
                                        <i class="fas fa-check-double ms-1 {{ $msg->read_at ? 'text-white' : '' }}"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">Start a new conversation with {{ $recipient->full_name }}</p>
                        </div>
                    @endforelse
                </div>
                <div class="card-footer bg-white py-3">
                    <form action="{{ route('lms.messages.send', ['recipient' => $recipient->id]) }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="message" class="form-control bg-light border-0 py-3 px-4" placeholder="Type your message..." required autocomplete="off">
                            <button class="btn btn-primary px-4 shadow-sm" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatBody = document.getElementById('chatBody');
        chatBody.scrollTop = chatBody.scrollHeight;
    });
</script>
@endsection
