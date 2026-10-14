<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Required: Change Your Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #f8fafc;
        }
        .card-custom {
            background: #ffffff;
            color: #1e293b;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            border: none;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 28px;
        }
        .form-control:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
    </style>
</head>
<body>
    <div class="card-custom">
        <div class="card-header-custom">
            <div class="icon-circle">
                <i class="fas fa-lock"></i>
            </div>
            <h4 class="fw-bold mb-1">Password Change Required</h4>
            <p class="mb-0 small text-white-50">Your account password was reset by an administrator.</p>
        </div>
        <div class="card-body p-4">
            @if(session('warning'))
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="text-muted small mb-4">
                For security reasons, you must set a new confidential password before you can access system features.
            </p>

            <form action="{{ route('password.force_change.update') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary">Current Temporary Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                        <input type="password" name="current_password" class="form-control" required placeholder="Enter temporary password (e.g. 123)">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Enter new password (min. 6 chars)">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-check-circle text-muted"></i></span>
                        <input type="password" name="password_confirmation" class="form-control" required placeholder="Re-enter new password">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger btn-submit text-white">
                        <i class="fas fa-save me-2"></i>Update Password & Continue
                    </button>
                </div>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-link text-decoration-none text-muted small p-0">
                        <i class="fas fa-sign-out-alt me-1"></i>Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
