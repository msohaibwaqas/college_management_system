@extends('layouts.public')
@section('title', 'Admissions Closed')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center" data-aos="fade-up">
            <div class="bg-light p-5 rounded-4 shadow-sm border-top border-4 border-warning">
                <i class="fas fa-lock fa-4x text-warning mb-4"></i>
                <h1 class="display-5 fw-bold mb-3">{{ $program ?? 'Inter' }} Admissions Closed</h1>
                
                @php
                    $settings = \App\Models\AdmissionSetting::getForProgram($program ?? 'Inter');
                @endphp

                @if($settings->start_date && $settings->start_date->isFuture())
                    <p class="lead text-muted mb-4">
                        Online admissions for <strong>{{ $program ?? 'Inter' }}</strong> session will open on 
                        <strong>{{ $settings->start_date->format('l, F j, Y') }}</strong>.
                    </p>
                    <p class="mb-4">Please check back later or contact the college administration for more details.</p>
                @else
                    <p class="lead text-muted mb-4">
                        The online admission period for <strong>{{ $program ?? 'Inter' }}</strong> is currently closed.
                    </p>
                    <p class="mb-4">If you believe this is an error, please contact the admission office.</p>
                @endif
                
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-home me-2"></i> Return to Home
                    </a>
                    <a href="{{ route('public.contact') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-phone me-2"></i> Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
