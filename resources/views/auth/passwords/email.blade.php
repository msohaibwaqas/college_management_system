<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - College Management System</title>
    
    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    {{-- Font Awesome Icons --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    {{-- Custom Styles --}}
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body class="login-page">
@php
    $settings = \App\Models\InstituteSetting::getSettings();
@endphp
    <div class="d-flex flex-column align-items-center justify-content-center w-100" style="min-height: 100vh;">
        <div class="login-card">
            <div class="login-header">
                @if($settings->hasLogo())
                    <img src="{{ $settings->logo_url }}" alt="College Logo">
                @else
                    <i class="fas fa-graduation-cap"></i>
                @endif
                <h3 class="mb-0">{{ $settings->institute_title ?? 'College Management System' }}</h3>
                <p class="mb-0 mt-2">Reset Password</p>
            </div>
            
            <div class="login-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach ($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email Address
                        </label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autofocus
                               placeholder="Enter your email address">
                        <div class="form-text mt-2 text-muted">
                            Enter the email associated with your profile to receive a reset link.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100 mb-3">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                    </button>

                    <div class="text-center mt-3">
                        <a href="{{ route('login') }}" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-2 text-center">
            @cmsCredits
        </div>
    </div>
    
    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
