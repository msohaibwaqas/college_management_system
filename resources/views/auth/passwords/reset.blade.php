<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - College Management System</title>
    
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
                <p class="mb-0 mt-2">Create New Password</p>
            </div>
            
            <div class="login-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach ($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email Address
                        </label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ $email ?? old('email') }}" 
                               required 
                               autocomplete="email" 
                               readonly>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>New Password
                        </label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               required 
                               autocomplete="new-password"
                               autofocus
                               placeholder="Enter new password">
                    </div>

                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">
                            <i class="fas fa-lock me-1"></i>Confirm New Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="password-confirm" 
                               name="password_confirmation" 
                               required 
                               autocomplete="new-password"
                               placeholder="Confirm new password">
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100">
                        <i class="fas fa-save me-2"></i>Reset Password
                    </button>
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
