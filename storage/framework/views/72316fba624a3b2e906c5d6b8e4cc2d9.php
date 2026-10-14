      <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title'); ?> - GGC CTN</title>
    
    <?php
        $settings = \App\Models\InstituteSetting::getSettings();
    ?>
    <?php if($settings->hasLogo()): ?>
        <link rel="icon" type="image/png" href="<?php echo e($settings->logo_url); ?>">
        <link rel="shortcut icon" type="image/png" href="<?php echo e($settings->logo_url); ?>">
    <?php endif; ?>
    
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
    
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    
    <link href="<?php echo e(asset('css/custom.css')); ?>?v=<?php echo e(filemtime(public_path('css/custom.css'))); ?>" rel="stylesheet">

    
    <?php
        $colors = \App\Models\InstituteSetting::getColors();
    ?>
    <style>
    :root {
        --col-primary: <?php echo e($colors['primary']); ?>;
        --col-accent: <?php echo e($colors['secondary']); ?>;
        --col-secondary: <?php echo e($colors['secondary']); ?>;
        --col-highlight: <?php echo e($colors['highlight']); ?>;
        --col-success: <?php echo e($colors['success']); ?>;
        
        /* Update semantic mappings */
        --primary-color: var(--col-primary);
        --secondary-color: var(--col-accent);
        --col-sidebar-bg: var(--col-primary);
        --col-hover: color-mix(in srgb, <?php echo e($colors['primary']); ?> 80%, white);
        --col-active: color-mix(in srgb, <?php echo e($colors['primary']); ?> 70%, black);
        --col-dark: color-mix(in srgb, <?php echo e($colors['primary']); ?> 60%, black);
        --col-mint: color-mix(in srgb, <?php echo e($colors['secondary']); ?> 50%, white);
        --col-light-bg: color-mix(in srgb, <?php echo e($colors['secondary']); ?> 30%, white);
        --col-info: <?php echo e($colors['secondary']); ?>;
    }

    /* Override Bootstrap primary color classes */
    .bg-primary { background-color: var(--col-primary) !important; }
    .text-primary { color: var(--col-primary) !important; }
    .btn-primary {
        background-color: var(--col-primary) !important;
        border-color: var(--col-primary) !important;
    }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
        background-color: color-mix(in srgb, var(--col-primary) 85%, black) !important;
        border-color: color-mix(in srgb, var(--col-primary) 85%, black) !important;
    }
    .btn-outline-primary {
        color: var(--col-primary) !important;
        border-color: var(--col-primary) !important;
    }
    .btn-outline-primary:hover {
        background-color: var(--col-primary) !important;
        color: #fff !important;
    }
    .border-primary { border-color: var(--col-primary) !important; }

    .navbar { background-color: var(--col-primary) !important; }
    .navbar.scrolled {
        background-color: rgba(0,0,0,0.6) !important;
        backdrop-filter: blur(16px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
        box-shadow: 0 4px 30px rgba(0,0,0,0.3) !important;
    }
    .public-footer { 
        background: linear-gradient(180deg, color-mix(in srgb, <?php echo e($colors['primary']); ?> 25%, #071322) 0%, #030811 100%) !important;
    }
    .footer-wave-path {
        fill: color-mix(in srgb, <?php echo e($colors['primary']); ?> 25%, #071322);
    }
    .public-footer::before {
        background: linear-gradient(90deg, <?php echo e($colors['highlight']); ?>, <?php echo e($colors['secondary']); ?>, <?php echo e($colors['highlight']); ?>) !important;
    }

    .hero-section {
        background-size: cover !important;
        background-position: center !important;
    }

    /* Section title underline */
    .section-title::after { background-color: <?php echo e($colors['secondary']); ?>; }

    /* Nav link hover */
    .nav-link:hover, .nav-link.active { color: <?php echo e($colors['highlight']); ?> !important; }
    .nav-link::after { background-color: <?php echo e($colors['highlight']); ?>; }

    /* Back to top */
    #backToTop { background: var(--col-primary); }
    #backToTop:hover { background: color-mix(in srgb, var(--col-primary) 80%, black); }

    /* Scrollable Dropdown for Departments */
    .scrollable-dropdown {
        max-height: 250px;
        overflow-y: auto;
        min-width: 200px;
    }
    
    /* Custom Scrollbar for better aesthetics */
    .scrollable-dropdown::-webkit-scrollbar {
        width: 6px;
    }
    .scrollable-dropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .scrollable-dropdown::-webkit-scrollbar-thumb {
        background: var(--col-accent);
        border-radius: 10px;
    }
    .scrollable-dropdown::-webkit-scrollbar-thumb:hover {
        background: var(--col-primary);
    }

    /* Public login button */
    .btn-public-login {
        background-color: <?php echo e($colors['highlight']); ?> !important;
        color: <?php echo e($colors['primary']); ?> !important;
    }

    /* Form focus */
    .form-control:focus, .form-select:focus {
        border-color: <?php echo e($colors['secondary']); ?>;
        box-shadow: 0 0 0 0.25rem rgba(<?php echo e(intval(hexdec(substr($colors['secondary'], 1, 2)))); ?>, <?php echo e(intval(hexdec(substr($colors['secondary'], 3, 2)))); ?>, <?php echo e(intval(hexdec(substr($colors['secondary'], 5, 2)))); ?>, 0.2);
    }
    </style>
</head>
<body>

    
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo e(route('home')); ?>">
                <?php
                    $settings = \App\Models\InstituteSetting::getSettings();
                ?>
                <?php if($settings->hasLogo()): ?>
                    <img src="<?php echo e($settings->logo_url); ?>" alt="Logo" style="height: 50px; margin-right: 10px;">
                <?php else: ?>
                    <i class="fas fa-graduation-cap fa-2x fa-fw me-2 text-warning"></i>
                <?php endif; ?>
                <div>
                    <div style="line-height: 1; font-size: 1.2rem;"><?php echo e(strtoupper($settings->short_name ?? 'GOVT. GRADUATE COLLEGE')); ?></div>
                    <div style="font-size: 0.8rem; font-family: 'Outfit', sans-serif; font-weight: 400; color: var(--secondary-color);"><?php echo e(strtoupper($settings->institute_location ?? 'CHISHTIAN')); ?></div>
                </div>
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">      
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo e(request()->routeIs('public.department') ? 'active' : ''); ?>" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Departments
                        </a>
                        <ul class="dropdown-menu border-0 shadow-lg scrollable-dropdown" aria-labelledby="navbarDropdown">
                            <?php
                                $departments = \App\Models\Subject::whereHas('staffSubjectSpecializations.staff', function($q) {
                                    $q->where('status', 'active');
                                })->orderBy('name')->get();
                            ?>
                            
                            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><a class="dropdown-item py-2" href="<?php echo e(route('public.department', $dept->id)); ?>"><?php echo e($dept->name); ?></a></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            
                            <?php if($departments->isEmpty()): ?>
                                <li><span class="dropdown-item text-muted">No active departments</span></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->routeIs('public.principals') ? 'active' : ''); ?>" href="<?php echo e(route('public.principals')); ?>">Principals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->routeIs('public.gallery') ? 'active' : ''); ?>" href="<?php echo e(route('public.gallery')); ?>">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->routeIs('public.students-corner') ? 'active' : ''); ?>" href="<?php echo e(route('public.students-corner')); ?>">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->routeIs('public.programs') ? 'active' : ''); ?>" href="<?php echo e(route('public.programs')); ?>">Programs</a>
                    </li>
                     <li class="nav-item ms-lg-2">
                        <a class="nav-link <?php echo e(request()->routeIs('public.contact') ? 'active' : ''); ?>" href="<?php echo e(route('public.contact')); ?>">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->routeIs('apply.status') ? 'active' : ''); ?>" href="<?php echo e(route('apply.status')); ?>">Status</a>
                    </li>
                    <?php if(auth()->guard()->check()): ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-primary rounded-pill fw-bold px-3 hover-scale text-white" href="<?php echo e(route('dashboard')); ?>">Dashboard</a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <form action="<?php echo e(route('logout')); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-outline-light rounded-pill fw-bold px-3 hover-scale">Logout</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-primary rounded-pill fw-bold px-4 hover-scale text-white" href="<?php echo e(route('login')); ?>">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    
    <button id="backToTop" title="Back to top" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    
    <main>
        <div class="container mt-3">
            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(session('info')): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i> <?php echo e(session('info')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <?php echo $__env->yieldContent('content'); ?>
    </main>

    
    <div class="footer-wave-container">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path class="footer-wave-path" d="M0,32L48,42.7C96,53,192,75,288,80C384,85,480,75,576,58.7C672,43,768,21,864,21.3C960,21,1056,43,1152,53.3C1248,64,1344,64,1392,64L1440,64L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
        </svg>
    </div>

    
    <footer class="public-footer">
        <div class="footer-ambient-glow glow-1"></div>
        <div class="footer-ambient-glow glow-2"></div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row g-4 g-lg-5">
                
                <div class="col-lg-4 col-md-6">
                    <?php
                        $settings = \App\Models\InstituteSetting::getSettings();
                    ?>
                    <div class="footer-brand mb-3">
                        <div class="d-flex align-items-center mb-3">
                            <?php if($settings->hasLogo()): ?>
                                <div class="footer-logo-wrap me-3">
                                    <img src="<?php echo e($settings->logo_url); ?>" alt="Logo" class="footer-logo-img">
                                </div>
                            <?php else: ?>
                                <div class="footer-logo-badge me-3">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h4 class="footer-brand-name mb-0"><?php echo e($settings->full_name ?? $settings->institute_title ?? 'Govt. Graduate College'); ?></h4>
                                <span class="footer-brand-sub"><?php echo e(strtoupper($settings->institute_location ?? 'Chishtian')); ?></span>
                            </div>
                        </div>
                    </div>

                    <p class="footer-motto mb-4">
                        <?php echo e($settings->motto ?? 'Empowering students with quality education and moral values. We are committed to academic excellence and holistic student development.'); ?>

                    </p>

                    <?php if($settings->reg_number || $settings->ems_code): ?>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php if($settings->reg_number): ?>
                                <span class="badge footer-info-pill"><i class="fas fa-certificate me-1 text-warning"></i> Reg: <?php echo e($settings->reg_number); ?></span>
                            <?php endif; ?>
                            <?php if($settings->ems_code): ?>
                                <span class="badge footer-info-pill"><i class="fas fa-barcode me-1 text-info"></i> EMS: <?php echo e($settings->ems_code); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="footer-social-row">
                        <span class="footer-social-label">Connect With Us</span>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <?php if($settings->facebook_url): ?>
                                <a href="<?php echo e($settings->facebook_url); ?>" target="_blank" class="footer-social-btn social-fb" title="Facebook" aria-label="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            <?php endif; ?>
                            <?php if($settings->twitter_url): ?>
                                <a href="<?php echo e($settings->twitter_url); ?>" target="_blank" class="footer-social-btn social-tw" title="Twitter" aria-label="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif; ?>
                            <?php if($settings->instagram_url): ?>
                                <a href="<?php echo e($settings->instagram_url); ?>" target="_blank" class="footer-social-btn social-ig" title="Instagram" aria-label="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>
                            <?php if($settings->whatsapp_number): ?>
                                <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $settings->whatsapp_number)); ?>" target="_blank" class="footer-social-btn social-wa" title="WhatsApp" aria-label="WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            <?php endif; ?>
                            <?php if($settings->google_map_url): ?>
                                <a href="<?php echo e($settings->google_map_url); ?>" target="_blank" class="footer-social-btn social-map" title="Campus Map" aria-label="Campus Map">
                                    <i class="fas fa-map-marked-alt"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                
                <div class="col-lg-2 col-md-6 col-6">
                    <h5 class="footer-title">
                        <span>Quick Links</span>
                        <span class="footer-title-line"></span>
                    </h5>
                    <ul class="footer-links-list list-unstyled">
                        <li><a href="<?php echo e(route('home')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Home</a></li>
                        <li><a href="<?php echo e(route('public.staff')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Faculty Roster</a></li>
                        <li><a href="<?php echo e(route('public.duty_roster_lookup')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Duty Roster</a></li>
                        <li><a href="<?php echo e(route('public.downloads')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Downloads</a></li>
                        <li><a href="<?php echo e(route('apply.status')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Status Check</a></li>
                        <li><a href="<?php echo e(route('login')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Staff Portal</a></li>
                    </ul>
                </div>

                
                <div class="col-lg-3 col-md-6 col-6">
                    <h5 class="footer-title">
                        <span>Academic Life</span>
                        <span class="footer-title-line"></span>
                    </h5>
                    <ul class="footer-links-list list-unstyled">
                        <li><a href="<?php echo e(route('public.programs')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Programs</a></li>
                        <li><a href="<?php echo e(route('public.news')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> News & Updates</a></li>
                        <li><a href="<?php echo e(route('public.gallery')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Campus Gallery</a></li>
                        <li><a href="<?php echo e(route('public.students-corner')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Student Corner</a></li>
                        <li><a href="<?php echo e(route('public.principals')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Roll of Honor</a></li>
                        <li><a href="<?php echo e(route('public.contact')); ?>" class="footer-link"><i class="fas fa-chevron-right link-icon"></i> Contact Us</a></li>
                    </ul>
                </div>

                
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title">
                        <span>Contact Info</span>
                        <span class="footer-title-line"></span>
                    </h5>
                    <div class="footer-contact-card">
                        <?php if($settings->address): ?>
                            <div class="footer-contact-item">
                                <div class="contact-icon-box text-warning">
                                    <i class="fas fa-location-dot"></i>
                                </div>
                                <div class="contact-text">
                                    <span class="contact-label">Location</span>
                                    <span class="contact-val"><?php echo e($settings->address); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($settings->contact_number || $settings->landline): ?>
                            <div class="footer-contact-item">
                                <div class="contact-icon-box text-info">
                                    <i class="fas fa-phone-volume"></i>
                                </div>
                                <div class="contact-text">
                                    <span class="contact-label">Phone Inquiry</span>
                                    <a href="tel:<?php echo e($settings->contact_number ?? $settings->landline); ?>" class="contact-val contact-link"><?php echo e($settings->contact_number ?? $settings->landline); ?></a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($settings->email): ?>
                            <div class="footer-contact-item">
                                <div class="contact-icon-box text-success">
                                    <i class="fas fa-envelope-open-text"></i>
                                </div>
                                <div class="contact-text me-0 pe-0 style-truncate" style="min-width:0;">
                                    <span class="contact-label">Email Us</span>
                                    <a href="mailto:<?php echo e($settings->email); ?>" class="contact-val contact-link text-truncate d-block"><?php echo e($settings->email); ?></a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($settings->whatsapp_number): ?>
                            <div class="footer-contact-item">
                                <div class="contact-icon-box text-success">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <div class="contact-text">
                                    <span class="contact-label">WhatsApp</span>
                                    <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $settings->whatsapp_number)); ?>" target="_blank" class="contact-val contact-link"><?php echo e($settings->whatsapp_number); ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="footer-bottom mt-5 pt-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-7 text-center text-md-start text-white-50 fs-7">
                        <?php echo \Sohaib\CmsCore\ProtectionService::renderCredits(); ?>
                    </div>
                    <div class="col-md-5 text-center text-md-end">
                        <div class="footer-bottom-links d-inline-flex align-items-center gap-3">
                            <span class="badge rounded-pill bg-white-10 text-white-50 px-3 py-2 border border-white-10 fs-8">
                                <i class="fas fa-shield-alt text-warning me-1"></i> Official Institution Portal
                            </span>
                            <a href="#top" onclick="window.scrollTo({top:0, behavior:'smooth'}); return false;" class="btn-footer-top" title="Back to top">
                                <span>Top</span> <i class="fas fa-arrow-up ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.8.0/countUp.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.1.0/dist/typed.umd.js"></script>
    <script>
        // AOS
        AOS.init({ duration: 800, once: true });

        // Glassmorphism navbar on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                if (window.scrollY > 60) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });

        // Back to top button
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 400) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });
            backToTop.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    </script>
    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/layouts/public.blade.php ENDPATH**/ ?>