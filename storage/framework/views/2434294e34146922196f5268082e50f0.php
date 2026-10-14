<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', config('app.name', 'College Management System')); ?></title>

    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">

    
    <link href="<?php echo e(asset('css/custom.css')); ?>?v=<?php echo e(filemtime(public_path('css/custom.css'))); ?>" rel="stylesheet">


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <?php echo $__env->yieldContent('styles'); ?>
</head>
<body class="<?php echo $__env->yieldContent('body-class'); ?>">

    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="<?php echo e(route('dashboard')); ?>" class="d-flex align-items-center text-white text-decoration-none">
                <?php
                    $settings = \App\Models\InstituteSetting::getSettings();
                ?>
                <?php if($settings->hasLogo()): ?>
                    <img src="<?php echo e($settings->logo_url); ?>" alt="Logo">
                <?php else: ?>
                    <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
                <span><?php echo e($settings->institute_title ?? 'College MS'); ?></span>
            </a>
            <button class="btn-close btn-close-white ms-auto d-md-none" id="mobileSidebarClose" aria-label="Close"></button>
        </div>

        <?php
            $user = Auth::user();
            $userRoles = $user ? $user->roles : collect();
            $activeRole = session('active_role');
            if (!$activeRole && Auth::check()) {
                if ($user->is_super_admin) {
                    $activeRole = $userRoles->contains('name', 'super_admin') ? 'super_admin' : 'admin';
                } elseif ($userRoles->isNotEmpty()) {
                    // Default priority fallback if session is empty
                    if ($userRoles->contains('name', 'admin')) $activeRole = 'admin';
                    elseif ($userRoles->contains('name', 'principal')) $activeRole = 'principal';
                    elseif ($userRoles->contains('name', 'super_admin')) $activeRole = 'super_admin';
                    elseif ($userRoles->contains('name', 'teacher')) $activeRole = 'teacher';
                    else $activeRole = $userRoles->first()->name;
                }
                session(['active_role' => $activeRole]);
            }

            // Get sidebar view from session, default to HSSC
            $sidebarView = session('sidebar_view', 'HSSC');
            
            // For BS/ADP specific roles, force BS view if not set
            if (in_array($activeRole, ['bs_clerk', 'bs_admin', 'bs_accountant']) && !session()->has('sidebar_view')) {
                $sidebarView = 'BS';
                session(['sidebar_view' => 'BS']);
            }
            
            // For HSSC specific roles, force HSSC view
            if (in_array($activeRole, ['hssc_clerk', 'hssc_admin', 'hssc_accountant']) && !session()->has('sidebar_view')) {
                $sidebarView = 'HSSC';
                session(['sidebar_view' => 'HSSC']);
            }

            $isSpecialAdmin = ($user && $user->is_super_admin) || in_array($activeRole, ['admin', 'principal']);
        ?>

        <div class="px-3 mb-3">
            <?php if($isSpecialAdmin): ?>
                <div class="btn-group w-100 shadow-sm" role="group">
                    <form action="<?php echo e(route('switch.view')); ?>" method="POST" class="w-50">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="view" value="HSSC">
                        <button type="submit" class="btn btn-sm w-100 <?php echo e($sidebarView == 'HSSC' ? 'btn-primary' : 'btn-outline-primary'); ?>" style="border-radius: 5px 0 0 5px;">
                            HSSC
                        </button>
                    </form>
                    <form action="<?php echo e(route('switch.view')); ?>" method="POST" class="w-50">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="view" value="BS">
                        <button type="submit" class="btn btn-sm w-100 <?php echo e($sidebarView == 'BS' ? 'btn-primary' : 'btn-outline-primary'); ?>" style="border-radius: 0 5px 5px 0;">
                            ADP / BS
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar-search-container">
            <input type="text" id="sidebarSearch" class="sidebar-search" placeholder="Search menu...">
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-header">Quick Access</li>
            <li>
                <a href="<?php echo e(route('dashboard')); ?>" class="<?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>


            
            <?php if($sidebarView == 'HSSC'): ?>
                <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'accountant', 'hssc_accountant'])): ?>
                    
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['students.*', 'bise-roll-numbers.*', 'staff.student_assignment', 'student-affairs.*']) ? 'active' : ''); ?>" data-target="students-menu">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student affairs</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="students-menu">
                            <li><a href="<?php echo e(route('students.index')); ?>" class="<?php echo e(request()->routeIs('students.index') ? 'active' : ''); ?>">
                                <?php echo e($activeRole == 'teacher' ? 'My Students' : 'HSSC Students'); ?>

                            </a></li>
                            <?php if($activeRole != 'teacher'): ?>
                                <li><a href="<?php echo e(route('students.create')); ?>" class="<?php echo e(request()->routeIs('students.create') ? 'active' : ''); ?>">Add New</a></li>
                                <li><a href="<?php echo e(route('student-affairs.sections.index')); ?>" class="<?php echo e(request()->routeIs('student-affairs.sections.*') ? 'active' : ''); ?>">Sections</a></li>
                                <li><a href="<?php echo e(route('staff.student_assignment')); ?>" class="<?php echo e(request()->routeIs('staff.student_assignment') ? 'active' : ''); ?>">Assign Students</a></li>
                                <li><a href="<?php echo e(route('bise-roll-numbers.index')); ?>" class="<?php echo e(request()->routeIs('bise-roll-numbers.*') ? 'active' : ''); ?>">Add BISE Roll No.</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    
                    <li>
                        <a href="#" class="menu-toggle <?php echo e((request()->routeIs('staff.*') && !request()->routeIs(['staff.student_assignment', 'staff.award_list.*'])) || request()->routeIs('reports.staff-attendance.*') ? 'active' : ''); ?>" data-target="staff-menu">
                            <i class="fas fa-user-tie"></i>
                            <span>Staff Details</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="staff-menu">
                            <li><a href="<?php echo e(route('staff.index')); ?>" class="<?php echo e(request()->routeIs('staff.index') ? 'active' : ''); ?>">All Staff</a></li>
                            <?php if($activeRole != 'teacher'): ?>
                                <li><a href="<?php echo e(route('staff.create')); ?>" class="<?php echo e(request()->routeIs('staff.create') ? 'active' : ''); ?>">Add New</a></li>
                                <li><a href="<?php echo e(route('reports.staff-attendance.index')); ?>" class="<?php echo e(request()->routeIs('reports.staff-attendance.*') ? 'active' : ''); ?>">Attendance Reports</a></li>
                            <?php endif; ?>
                            <?php if(in_array($activeRole, ['admin', 'principal', 'super_admin']) || (isset($user) && $user->is_super_admin)): ?>
                                <li><a href="<?php echo e(route('leave-approvals.index')); ?>" class="<?php echo e(request()->routeIs('leave-approvals.*') ? 'active' : ''); ?>"><i class="fas fa-check-circle text-success me-1"></i> Leave Approvals</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    
                    <?php if($activeRole != 'teacher'): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['datesheets.*', 'seating-plans.*', 'attendance.gate.*', 'test-attendance.*', 'staff.award_list.*']) ? 'active' : ''); ?>" data-target="exams-menu">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Examination</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="exams-menu">
                            <li><a href="<?php echo e(route('datesheets.index')); ?>" class="<?php echo e(request()->routeIs('datesheets.*') ? 'active' : ''); ?>">Datesheets</a></li>
                            <li><a href="<?php echo e(route('staff.award_list.form')); ?>" class="<?php echo e(request()->routeIs('staff.award_list.*') ? 'active' : ''); ?>">Award List</a></li>
                            <li><a href="<?php echo e(route('seating-plans.index')); ?>" class="<?php echo e(request()->routeIs('seating-plans.*') ? 'active' : ''); ?>">Seating Plans</a></li>
                            <li><a href="<?php echo e(route('attendance.gate.index')); ?>" class="<?php echo e(request()->routeIs('attendance.gate.*') ? 'active' : ''); ?>">Gate Attendance Scanner</a></li>
                            <li><a href="<?php echo e(route('test-attendance.create')); ?>" class="<?php echo e(request()->routeIs('test-attendance.*') ? 'active' : ''); ?>">Mark Test Attendance</a></li>
                        </ul>
                    </li>

                    
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs('invigilators.*') ? 'active' : ''); ?>" data-target="invigilators-menu">
                            <i class="fas fa-user-shield"></i>
                            <span>Invigilators</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="invigilators-menu">
                            <li><a href="<?php echo e(route('invigilators.index')); ?>" class="<?php echo e(request()->routeIs('invigilators.index') ? 'active' : ''); ?>">Assignments</a></li>
                            <li><a href="<?php echo e(route('invigilators.assign')); ?>" class="<?php echo e(request()->routeIs('invigilators.assign') ? 'active' : ''); ?>">Assign by Date</a></li>
                            <li><a href="<?php echo e(route('invigilators.duty_roster')); ?>" class="<?php echo e(request()->routeIs('invigilators.duty_roster') ? 'active' : ''); ?>">Print Duty Roster</a></li>
                            <li><a href="<?php echo e(route('invigilators.create')); ?>" class="<?php echo e(request()->routeIs('invigilators.create') ? 'active' : ''); ?>">Auto-Assign</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="<?php echo e(route('invigilators.duty_rules')); ?>" class="<?php echo e(request()->routeIs('invigilators.duty_rules') ? 'active' : ''); ?>">Duty Rules</a></li>
                            <li><a href="<?php echo e(route('invigilators.exemptions')); ?>" class="<?php echo e(request()->routeIs('invigilators.exemptions') ? 'active' : ''); ?>">Exemptions</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <li>
                        <a href="#" class="menu-toggle <?php echo e((request()->routeIs('results.*') && !request()->routeIs('results.final.view')) || request()->routeIs('results.create') ? 'active' : ''); ?>" data-target="results-menu">
                            <i class="fas fa-trophy"></i>
                            <span>Results</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="results-menu">
                            <?php if($activeRole != 'teacher'): ?>
                                <li><a href="<?php echo e(route('results.index')); ?>" class="<?php echo e(request()->routeIs('results.index') ? 'active' : ''); ?>">All Results</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo e(route('results.create')); ?>" class="<?php echo e(request()->routeIs('results.create') ? 'active' : ''); ?>">Add Result</a></li>
                            <?php if($activeRole != 'teacher'): ?>
                                <li><a href="<?php echo e(route('results.11th.create')); ?>" class="<?php echo e(request()->routeIs('results.11th.create') ? 'active' : ''); ?>">Add 11th Result</a></li>
                                <li><a href="<?php echo e(route('results.final.create')); ?>" class="<?php echo e(request()->routeIs('results.final.create') ? 'active' : ''); ?>">Add Final Result</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    
                    <?php if($activeRole != 'teacher'): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e((request()->routeIs('reports.*') && !request()->routeIs('reports.staff-attendance.*')) || request()->routeIs(['results.final.view', 'attendance.reports.*']) ? 'active' : ''); ?>" data-target="reports-menu">
                            <i class="fas fa-chart-pie"></i>
                            <span>Custom Reports</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="reports-menu">
                            <li><a href="<?php echo e(route('reports.subject-enrollment.index')); ?>" class="<?php echo e(request()->routeIs('reports.subject-enrollment.*') ? 'active' : ''); ?>">Subject Enrollment</a></li>
                            <li><a href="<?php echo e(route('reports.cash-register.index')); ?>" class="<?php echo e(request()->routeIs('reports.cash-register.*') ? 'active' : ''); ?>">Cash Register</a></li>
                            <li><a href="<?php echo e(route('reports.students-without-teacher.index')); ?>" class="<?php echo e(request()->routeIs('reports.students-without-teacher.*') ? 'active' : ''); ?>">Students Without Teacher</a></li>
                            <li><a href="<?php echo e(route('results.final.view')); ?>" class="<?php echo e(request()->routeIs('results.final.view') ? 'active' : ''); ?>">View Final Results</a></li>
                            <li><a href="<?php echo e(route('reports.final-result-report.index')); ?>" class="<?php echo e(request()->routeIs('reports.final-result-report.*') ? 'active' : ''); ?>">Final Result Report</a></li>
                            <li><a href="<?php echo e(route('reports.test-result-report.index')); ?>" class="<?php echo e(request()->routeIs('reports.test-result-report.*') ? 'active' : ''); ?>">Test Result Report</a></li>
                            <li><a href="<?php echo e(route('reports.detailed-test-result-report.index')); ?>" class="<?php echo e(request()->routeIs('reports.detailed-test-result-report.*') ? 'active' : ''); ?>">Detailed Test Result Report</a></li>
                            <li><a href="<?php echo e(route('reports.detailed-final-result-report.index')); ?>" class="<?php echo e(request()->routeIs('reports.detailed-final-result-report.*') ? 'active' : ''); ?>">Detailed Final Result Report</a></li>
                            <li><a href="<?php echo e(route('reports.test-attendance-report')); ?>" class="<?php echo e(request()->routeIs('reports.test-attendance-report') ? 'active' : ''); ?>">Test Attendance Report</a></li>
                            <li><a href="<?php echo e(route('attendance.reports.form')); ?>" class="<?php echo e(request()->routeIs('attendance.reports.form') ? 'active' : ''); ?>">Daily Attendance Report</a></li>
                            <?php if(in_array($activeRole, ['admin', 'principal', 'super_admin']) || (isset($user) && $user->is_super_admin)): ?>
                                <li><a href="<?php echo e(route('reports.teacher_attendance_status')); ?>" class="<?php echo e(request()->routeIs('reports.teacher_attendance_status') ? 'active' : ''); ?>"><i class="fas fa-clipboard-check text-warning me-1"></i> Teacher Attendance Status</a></li>
                                <li><a href="<?php echo e(route('reports.teacher_workload')); ?>" class="<?php echo e(request()->routeIs('reports.teacher_workload') ? 'active' : ''); ?>"><i class="fas fa-briefcase text-info me-1"></i> Teacher Workload Report</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo e(route('attendance.reports.whole_year.form')); ?>" class="<?php echo e(request()->routeIs('attendance.reports.whole_year.*') ? 'active' : ''); ?>">Whole-Year Attendance Report</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['admission-applications.*', 'fee-heads.*', 'subject-rules.*', 'admissions.settings.*']) ? 'active' : ''); ?>" data-target="admissions-menu">
                            <i class="fas fa-file-alt"></i>
                            <span>Admissions</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="admissions-menu">
                            <li><a href="<?php echo e(route('admission-applications.index')); ?>" class="<?php echo e(request()->routeIs('admission-applications.index') ? 'active' : ''); ?>">All Applications</a></li>
                            <li><a href="<?php echo e(route('admission-applications.create')); ?>" class="<?php echo e(request()->routeIs('admission-applications.create') ? 'active' : ''); ?>">New Walk-in</a></li>
                            <li><a href="<?php echo e(route('admission-applications.merit-list')); ?>" class="<?php echo e(request()->routeIs('admission-applications.merit-list') ? 'active' : ''); ?>">Merit List</a></li>
                            <?php if($user->is_super_admin || in_array($activeRole, ['admin', 'principal'])): ?>
                                <li style="border-top: 1px dotted #444; margin: 5px 0;"></li>
                                <li><a href="<?php echo e(route('fee-heads.index')); ?>" class="<?php echo e(request()->routeIs('fee-heads.*') ? 'active' : ''); ?>">Fee Heads</a></li>
                                <li><a href="<?php echo e(route('subject-rules.index')); ?>" class="<?php echo e(request()->routeIs('subject-rules.*') ? 'active' : ''); ?>">Subject Rules</a></li>
                                <li><a href="<?php echo e(route('admissions.settings.index')); ?>" class="<?php echo e(request()->routeIs('admissions.settings.*') ? 'active' : ''); ?>">Admission Settings</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    
                    <?php if($activeRole != 'teacher'): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs('vouchers.*') ? 'active' : ''); ?>" data-target="vouchers-menu">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Fee vouchers</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="vouchers-menu">
                            <li><a href="<?php echo e(route('vouchers.index')); ?>" class="<?php echo e(request()->routeIs('vouchers.index') ? 'active' : ''); ?>">All Vouchers</a></li>
                            <?php if (! (in_array($activeRole, ['bs_clerk', 'bs_admin', 'bs_accountant']))): ?>
                                <li><a href="<?php echo e(route('vouchers.bulk-class12')); ?>" class="<?php echo e(request()->routeIs('vouchers.bulk-class12') ? 'active' : ''); ?>">Bulk Class 12</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>


                    
                    <?php if($activeRole != 'teacher'): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['subject.*', 'bise.*', 'rooms.*', 'timetables.*', 'designations.*', 'quota.*', 'bloodGroup.*', 'subject-groups.*']) ? 'active' : ''); ?>" data-target="academic-menu">
                            <i class="fas fa-university"></i>
                            <span>Academic Info</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="academic-menu">
                            <li><a href="<?php echo e(route('rooms.index')); ?>" class="<?php echo e(request()->routeIs('rooms.*') ? 'active' : ''); ?>">Rooms & Blocks</a></li>
                             <li><a href="<?php echo e(route('subject.index')); ?>" class="<?php echo e(request()->routeIs('subject.*') ? 'active' : ''); ?>">Subject Catalog</a></li>
                            <li><a href="<?php echo e(route('subject-groups.index')); ?>" class="<?php echo e(request()->routeIs('subject-groups.*') ? 'active' : ''); ?>">Subject Groups</a></li>
                            <li><a href="<?php echo e(route('admin.timetables.index')); ?>" class="<?php echo e(request()->routeIs('admin.timetables.*') ? 'active' : ''); ?>">Class Timetables</a></li>
                            <li><a href="<?php echo e(route('time-slots.index')); ?>" class="<?php echo e(request()->routeIs('time-slots.*') ? 'active' : ''); ?>">Time Slots</a></li>
                            <li><a href="<?php echo e(route('bise.biseindex')); ?>" class="<?php echo e(request()->routeIs('bise.*') ? 'active' : ''); ?>">BISE Records</a></li>
                            <li><a href="<?php echo e(route('designations.index')); ?>" class="<?php echo e(request()->routeIs('designations.*') ? 'active' : ''); ?>">Designations</a></li>
                            <li><a href="<?php echo e(route('quota.index')); ?>" class="<?php echo e(request()->routeIs('quota.*') ? 'active' : ''); ?>">Quota Settings</a></li>
                            <li><a href="<?php echo e(route('bloodGroup.index')); ?>" class="<?php echo e(request()->routeIs('bloodGroup.*') ? 'active' : ''); ?>">Blood Groups</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->is(['admin/assets*', 'admin/budgets*', 'admin/transport*', 'admin/hostel*', 'admin/alumni*']) ? 'active' : ''); ?>" data-target="hssc-ops">
                            <i class="fas fa-truck-loading"></i>
                            <span>Logistics & Alumni</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="hssc-ops">
                            <li><a href="<?php echo e(route('assets.index')); ?>" class="<?php echo e(request()->routeIs('assets.index') ? 'active' : ''); ?>">Asset Inventory</a></li>
                            <li><a href="<?php echo e(route('assets.categories')); ?>" class="<?php echo e(request()->routeIs('assets.categories') ? 'active' : ''); ?>">Asset Categories</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="<?php echo e(route('budgets.index')); ?>" class="<?php echo e(request()->routeIs('budgets.index') ? 'active' : ''); ?>">Budget Planning</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="<?php echo e(route('transport.index')); ?>" class="<?php echo e(request()->routeIs('transport.index') ? 'active' : ''); ?>">Transport Fleet</a></li>
                            <li><a href="<?php echo e(route('hostel.index')); ?>" class="<?php echo e(request()->routeIs('hostel.index') ? 'active' : ''); ?>">Hostel Mgmt</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="<?php echo e(route('alumni.index')); ?>" class="<?php echo e(request()->routeIs('alumni.index') ? 'active' : ''); ?>">Alumni Directory</a></li>
                            <li><a href="<?php echo e(route('alumni.batches')); ?>" class="<?php echo e(request()->routeIs('alumni.batches') ? 'active' : ''); ?>">Graduation Batches</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                
                <?php if($activeRole == 'teacher'): ?>
                <li class="sidebar-heading">Personal Spaces</li>
                <li>
                    <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['attendance.*', 'he.attendance.*', 'he.results.*']) ? 'active' : ''); ?>" data-target="teacher-tasks-menu">
                        <i class="fas fa-tasks"></i>
                        <span>Academic Tasks</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="teacher-tasks-menu">
                        
                        <li class="submenu-header text-muted small ps-3 mt-2 Review ps-3 mt-2 mb-1">HSSC</li>
                        <li><a href="<?php echo e(route('attendance.index')); ?>" class="<?php echo e(request()->routeIs('attendance.index') ? 'active' : ''); ?>">Mark Attendance</a></li>
                        <li><a href="<?php echo e(route('attendance.reports.form')); ?>" class="<?php echo e(request()->routeIs('attendance.reports.*') ? 'active' : ''); ?>">Attendance History</a></li>
                        
                        
                        <li class="submenu-header text-muted small ps-3 mt-2 mb-1">ADP / BS</li>
                        <li><a href="<?php echo e(route('he.attendance.index')); ?>" class="<?php echo e(request()->routeIs('he.attendance.index') ? 'active' : ''); ?>">Mark HE Attendance</a></li>
                        <li><a href="<?php echo e(route('he.results.create')); ?>" class="<?php echo e(request()->routeIs('he.results.create') ? 'active' : ''); ?>">HE Results Entry</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            <?php endif; ?>

            
            <?php if($sidebarView == 'BS'): ?>
                <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'teacher', 'bs_accountant'])): ?>
                    
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['admissions.*', 'he.roll-numbers.*']) ? 'active' : ''); ?>" data-target="he-students-menu">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student affairs</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-students-menu">
                            <li><a href="<?php echo e(route('admissions.index')); ?>" class="<?php echo e(request()->routeIs('admissions.*') ? 'active' : ''); ?>">Students</a></li>
                            <li><a href="<?php echo e(route('he.roll-numbers.index')); ?>" class="<?php echo e(request()->routeIs('he.roll-numbers.*') ? 'active' : ''); ?>">University Roll Nos</a></li>
                        </ul>
                    </li>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'bs_accountant'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e((request()->routeIs('staff.*') && !request()->routeIs(['staff.student_assignment', 'staff.award_list.*'])) ? 'active' : ''); ?>" data-target="he-staff-menu">
                            <i class="fas fa-user-tie"></i>
                            <span>Staff Details</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-staff-menu">
                            <li><a href="<?php echo e(route('staff.index')); ?>" class="<?php echo e(request()->routeIs('staff.index') ? 'active' : ''); ?>">All Staff</a></li>
                            <?php if($activeRole != 'teacher'): ?>
                                <li><a href="<?php echo e(route('staff.create')); ?>" class="<?php echo e(request()->routeIs('staff.create') ? 'active' : ''); ?>">Add New</a></li>
                            <?php endif; ?>
                            <?php if(in_array($activeRole, ['admin', 'principal', 'super_admin']) || (isset($user) && $user->is_super_admin)): ?>
                                <li><a href="<?php echo e(route('leave-approvals.index')); ?>" class="<?php echo e(request()->routeIs('leave-approvals.*') ? 'active' : ''); ?>"><i class="fas fa-check-circle text-success me-1"></i> Leave Approvals</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if($activeRole != 'teacher'): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['he.teacher_assignment*', 'he.attendance.*', 'he.datesheets.*', 'he.seating_plans.*', 'he.timetables.*']) ? 'active' : ''); ?>" data-target="he-exams-menu">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Examination</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-exams-menu">
                            <?php if($activeRole == 'teacher'): ?>
                                <li><a href="<?php echo e(route('he.attendance.index')); ?>" class="<?php echo e(request()->routeIs('he.attendance.*') ? 'active' : ''); ?>">Mark Attendance</a></li>
                            <?php else: ?>
                                <li><a href="<?php echo e(route('attendance.gate.index')); ?>" class="<?php echo e(request()->routeIs('attendance.gate.*') ? 'active' : ''); ?>">Gate Attendance Scanner</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo e(route('he.datesheets.index')); ?>" class="<?php echo e(request()->routeIs('he.datesheets.*') ? 'active' : ''); ?>">Datesheets</a></li>
                            <li><a href="<?php echo e(route('he.seating_plans.index')); ?>" class="<?php echo e(request()->routeIs('he.seating_plans.*') ? 'active' : ''); ?>">Seating Plans</a></li>
                            <li><a href="<?php echo e(route('he.timetables.index')); ?>" class="<?php echo e(request()->routeIs('he.timetables.*') ? 'active' : ''); ?>">Timetables</a></li>
                            <li><a href="<?php echo e(route('he.teacher_assignment')); ?>" class="<?php echo e(request()->routeIs('he.teacher_assignment') ? 'active' : ''); ?>">Teacher Assignment</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs('he.invigilators.*') ? 'active' : ''); ?>" data-target="he-invigilators-menu">
                            <i class="fas fa-user-shield"></i>
                            <span>Invigilators</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-invigilators-menu">
                            <li><a href="<?php echo e(route('he.invigilators.index')); ?>" class="<?php echo e(request()->routeIs('he.invigilators.index') ? 'active' : ''); ?>">Assignments</a></li>
                            <li><a href="<?php echo e(route('he.invigilators.create')); ?>" class="<?php echo e(request()->routeIs('he.invigilators.create') ? 'active' : ''); ?>">Auto-Assign Duty</a></li>
                            <li><a href="<?php echo e(route('he.invigilators.assign')); ?>" class="<?php echo e(request()->routeIs('he.invigilators.assign') ? 'active' : ''); ?>">Assign by Date</a></li>
                            <li><a href="<?php echo e(route('he.invigilators.duty_roster')); ?>" class="<?php echo e(request()->routeIs('he.invigilators.duty_roster') ? 'active' : ''); ?>">Duty Roster</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'bs_clerk', 'teacher', 'bs_admin'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs('he.results.*') ? 'active' : ''); ?>" data-target="he-results-menu">
                            <i class="fas fa-poll"></i>
                            <span>Results</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-results-menu">
                            <li><a href="<?php echo e(route('he.results.index')); ?>" class="<?php echo e(request()->routeIs('he.results.index') ? 'active' : ''); ?>">Results Entry</a></li>
                            <li><a href="<?php echo e(route('he.results.summary')); ?>" class="<?php echo e(request()->routeIs('he.results.summary') ? 'active' : ''); ?>">Results Summary</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs('he.reports.*') ? 'active' : ''); ?>" data-target="he-reports-menu">
                            <i class="fas fa-chart-pie"></i>
                            <span>Custom Reports</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-reports-menu">
                            <li><a href="<?php echo e(route('he.reports.award_list.form')); ?>" class="<?php echo e(request()->routeIs('he.reports.award_list.*') ? 'active' : ''); ?>">Award Lists</a></li>
                            <li><a href="<?php echo e(route('he.reports.tabulation_sheet.form')); ?>" class="<?php echo e(request()->routeIs('he.reports.tabulation_sheet.*') ? 'active' : ''); ?>">Tabulation Sheets</a></li>
                            <li><a href="<?php echo e(route('he.reports.transcript.search')); ?>" class="<?php echo e(request()->routeIs('he.reports.transcript.*') ? 'active' : ''); ?>">Transcripts</a></li>
                            <li><a href="<?php echo e(route('he.reports.attendance.form')); ?>" class="<?php echo e(request()->routeIs('he.reports.attendance.*') ? 'active' : ''); ?>">Attendance Reports</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs('admission-applications.*') ? 'active' : ''); ?>" data-target="he-admissions-menu">
                            <i class="fas fa-door-open"></i>
                            <span>Admissions</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-admissions-menu">
                            <li><a href="<?php echo e(route('admission-applications.index')); ?>" class="<?php echo e(request()->routeIs('admission-applications.*') ? 'active' : ''); ?>">Applications</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'bs_accountant'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs('vouchers.*') ? 'active' : ''); ?>" data-target="he-vouchers-menu">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Fee vouchers</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-vouchers-menu">
                            <li><a href="<?php echo e(route('vouchers.index')); ?>" class="<?php echo e(request()->routeIs('vouchers.index') ? 'active' : ''); ?>">Voucher History</a></li>
                            <?php if (! (in_array($activeRole, ['hssc_clerk', 'hssc_admin', 'hssc_accountant']))): ?>
                                <li><a href="<?php echo e(route('vouchers.bulk-he')); ?>" class="<?php echo e(request()->routeIs('vouchers.bulk-he') ? 'active' : ''); ?>">Bulk Semester Vouchers</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin'])): ?>
                    <li>
                        <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['programs.*', 'bs-subjects.*', 'semesters.*']) ? 'active' : ''); ?>" data-target="he-academic-menu">
                            <i class="fas fa-university"></i>
                            <span>Academic Info</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-academic-menu">
                            <li><a href="<?php echo e(route('programs.index')); ?>" class="<?php echo e(request()->routeIs('programs.*') ? 'active' : ''); ?>">Programs</a></li>
                            <li><a href="<?php echo e(route('bs-subjects.index')); ?>" class="<?php echo e(request()->routeIs('bs-subjects.*') ? 'active' : ''); ?>">Subjects</a></li>
                            <li><a href="<?php echo e(route('semesters.index')); ?>" class="<?php echo e(request()->routeIs('semesters.*') ? 'active' : ''); ?>">Curriculum</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>

            
            <?php if(in_array($activeRole, ['student', 'teacher', 'admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin'])): ?>
            <li>
                <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['lms.*', 'library.*']) ? 'active' : ''); ?>" data-target="learning-blocks">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Learning & Library</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="learning-blocks">
                    <li><a href="<?php echo e(route('lms.dashboard')); ?>" class="<?php echo e(request()->routeIs('lms.dashboard') ? 'active' : ''); ?>">LMS Dashboard</a></li>
                    <li><a href="<?php echo e(route('lms.assignments.index')); ?>" class="<?php echo e(request()->routeIs('lms.assignments.*') ? 'active' : ''); ?>">Assignments</a></li>
                    <?php if(in_array($activeRole, ['admin', 'principal', 'clerk'])): ?>
                        <li><a href="<?php echo e(route('library.index')); ?>" class="<?php echo e(request()->routeIs('library.index') ? 'active' : ''); ?>">Library Home</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if($user->is_super_admin || in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin'])): ?>
            <li>
                <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['notifications.*', 'announcements.*']) ? 'active' : ''); ?>" data-target="global-comm">
                    <i class="fas fa-bullhorn"></i>
                    <span>Communication</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="global-comm">
                    <li><a href="<?php echo e(route('notifications.index')); ?>" class="<?php echo e(request()->routeIs('notifications.*') ? 'active' : ''); ?>">In-App Alerts</a></li>
                    <li><a href="<?php echo e(route('announcements.index')); ?>" class="<?php echo e(request()->routeIs('announcements.*') ? 'active' : ''); ?>">Bulk Announcements</a></li>
                </ul>
            </li>
            <?php endif; ?>

            
            <?php if(!$userRoles->contains('name', 'parent') && !$userRoles->contains('name', 'student')): ?>
            <li>
                <a href="<?php echo e(route('my-leaves.index')); ?>" class="<?php echo e(request()->routeIs('my-leaves.*') ? 'active' : ''); ?>">
                    <i class="fas fa-suitcase-rolling"></i>
                    <span>My Leaves</span>
                </a>
            </li>
            <li>
                <a href="<?php echo e(route('public.duty_roster_lookup')); ?>" class="<?php echo e(request()->routeIs('public.duty_roster_lookup') ? 'active' : ''); ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Duty Roster</span>
                </a>
            </li>
            <?php endif; ?>

            
            <?php if(in_array($activeRole, ['admin', 'principal', 'clerk'])): ?>
            <li>
                <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['albums.*', 'updates.*', 'committees.*', 'principals.*', 'settings.*', 'downloads.*', 'admin.database.audit.*']) ? 'active' : ''); ?>" data-target="website-menu">
                    <i class="fas fa-globe"></i>
                    <span>Website Setup</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="website-menu">
                    <li><a href="<?php echo e(route('albums.index')); ?>" class="<?php echo e(request()->routeIs('albums.*') ? 'active' : ''); ?>">Photo Albums</a></li>
                    <li><a href="<?php echo e(route('updates.index')); ?>" class="<?php echo e(request()->routeIs('updates.*') ? 'active' : ''); ?>">News & Updates</a></li>
                    <li><a href="<?php echo e(route('committees.index')); ?>" class="<?php echo e(request()->routeIs('committees.*') ? 'active' : ''); ?>">Committees</a></li>
                    <li><a href="<?php echo e(route('principals.index')); ?>" class="<?php echo e(request()->routeIs('principals.*') ? 'active' : ''); ?>">Principals History</a></li>
                    <li><a href="<?php echo e(route('downloads.index')); ?>" class="<?php echo e(request()->routeIs('downloads.*') ? 'active' : ''); ?>">Downloads</a></li>
                    <?php if($activeRole == 'admin' || $user->is_super_admin): ?>
                        <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                        <li><a href="<?php echo e(route('settings.edit')); ?>" class="<?php echo e(request()->routeIs('settings.edit') ? 'active' : ''); ?>">General Settings</a></li>
                        <li><a href="<?php echo e(route('settings.colors')); ?>" class="<?php echo e(request()->routeIs('settings.colors') ? 'active' : ''); ?>">Color Settings</a></li>
                        <li><a href="<?php echo e(route('admin.database.audit.index')); ?>" class="<?php echo e(request()->routeIs('admin.database.audit.*') ? 'active' : ''); ?>"><i class="fas fa-database text-warning me-1"></i> Database Audit</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            
            <?php if($user->is_super_admin || $userRoles->contains('name', 'principal')): ?>
            <li>
                <a href="#" class="menu-toggle <?php echo e(request()->routeIs(['users.*', 'role-permissions.*']) ? 'active' : ''); ?>" data-target="users-menu-parent">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="users-menu-parent">
                    <li><a href="<?php echo e(route('users.index')); ?>" class="<?php echo e(request()->routeIs('users.*') ? 'active' : ''); ?>">Manage Users</a></li>
                    <li><a href="<?php echo e(route('role-permissions.index')); ?>" class="<?php echo e(request()->routeIs('role-permissions.*') ? 'active' : ''); ?>">Role Permissions</a></li>
                </ul>
            </li>
            <?php endif; ?>

            
            <?php if($user->is_super_admin || $userRoles->contains('name', 'principal')): ?>
            <li>
                <a href="<?php echo e(route('notifications.index')); ?>" class="<?php echo e(request()->routeIs('notifications.*') ? 'active' : ''); ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php $unreadCount = $user->unreadNotifications->count(); ?>
                    <?php if($unreadCount > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo e($unreadCount); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            
            <li>
                <form action="<?php echo e(route('logout')); ?>" method="POST" id="logout-form">
                    <?php echo csrf_field(); ?>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </form>
            </li>
        </ul>
    </aside>

    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-wrapper">
        
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="topbar-right">
                <?php if(Auth::check() && $userRoles->count() > 1): ?>
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="roleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo e(ucfirst(session('active_role', $userRoles->first()->name ?? 'Role'))); ?>

                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="roleDropdown">
                            <?php $__currentLoopData = $userRoles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <form action="<?php echo e(route('switch.role')); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="role" value="<?php echo e($role->name); ?>">
                                        <button type="submit" class="dropdown-item <?php echo e(session('active_role') == $role->name ? 'active' : ''); ?>">
                                            <?php echo e(ucfirst($role->name)); ?>

                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <a href="<?php echo e(route('profile.edit')); ?>" class="text-decoration-none d-flex align-items-center">
                    <span class="text-muted mr-2"><?php echo e($user->full_name ?: $user->username); ?></span>
                    <img class="img-profile rounded-circle ms-2" 
                         src="<?php echo e($user->profile_picture_url); ?>" 
                         alt="<?php echo e($user->full_name); ?>"
                         style="width: 40px; height: 40px; object-fit: cover;">
                </a>
            </div>
        </div>

        
        <main class="main-content">
            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            
            <?php if(Auth::check() && Auth::user()->staff): ?>
                <?php
                    $pendingReminders = \App\Models\AttendanceReminder::with('subject')
                        ->where('staff_id', Auth::user()->staff->id)
                        ->where('is_used', false)
                        ->orderByDesc('created_at')
                        ->get();
                ?>
                <?php if($pendingReminders->count() > 0): ?>
                    <div class="m-3">
                        <?php $__currentLoopData = $pendingReminders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reminder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="alert alert-warning border-start border-4 border-warning shadow-sm mb-2 rounded-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark">
                                            <i class="fas fa-bell text-warning me-2 fa-lg"></i>
                                            Attendance Marking Reminder from Principal
                                        </h6>
                                        <div class="small text-dark">
                                            Please mark attendance for <strong><?php echo e($reminder->subject->name ?? 'Subject'); ?></strong> - 
                                            <strong><?php echo e($reminder->class_level); ?>th Class (<?php echo e($reminder->admission_year); ?>)</strong>
                                            <?php if($reminder->section): ?>
                                                (Section: <span class="badge bg-info text-dark"><?php echo e($reminder->section); ?></span>)
                                            <?php endif; ?>
                                            for date <strong class="text-danger"><?php echo e(\Carbon\Carbon::parse($reminder->attendance_date)->format('d F Y (l)')); ?></strong>.
                                        </div>
                                        <?php if(!empty($reminder->message)): ?>
                                            <div class="small text-muted fst-italic mt-1">
                                                <i class="fas fa-quote-left me-1"></i>"<?php echo e($reminder->message); ?>"
                                            </div>
                                        <?php endif; ?>
                                        <div class="badge bg-light text-danger border border-danger small mt-1">
                                            <i class="fas fa-shield-alt me-1"></i>One-Time Past Date Permission Granted
                                        </div>
                                    </div>
                                    <div>
                                        <a href="<?php echo e(route('attendance.create', [
                                            'subject_id' => $reminder->subject_id,
                                            'class' => $reminder->class_level,
                                            'year' => $reminder->admission_year,
                                            'date' => \Carbon\Carbon::parse($reminder->attendance_date)->toDateString(),
                                            'section' => $reminder->section,
                                            'reminder_id' => $reminder->id
                                        ])); ?>" class="btn btn-warning btn-sm fw-bold shadow-sm">
                                            <i class="fas fa-pen-fancy me-1"></i>Mark Attendance Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </main>

        
        <footer class="dashboard-footer">
            <?php echo \Sohaib\CmsCore\ProtectionService::renderCredits(); ?>
        </footer>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileSidebarClose = document.getElementById('mobileSidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const menuToggles = document.querySelectorAll('.menu-toggle');

            function closeMobileSidebar() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }

            function openMobileSidebar() {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
                document.body.classList.add('sidebar-open');
            }

            function toggleMobileSidebar() {
                if (sidebar.classList.contains('show')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            }

            // Sidebar collapse/expand (Desktop) or drawer (Mobile)
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.toggle('collapsed');
                } else {
                    toggleMobileSidebar();
                }
            });

            // Mobile sidebar close button
            if (mobileSidebarClose) {
                mobileSidebarClose.addEventListener('click', closeMobileSidebar);
            }

            // Overlay click to close
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeMobileSidebar);
            }

            // Close all submenus
            function closeAllSubmenus(exceptId) {
                document.querySelectorAll('.submenu.show').forEach(submenu => {
                    if (submenu.id !== exceptId) {
                        submenu.classList.remove('show');
                        const parentToggle = submenu.previousElementSibling;
                        if (parentToggle) {
                            const icon = parentToggle.querySelector('.menu-toggle-icon');
                            if (icon) icon.classList.remove('rotated');
                        }
                    }
                });
            }

            // Submenu toggle — accordion style (one open at a time)
            menuToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-target');
                    const submenu = document.getElementById(targetId);
                    const icon = this.querySelector('.menu-toggle-icon');
                    const isOpen = submenu.classList.contains('show');

                    // Close all other submenus first
                    closeAllSubmenus(targetId);

                    // Toggle the clicked submenu
                    submenu.classList.toggle('show', !isOpen);
                    icon.classList.toggle('rotated', !isOpen);
                });
            });

            // Auto-expand the menu that contains an active submenu link
            document.querySelectorAll('.submenu a.active').forEach(activeLink => {
                const submenu = activeLink.closest('.submenu');
                if (submenu) {
                    submenu.classList.add('show');
                    const parentToggle = submenu.previousElementSibling;
                    if (parentToggle) {
                        const icon = parentToggle.querySelector('.menu-toggle-icon');
                        if (icon) icon.classList.add('rotated');
                    }
                }
            });

            // Also handle menu-toggle level active links
            document.querySelectorAll('.sidebar-menu > li > a.menu-toggle.active').forEach(activeToggle => {
                const targetId = activeToggle.getAttribute('data-target');
                const submenu = document.getElementById(targetId);
                const icon = activeToggle.querySelector('.menu-toggle-icon');
                if (submenu && !submenu.classList.contains('show')) {
                    submenu.classList.add('show');
                    if (icon) icon.classList.add('rotated');
                }
            });

            // On mobile, close sidebar when a non-toggle link is clicked
            sidebar.querySelectorAll('a:not(.menu-toggle)').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeMobileSidebar();
                    }
                });
            });

            // Handle window resize — clean up mobile classes if resized to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeMobileSidebar();
                }
            });

            // Sidebar Search Logic
            const sidebarSearch = document.getElementById('sidebarSearch');
            if (sidebarSearch) {
                sidebarSearch.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    const menuItems = document.querySelectorAll('.sidebar-menu > li');
                    
                    menuItems.forEach(item => {
                        if (item.classList.contains('sidebar-header')) return;
                        
                        const text = item.innerText.toLowerCase();
                        const sublinks = item.querySelectorAll('.submenu li a');
                        let submatch = false;
                        
                        sublinks.forEach(sub => {
                            if (sub.innerText.toLowerCase().includes(query)) submatch = true;
                        });

                        if (text.includes(query) || submatch) {
                            item.style.display = '';
                            if (submatch && query.length > 0) {
                                const toggle = item.querySelector('.menu-toggle');
                                if (toggle) {
                                    const targetId = toggle.getAttribute('data-target');
                                    const submenu = document.getElementById(targetId);
                                    if (submenu) submenu.classList.add('show');
                                    const icon = toggle.querySelector('.menu-toggle-icon');
                                    if (icon) icon.classList.add('rotated');
                                }
                            }
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>

    <?php echo $__env->yieldContent('scripts'); ?>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</body>
</html><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/layouts/app.blade.php ENDPATH**/ ?>