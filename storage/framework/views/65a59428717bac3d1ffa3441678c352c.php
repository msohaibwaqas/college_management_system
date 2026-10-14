<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal Login - <?php echo e(\App\Models\InstituteSetting::getSettings()->short_name ?? 'College Management System'); ?></title>
    
    <?php
        $settings = \App\Models\InstituteSetting::getSettings();
        $colors = \App\Models\InstituteSetting::getColors();
    ?>

    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    
    <link href="<?php echo e(asset('css/custom.css')); ?>?v=<?php echo e(filemtime(public_path('css/custom.css'))); ?>" rel="stylesheet">

    <style>
    :root {
        --col-primary: <?php echo e($colors['primary']); ?>;
        --col-accent: <?php echo e($colors['secondary']); ?>;
        --col-dark: color-mix(in srgb, <?php echo e($colors['primary']); ?> 60%, black);
    }
    body {
        font-family: 'Outfit', sans-serif;
    }
    .btn-login-v2 {
        background: linear-gradient(135deg, var(--col-primary), var(--col-dark)) !important;
        color: #ffffff !important;
        border: none !important;
        padding: 12px;
        border-radius: 30px;
        font-weight: 700;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    .btn-login-v2:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        filter: brightness(1.1);
    }
    </style>
</head>
<body class="login-page-v2 d-flex align-items-center justify-content-center py-4 py-sm-5">

    <div class="container position-relative my-auto" style="z-index: 5;">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5 col-xl-4 d-flex flex-column align-items-center">
                
                
                <a href="<?php echo e(route('home')); ?>" class="btn btn-sm btn-outline-light rounded-pill px-4 mb-4 shadow-sm fw-semibold" style="backdrop-filter: blur(10px); border-color: rgba(255,255,255,0.3);">
                    <i class="fas fa-arrow-left me-2"></i>Back to Homepage
                </a>

                
                <div class="login-card-v2 p-4 p-md-5">
                    
                    
                    <div class="text-center mb-4">
                        <div class="mb-3 d-inline-block p-3 rounded-circle bg-white shadow-md">
                            <?php if($settings->hasLogo()): ?>
                                <img src="<?php echo e($settings->logo_url); ?>" alt="College Logo" style="height: 60px; width: 60px; object-fit: contain;">
                            <?php else: ?>
                                <i class="fas fa-graduation-cap fa-3x" style="color: var(--col-primary);"></i>
                            <?php endif; ?>
                        </div>
                        <h4 class="fw-bold text-dark mb-1" style="font-family: 'Playfair Display', serif;">
                            <?php echo e($settings->short_name ?? $settings->institute_title ?? 'GGC Chishtian'); ?>

                        </h4>
                        <span class="badge rounded-pill px-3 py-1 text-uppercase fw-bold"
                              style="background: color-mix(in srgb, var(--col-primary) 12%, transparent); color: var(--col-primary); font-size: 0.72rem; letter-spacing: 1.5px;">
                            STAFF PORTAL
                        </span>
                    </div>

                    
                    <div>
                        <?php if($errors->any()): ?>
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 mb-3" role="alert">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php echo e($error); ?>

                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if(session('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 mb-3" role="alert">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <?php echo e(session('error')); ?>

                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo e(route('login')); ?>">
                            <?php echo csrf_field(); ?>

                            <div class="mb-3">
                                <label for="username" class="form-label fw-bold text-secondary small">
                                    <i class="fas fa-user me-1 text-primary"></i>Username
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user-circle"></i></span>
                                    <input type="text"
                                           class="form-control border-start-0 ps-0 <?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="username"
                                           name="username"
                                           value="<?php echo e(old('username')); ?>"
                                           required
                                           autofocus
                                           placeholder="Enter username">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold text-secondary small">
                                    <i class="fas fa-lock me-1 text-primary"></i>Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-key"></i></span>
                                    <input type="password"
                                           class="form-control border-start-0 ps-0 <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="password"
                                           name="password"
                                           required
                                           placeholder="Enter password">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="captcha" class="form-label fw-bold text-secondary small">
                                    <i class="fas fa-robot me-1 text-success"></i>Human Verification: <strong><?php echo e($captcha_question); ?> = ?</strong>
                                </label>
                                <input type="number"
                                       class="form-control <?php $__errorArgs = ['captcha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="captcha"
                                       name="captcha"
                                       required
                                       placeholder="Calculate result">
                                <?php $__errorArgs = ['captcha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label small text-secondary" for="remember">
                                        Remember me
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login-v2 w-100 mb-3 fs-6">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In to Portal
                            </button>

                            <div class="text-center my-3 position-relative">
                                <span class="text-muted small px-3 bg-white rounded-pill border" style="font-size: 0.75rem;">OR</span>
                            </div>

                            <a href="<?php echo e(route('auth.google')); ?>" class="btn btn-outline-dark rounded-pill w-100 fw-semibold py-2.5 small d-flex align-items-center justify-content-center">
                                <i class="fab fa-google text-danger me-2"></i>Login with Google
                            </a>
                        </form>
                    </div>

                </div>

                
                <div class="mt-4 text-center text-white-50 small">
                    <?php echo \Sohaib\CmsCore\ProtectionService::renderCredits(); ?>
                </div>

            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/auth/login.blade.php ENDPATH**/ ?>