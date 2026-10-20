<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'College Management System'))</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome Icons --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    {{-- Google Fonts for Urdu/Arabic support --}}
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">

    {{-- Custom Styles --}}
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">

    {{-- Dynamic Color Variables (override CSS defaults with DB values) --}}
    @php
        $colors = \App\Models\InstituteSetting::getColors();
    @endphp
    <style>
    :root {
        --col-primary: {{ $colors['primary'] }};
        --col-accent: {{ $colors['secondary'] }};
        --col-highlight: {{ $colors['highlight'] }};
        --col-success: {{ $colors['success'] }};

        /* Derived colors */
        --col-sidebar-bg: {{ $colors['primary'] }};
        --primary-color: var(--col-primary);
        --secondary-color: var(--col-accent);
        --col-hover: color-mix(in srgb, {{ $colors['primary'] }} 80%, white);
        --col-active: color-mix(in srgb, {{ $colors['primary'] }} 70%, black);
        --col-dark: color-mix(in srgb, {{ $colors['primary'] }} 60%, black);
        --col-mint: color-mix(in srgb, {{ $colors['secondary'] }} 50%, white);
        --col-light-bg: color-mix(in srgb, {{ $colors['secondary'] }} 30%, white);
        --col-info: {{ $colors['secondary'] }};
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
    .border-left-primary { border-left-color: var(--col-primary) !important; }

    /* Override Bootstrap success */
    .bg-success { background-color: var(--col-success) !important; }
    .text-success { color: var(--col-success) !important; }
    .btn-success {
        background-color: var(--col-success) !important;
        border-color: var(--col-success) !important;
    }
    .border-left-success { border-left-color: var(--col-success) !important; }

    /* Sidebar override */
    .sidebar { background: {{ $colors['primary'] }}; }
    .sidebar-brand { background: color-mix(in srgb, {{ $colors['primary'] }} 60%, black); }

    /* Login gradient override */
    body.login-page {
        background: linear-gradient(-45deg, {{ $colors['primary'] }}, {{ $colors['secondary'] }}, color-mix(in srgb, {{ $colors['primary'] }} 80%, white), {{ $colors['secondary'] }});
        background-size: 400% 400%;
    }
    .login-card { background: rgba({{ intval(hexdec(substr($colors['secondary'], 1, 2))) }}, {{ intval(hexdec(substr($colors['secondary'], 3, 2))) }}, {{ intval(hexdec(substr($colors['secondary'], 5, 2))) }}, 0.95); }

    /* Form focus */
    .form-control:focus, .form-select:focus {
        border-color: {{ $colors['secondary'] }};
        box-shadow: 0 0 0 0.25rem rgba({{ intval(hexdec(substr($colors['secondary'], 1, 2))) }}, {{ intval(hexdec(substr($colors['secondary'], 3, 2))) }}, {{ intval(hexdec(substr($colors['secondary'], 5, 2))) }}, 0.2);
    }

    /* Page header */
    .page-header { border-left-color: var(--col-primary); }
    .card-header.bg-primary { background-color: var(--col-primary) !important; }

    /* New Sidebar Enhancements */
    .sidebar-header {
        padding: 1.5rem 1.5rem 0.5rem;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(255, 255, 255, 0.5);
        display: block;
    }
    .sidebar-search-container {
        padding: 0 1rem 1rem;
    }
    .sidebar-search {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        border-radius: 6px;
        width: 100%;
        transition: all 0.2s;
    }
    .sidebar-search:focus {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.5);
        outline: none;
        box-shadow: none;
    }
    .sidebar-search::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }
    </style>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('styles')
</head>
<body class="@yield('body-class')">

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            @php
                $settings = \App\Models\InstituteSetting::getSettings();
            @endphp
            @if($settings->hasLogo())
                <img src="{{ $settings->logo_url }}" alt="Logo">
            @else
                <i class="fas fa-graduation-cap"></i>
            @endif
            <span>{{ $settings->institute_title ?? 'College MS' }}</span>
            <button class="btn-close btn-close-white ms-auto d-md-none" id="mobileSidebarClose" aria-label="Close"></button>
        </div>

        @php
            $user = Auth::user();
            $userRoles = $user ? $user->roles : collect();
            $activeRole = session('active_role');
            if (!$activeRole && Auth::check()) {
                if ($user->is_super_admin) {
                    $activeRole = 'admin';
                } elseif ($userRoles->isNotEmpty()) {
                    // Default priority fallback if session is empty
                    if ($userRoles->contains('name', 'admin')) $activeRole = 'admin';
                    elseif ($userRoles->contains('name', 'principal')) $activeRole = 'principal';
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
        @endphp

        <div class="px-3 mb-3">
            @if($isSpecialAdmin)
                <div class="btn-group w-100 shadow-sm" role="group">
                    <form action="{{ route('switch.view') }}" method="POST" class="w-50">
                        @csrf
                        <input type="hidden" name="view" value="HSSC">
                        <button type="submit" class="btn btn-sm w-100 {{ $sidebarView == 'HSSC' ? 'btn-primary' : 'btn-outline-primary' }}" style="border-radius: 5px 0 0 5px;">
                            HSSC
                        </button>
                    </form>
                    <form action="{{ route('switch.view') }}" method="POST" class="w-50">
                        @csrf
                        <input type="hidden" name="view" value="BS">
                        <button type="submit" class="btn btn-sm w-100 {{ $sidebarView == 'BS' ? 'btn-primary' : 'btn-outline-primary' }}" style="border-radius: 0 5px 5px 0;">
                            ADP / BS
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="sidebar-search-container">
            <input type="text" id="sidebarSearch" class="sidebar-search" placeholder="Search menu...">
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-header">Quick Access</li>
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            @if(!$userRoles->contains('name', 'parent') && !$userRoles->contains('name', 'student'))
            <li>
                <a href="{{ route('my-leaves.index') }}" class="{{ request()->routeIs('my-leaves.*') ? 'active' : '' }}">
                    <i class="fas fa-suitcase-rolling"></i>
                    <span>My Leaves</span>
                </a>
            </li>
            @endif

            {{-- Communication (Global Notifications/Announcements) --}}
            @if($user->is_super_admin || in_array($activeRole, ['admin', 'principal']))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['notifications.*', 'announcements.*']) ? 'active' : '' }}" data-target="global-comm">
                    <i class="fas fa-bullhorn"></i>
                    <span>Communication</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="global-comm">
                    <li><a href="{{ route('notifications.index') }}">In-App Alerts</a></li>
                    <li><a href="{{ route('announcements.index') }}">Bulk Announcements</a></li>
                </ul>
            </li>
            @endif

            @if($userRoles->contains('name', 'parent'))
            <li>
                <a href="{{ route('parent.dashboard') }}" class="{{ request()->routeIs('parent.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Parent Portal</span>
                </a>
            </li>
            @endif

            <li class="sidebar-header">Academics</li>
            
            {{-- HSSC Academics --}}
            @if($sidebarView == 'HSSC' && in_array($activeRole, ['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'accountant', 'hssc_accountant', 'teacher']))
                @if($activeRole != 'teacher')
                <li>
                    <a href="#" class="menu-toggle {{ request()->routeIs(['rooms.*', 'subject.*', 'subject-groups.*', 'timetables.*', 'bise.*', 'designations.*', 'quota.*', 'bloodGroup.*']) ? 'active' : '' }}" data-target="hssc-acad-info">
                        <i class="fas fa-university"></i>
                        <span>Academic Info</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="hssc-acad-info">
                        <li><a href="{{ route('rooms.index') }}">Rooms & Blocks</a></li>
                        <li><a href="{{ route('subject.index') }}">Subject Catalog</a></li>
                        <li><a href="{{ route('subject-groups.index') }}">Subject Groups</a></li>
                        <li><a href="{{ route('admin.timetables.index') }}">Class Timetables</a></li>
                        <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                        <li><a href="{{ route('bise.biseindex') }}">BISE Records</a></li>
                        <li><a href="{{ route('quota.index') }}">Quota Settings</a></li>
                        <li><a href="{{ route('bloodGroup.index') }}">Blood Groups</a></li>
                        <li><a href="{{ route('designations.index') }}">Designations</a></li>
                    </ul>
                </li>
                @endif

                <li>
                    <a href="#" class="menu-toggle {{ request()->routeIs(['students.*', 'attendance.*', 'bise-roll-numbers.*', 'struck-off.*']) ? 'active' : '' }}" data-target="hssc-students">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student Affairs</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="hssc-students">
                        <li><a href="{{ route('students.index') }}">Student Directory</a></li>
                        @if($activeRole != 'teacher')
                            <li><a href="{{ route('students.create') }}">Direct Admission</a></li>
                            <li><a href="{{ route('students.promotion') }}">Class Promotion</a></li>
                        @endif
                        <li><a href="{{ route('attendance.index') }}">Class Attendance</a></li>
                        @if($activeRole === 'teacher')
                            <li>
                                <a href="{{ route('struck-off.teacher') }}"
                                   class="{{ request()->routeIs('struck-off.teacher') ? 'active' : '' }}"
                                   style="color: #ff8080;">
                                    <i class="fas fa-user-slash me-1" style="font-size:0.8em;"></i> Struck-Off Students
                                </a>
                            </li>
                        @endif
                        @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'super_admin']))
                            <li>
                                <a href="{{ route('struck-off.index') }}"
                                   class="{{ request()->routeIs('struck-off.index') ? 'active' : '' }}"
                                   style="color: #ff8080;">
                                    <i class="fas fa-user-slash me-1" style="font-size:0.8em;"></i> Struck-Off Mgmt
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('struck-off.teacher') }}"
                                   class="{{ request()->routeIs('struck-off.teacher') ? 'active' : '' }}">
                                    <i class="fas fa-exclamation-triangle me-1 text-warning" style="font-size:0.8em;"></i> Struck-Off Eligible
                                </a>
                            </li>
                        @endif
                        <li><a href="{{ route('attendance.gate.index') }}">Gate Attendance</a></li>
                        <li><a href="{{ route('bise-roll-numbers.index') }}">BISE Roll Nos</a></li>
                        <li><a href="{{ route('students.id_cards.index') }}">Generate ID Cards</a></li>
                        <li><a href="{{ route('students.trash') }}">Student Recycle Bin</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="menu-toggle {{ request()->routeIs(['datesheets.*', 'results.*', 'invigilators.*', 'seating-plans.*']) ? 'active' : '' }}" data-target="hssc-exams">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Examination Hub</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="hssc-exams">
                        <li><a href="{{ route('datesheets.index') }}">Exam Datesheets</a></li>
                        <li><a href="{{ route('results.index') }}">Result Management</a></li>
                        <li><a href="{{ route('invigilators.index') }}">Staff Invigilation</a></li>
                        <li><a href="{{ route('seating-plans.index') }}">Seating Plans</a></li>
                    </ul>
                </li>
            @endif

            {{-- BS Academics --}}
            @if($sidebarView == 'BS' && in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'teacher', 'bs_accountant']))
                <li>
                    <a href="#" class="menu-toggle {{ request()->routeIs(['programs.*', 'bs-subjects.*', 'semesters.*']) ? 'active' : '' }}" data-target="bs-acad-info">
                        <i class="fas fa-graduation-cap"></i>
                        <span>ADP / BS Programs</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="bs-acad-info">
                        <li><a href="{{ route('programs.index') }}">Degree Programs</a></li>
                        <li><a href="{{ route('bs-subjects.index') }}">Course Catalog</a></li>
                        <li><a href="{{ route('semesters.index') }}">Semester Config</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="menu-toggle {{ request()->routeIs(['admissions.*', 'he.roll-numbers.*', 'he.attendance.*', 'he.teacher_assignment']) ? 'active' : '' }}" data-target="bs-students">
                        <i class="fas fa-users"></i>
                        <span>BS Student Affairs</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="bs-students">
                        <li><a href="{{ route('admissions.index') }}">Student Records</a></li>
                        <li><a href="{{ route('he.attendance.index') }}">Attendance Log</a></li>
                        <li><a href="{{ route('he.roll-numbers.index') }}">Uni Roll Nos</a></li>
                        <li><a href="{{ route('he.teacher_assignment') }}">Teacher Assigns</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="menu-toggle {{ request()->routeIs(['he.results.*', 'he.datesheets.*', 'he.invigilators.*', 'he.seating_plans.*']) ? 'active' : '' }}" data-target="bs-exams">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Uni Examinations</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="bs-exams">
                        <li><a href="{{ route('he.results.index') }}">Semester Results</a></li>
                        <li><a href="{{ route('he.datesheets.index') }}">Exam Datesheets</a></li>
                        <li><a href="{{ route('he.invigilators.index') }}">Duty Roster</a></li>
                        <li><a href="{{ route('he.seating_plans.index') }}">Seating Layouts</a></li>
                    </ul>
                </li>
            @endif

            {{-- Global Learning (LMS/Library) --}}
            @if(in_array($activeRole, ['student', 'teacher', 'admin', 'principal']))
            <li>
                <a href="#" class="menu-toggle {{ request()->is('lms*') || request()->is('library*') ? 'active' : '' }}" data-target="learning-blocks">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Learning & Library</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="learning-blocks">
                    <li><a href="{{ route('lms.dashboard') }}">LMS Dashboard</a></li>
                    <li><a href="{{ route('lms.assignments.index') }}">Assignments</a></li>
                    <li><a href="{{ route('library.index') }}">Library Home</a></li>
                </ul>
            </li>

            {{-- Academic Reports (HSSC & BS) --}}
            @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'hssc_clerk', 'accountant', 'hssc_accountant', 'bs_accountant']))
            <li>
                <a href="#" class="menu-toggle {{ request()->is('reports*') ? 'active' : '' }}" data-target="acad-reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports & Analytics</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="acad-reports">
                    <li style="background: rgba(59, 130, 246, 0.15); border-left: 3px solid #3b82f6; margin-bottom: 4px;">
                        <a href="{{ route('reports.custom-student-report') }}" class="{{ request()->routeIs('reports.custom-student-report*') ? 'active' : '' }}" style="font-weight: 600; color: #60a5fa;">
                            <i class="fas fa-sliders-h me-1"></i> Dynamic Report Builder
                        </a>
                    </li>
                    <li><a href="{{ route('reports.subject-enrollment.index') }}">Subject Enrollment</a></li>
                    @if($sidebarView == 'HSSC')
                        <li><a href="{{ route('reports.final-result-report.index') }}">Annual Results</a></li>
                        <li><a href="{{ route('reports.detailed-final-result-report.index') }}">Detailed Final Result</a></li>
                        <li><a href="{{ route('reports.subject-final-result-report.index') }}">Subject Final Result</a></li>
                        <li><a href="{{ route('reports.test-result-report.index') }}">Test Performance</a></li>
                        <li><a href="{{ route('reports.test-attendance-report') }}">Attendance Analytics</a></li>
                    @else
                        <li><a href="{{ route('he.reports.award_list.form') }}">HE Award Lists</a></li>
                        <li><a href="{{ route('he.reports.tabulation_sheet.form') }}">Tabulation Sheets</a></li>
                        <li><a href="{{ route('he.reports.transcript.search') }}">Student Transcripts</a></li>
                    @endif
                    <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                    <li><a href="{{ route('reports.cash-register.index') }}">Collection Report</a></li>
                </ul>
            </li>
            @endif
            @endif

            <li class="sidebar-header">Administration</li>
            
            {{-- Website Setup (Global) --}}
            @if(in_array($activeRole, ['admin', 'principal', 'clerk']))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['albums.*', 'updates.*', 'committees.*', 'principals.*', 'settings.colors', 'downloads.*']) ? 'active' : '' }}" data-target="global-website">
                    <i class="fas fa-globe"></i>
                    <span>Website Setup</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="global-website">
                    <li><a href="{{ route('albums.index') }}">Photo Albums</a></li>
                    <li><a href="{{ route('updates.index') }}">News & Updates</a></li>
                    <li><a href="{{ route('downloads.index') }}">Download Center</a></li>
                    <li><a href="{{ route('committees.index') }}">Committees</a></li>
                    <li><a href="{{ route('principals.index') }}">Principals History</a></li>
                    @if($activeRole == 'admin' || $user->is_super_admin)
                        <li><a href="{{ route('settings.colors') }}">Theme Colors</a></li>
                    @endif
                </ul>
            </li>

            @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'hssc_clerk']))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['admission-applications.*', 'admissions.settings.*', 'fee-heads.*', 'subject-rules.*']) ? 'active' : '' }}" data-target="global-admissions">
                    <i class="fas fa-file-signature"></i>
                    <span>Admissions</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="global-admissions">
                    <li><a href="{{ route('admission-applications.index') }}">Applications</a></li>
                    <li><a href="{{ route('admission-applications.merit-list') }}">Merit Lists</a></li>
                    @if($activeRole == 'admin' || $user->is_super_admin)
                        <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                        <li><a href="{{ route('admissions.settings.index') }}">Admission Config</a></li>
                        <li><a href="{{ route('fee-heads.index') }}">Fee Structures</a></li>
                        <li><a href="{{ route('subject-rules.index') }}">Subject Rules</a></li>
                    @endif
                </ul>
            </li>
            @endif
            @endif

            {{-- HR & Staff (HSSC) --}}
            @if($sidebarView == 'HSSC' && in_array($activeRole, ['admin', 'principal', 'clerk']))
            <li>
                <a href="#" class="menu-toggle {{ request()->is('admin/staff*') || request()->is('admin/hr*') || request()->is('admin/payroll*') || request()->routeIs(['staff.attendance.*', 'leave-types.*', 'holidays.*']) ? 'active' : '' }}" data-target="hssc-admin">
                    <i class="fas fa-users-cog"></i>
                    <span>HR & Staffing</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="hssc-admin">
                    <li><a href="{{ route('staff.index') }}">Staff Directory</a></li>
                    <li><a href="{{ route('staff.attendance.index') }}">Staff Attendance</a></li>
                    <li><a href="{{ route('leave-approvals.index') }}">Leave Requests</a></li>
                    @if(in_array($activeRole, ['admin', 'principal']))
                        <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                        <li><a href="{{ route('reports.staff-attendance.index') }}">Attendance Reports</a></li>
                        <li><a href="{{ route('payroll.index') }}">Monthly Payroll</a></li>
                        <li><a href="{{ route('leave-types.index') }}">Leave Types</a></li>
                        <li><a href="{{ route('holidays.index') }}">Public Holidays</a></li>
                    @endif
                </ul>
            </li>
            @endif

            <li class="sidebar-header">Finance & Logistics</li>

            {{-- Fee Vouchers (HSSC & BS) --}}
            @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'accountant', 'bs_accountant']))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs('vouchers.*') ? 'active' : '' }}" data-target="global-vouchers">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Fee Vouchers</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="global-vouchers">
                    <li><a href="{{ route('vouchers.index') }}">Voucher History</a></li>
                    <li><a href="{{ route('vouchers.bulk-class12') }}">Bulk HSSC</a></li>
                    <li><a href="{{ route('vouchers.bulk-he') }}">Bulk BS/ADP</a></li>
                    <li><a href="{{ route('vouchers.fine.create', ['student' => 0]) }}">Issue Individual Fine</a></li>
                </ul>
            </li>
            @endif

            {{-- Assets & Logistics (HSSC) --}}
            @if($sidebarView == 'HSSC' && in_array($activeRole, ['admin', 'principal', 'clerk']))
            <li>
                <a href="#" class="menu-toggle {{ request()->is('admin/assets*') || request()->is('admin/budgets*') || request()->is('admin/transport*') || request()->is('admin/hostel*') || request()->routeIs(['alumni.*']) ? 'active' : '' }}" data-target="hssc-ops">
                    <i class="fas fa-truck-loading"></i>
                    <span>Logistics & Alumni</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="hssc-ops">
                    <li><a href="{{ route('assets.index') }}">Asset Inventory</a></li>
                    <li><a href="{{ route('assets.categories') }}">Asset Categories</a></li>
                    <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                    <li><a href="{{ route('budgets.index') }}">Budget Planning</a></li>
                    <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                    <li><a href="{{ route('transport.index') }}">Transport Fleet</a></li>
                    <li><a href="{{ route('hostel.index') }}">Hostel Mgmt</a></li>
                    <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                    <li><a href="{{ route('alumni.index') }}">Alumni Directory</a></li>
                    <li><a href="{{ route('alumni.batches') }}">Graduation Batches</a></li>
                </ul>
            </li>
            @endif

            <li class="sidebar-header">System</li>

            {{-- User Management --}}
            @if($user->is_super_admin || $userRoles->contains('name', 'principal'))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['users.*', 'role-permissions.*']) ? 'active' : '' }}" data-target="system-users">
                    <i class="fas fa-users-cog"></i>
                    <span>Users & Access</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="system-users">
                    <li><a href="{{ route('users.index') }}">User Accounts</a></li>
                    <li><a href="{{ route('role-permissions.index') }}">Role Permissions</a></li>
                    <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                    <li><a href="{{ route('settings.edit') }}">Global Settings</a></li>
                    <li><a href="{{ route('imports.index') }}">Data Imports</a></li>
                </ul>
            </li>
            @endif

            {{-- Logout --}}
            <li>
                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                    @csrf
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </form>
            </li>
        </ul>
    </aside>

    {{-- Sidebar Overlay for Mobile --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-wrapper">
        {{-- Top Bar --}}
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="topbar-right">
                @if(Auth::check() && $userRoles->count() > 1)
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="roleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ ucfirst(session('active_role', $userRoles->first()->name ?? 'Role')) }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="roleDropdown">
                            @foreach($userRoles as $role)
                                <li>
                                    <form action="{{ route('switch.role') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $role->name }}">
                                        <button type="submit" class="dropdown-item {{ session('active_role') == $role->name ? 'active' : '' }}">
                                            {{ ucfirst($role->name) }}
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <a href="{{ route('profile.edit') }}" class="text-decoration-none d-flex align-items-center">
                    <span class="text-muted mr-2">{{ $user->full_name ?: $user->username }}</span>
                    <img class="img-profile rounded-circle ms-2" 
                         src="{{ $user->profile_picture_url }}" 
                         alt="{{ $user->full_name }}"
                         style="width: 40px; height: 40px; object-fit: cover;">
                </a>
            </div>
        </div>

        {{-- Main Content --}}
        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="dashboard-footer">
            @cmsCredits
        </footer>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Sidebar Toggle Script --}}
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

                    // Hide headers if no visible items follow them
                    const headers = document.querySelectorAll('.sidebar-header');
                    headers.forEach(header => {
                        let next = header.nextElementSibling;
                        let hasVisible = false;
                        while (next && !next.classList.contains('sidebar-header')) {
                            if (next.style.display !== 'none') {
                                hasVisible = true;
                                break;
                            }
                            next = next.nextElementSibling;
                        }
                        header.style.display = hasVisible ? '' : 'none';
                    });
                });
            }
        });
    </script>

    @yield('scripts')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</body>
</html>