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

    {{-- Google Fonts: Outfit --}}
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Custom Styles --}}
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">

    {{-- Dynamic Theme Palette & Unified Component Styling --}}
    @include('layouts.dynamic-theme')


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
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-white text-decoration-none">
                @php
                    $settings = \App\Models\InstituteSetting::getSettings();
                @endphp
                @if($settings->hasLogo())
                    <img src="{{ $settings->logo_url }}" alt="Logo">
                @else
                    <i class="fas fa-graduation-cap"></i>
                @endif
                <span>{{ $settings->institute_title ?? 'College MS' }}</span>
            </a>
            <button class="btn-close btn-close-white ms-auto d-md-none" id="mobileSidebarClose" aria-label="Close"></button>
        </div>

        @php
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


            {{-- HSSC Content --}}
            @if($sidebarView == 'HSSC')
                @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'accountant', 'hssc_accountant', 'super_admin']) || ($user && $user->is_super_admin))
                    
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs(['students.*', 'bise-roll-numbers.*', 'staff.student_assignment', 'student-affairs.*']) || ($activeRole != 'teacher' && request()->routeIs('struck-off.*')) ? 'active' : '' }}" data-target="students-menu">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student affairs</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="students-menu">
                            <li><a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.index') ? 'active' : '' }}">
                                {{ $activeRole == 'teacher' ? 'My Students' : 'HSSC Students' }}
                            </a></li>
                            @if($activeRole != 'teacher')
                                <li><a href="{{ route('students.create') }}" class="{{ request()->routeIs('students.create') ? 'active' : '' }}">Add New</a></li>
                                <li><a href="{{ route('student-affairs.sections.index') }}" class="{{ request()->routeIs('student-affairs.sections.*') ? 'active' : '' }}">Sections</a></li>
                                <li><a href="{{ route('staff.student_assignment') }}" class="{{ request()->routeIs('staff.student_assignment') ? 'active' : '' }}">Assign Students</a></li>
                                <li><a href="{{ route('bise-roll-numbers.index') }}" class="{{ request()->routeIs('bise-roll-numbers.*') ? 'active' : '' }}">Add BISE Roll No.</a></li>
                                <li>
                                    <a href="{{ route('struck-off.index') }}" class="{{ request()->routeIs('struck-off.index') ? 'active' : '' }}">
                                        <i class="fas fa-user-slash me-1 text-danger"></i> Struck-Off Students
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('struck-off.teacher') }}" class="{{ request()->routeIs('struck-off.teacher') ? 'active' : '' }}">
                                        <i class="fas fa-user-times me-1 text-warning"></i> Strike Off Eligible
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>

                    {{-- Staff Details --}}
                    <li>
                        <a href="#" class="menu-toggle {{ (request()->routeIs('staff.*') && !request()->routeIs(['staff.student_assignment', 'staff.award_list.*'])) || request()->routeIs('reports.staff-attendance.*') ? 'active' : '' }}" data-target="staff-menu">
                            <i class="fas fa-user-tie"></i>
                            <span>Staff Details</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="staff-menu">
                            <li><a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.index') ? 'active' : '' }}">All Staff</a></li>
                            @if($activeRole != 'teacher')
                                <li><a href="{{ route('staff.create') }}" class="{{ request()->routeIs('staff.create') ? 'active' : '' }}">Add New</a></li>
                                <li><a href="{{ route('reports.staff-attendance.index') }}" class="{{ request()->routeIs('reports.staff-attendance.*') ? 'active' : '' }}">Attendance Reports</a></li>
                            @endif
                            @if(in_array($activeRole, ['admin', 'principal', 'super_admin']) || (isset($user) && $user->is_super_admin))
                                <li><a href="{{ route('leave-approvals.index') }}" class="{{ request()->routeIs('leave-approvals.*') ? 'active' : '' }}"><i class="fas fa-check-circle text-success me-1"></i> Leave Approvals</a></li>
                            @endif
                        </ul>
                    </li>

                    {{-- Examination --}}
                    @if($activeRole != 'teacher')
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs(['datesheets.*', 'seating-plans.*', 'attendance.gate.*', 'test-attendance.*', 'staff.award_list.*']) ? 'active' : '' }}" data-target="exams-menu">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Examination</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="exams-menu">
                            <li><a href="{{ route('datesheets.index') }}" class="{{ request()->routeIs('datesheets.*') ? 'active' : '' }}">Datesheets</a></li>
                            <li><a href="{{ route('staff.award_list.form') }}" class="{{ request()->routeIs('staff.award_list.*') ? 'active' : '' }}">Award List</a></li>
                            <li><a href="{{ route('seating-plans.index') }}" class="{{ request()->routeIs('seating-plans.*') ? 'active' : '' }}">Seating Plans</a></li>
                            <li><a href="{{ route('attendance.gate.index') }}" class="{{ request()->routeIs('attendance.gate.*') ? 'active' : '' }}">Gate Attendance Scanner</a></li>
                            <li><a href="{{ route('test-attendance.create') }}" class="{{ request()->routeIs('test-attendance.*') ? 'active' : '' }}">Mark Test Attendance</a></li>
                        </ul>
                    </li>

                    {{-- Invigilators --}}
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('invigilators.*') ? 'active' : '' }}" data-target="invigilators-menu">
                            <i class="fas fa-user-shield"></i>
                            <span>Invigilators</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="invigilators-menu">
                            <li><a href="{{ route('invigilators.index') }}" class="{{ request()->routeIs('invigilators.index') ? 'active' : '' }}">Assignments</a></li>
                            <li><a href="{{ route('invigilators.assign') }}" class="{{ request()->routeIs('invigilators.assign') ? 'active' : '' }}">Assign by Date</a></li>
                            <li><a href="{{ route('invigilators.duty_roster') }}" class="{{ request()->routeIs('invigilators.duty_roster') ? 'active' : '' }}">Print Duty Roster</a></li>
                            <li><a href="{{ route('invigilators.create') }}" class="{{ request()->routeIs('invigilators.create') ? 'active' : '' }}">Auto-Assign</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="{{ route('invigilators.duty_rules') }}" class="{{ request()->routeIs('invigilators.duty_rules') ? 'active' : '' }}">Duty Rules</a></li>
                            <li><a href="{{ route('invigilators.exemptions') }}" class="{{ request()->routeIs('invigilators.exemptions') ? 'active' : '' }}">Exemptions</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Results --}}
                    <li>
                        <a href="#" class="menu-toggle {{ (request()->routeIs('results.*') && !request()->routeIs('results.final.view')) || request()->routeIs('results.create') ? 'active' : '' }}" data-target="results-menu">
                            <i class="fas fa-trophy"></i>
                            <span>Results</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="results-menu">
                            @if($activeRole != 'teacher')
                                <li><a href="{{ route('results.index') }}" class="{{ request()->routeIs('results.index') ? 'active' : '' }}">All Results</a></li>
                            @endif
                            <li><a href="{{ route('results.create') }}" class="{{ request()->routeIs('results.create') ? 'active' : '' }}">Add Result</a></li>
                            @if($activeRole != 'teacher')
                                <li><a href="{{ route('results.11th.create') }}" class="{{ request()->routeIs('results.11th.create') ? 'active' : '' }}">Add 11th Result</a></li>
                                <li><a href="{{ route('results.final.create') }}" class="{{ request()->routeIs('results.final.create') ? 'active' : '' }}">Add Final Result</a></li>
                            @endif
                        </ul>
                    </li>

                    {{-- Custom Reports --}}
                    @if($activeRole != 'teacher')
                    <li>
                        <a href="#" class="menu-toggle {{ (request()->routeIs('reports.*') && !request()->routeIs('reports.staff-attendance.*')) || request()->routeIs(['results.final.view', 'attendance.reports.*']) ? 'active' : '' }}" data-target="reports-menu">
                            <i class="fas fa-chart-pie"></i>
                            <span>Custom Reports</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="reports-menu">
                            <li style="background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; margin-bottom: 4px;">
                                <a href="{{ route('reports.custom-student-report') }}" class="{{ request()->routeIs('reports.custom-student-report*') ? 'active' : '' }}" style="font-weight: 600;">
                                    <i class="fas fa-sliders-h text-primary me-1"></i> Dynamic Report Builder
                                </a>
                            </li>
                            <li><a href="{{ route('reports.subject-enrollment.index') }}" class="{{ request()->routeIs('reports.subject-enrollment.*') ? 'active' : '' }}">Subject Enrollment</a></li>
                            <li><a href="{{ route('reports.cash-register.index') }}" class="{{ request()->routeIs('reports.cash-register.*') ? 'active' : '' }}">Cash Register</a></li>
                            <li><a href="{{ route('reports.students-without-teacher.index') }}" class="{{ request()->routeIs('reports.students-without-teacher.*') ? 'active' : '' }}">Students Without Teacher</a></li>
                            <li><a href="{{ route('results.final.view') }}" class="{{ request()->routeIs('results.final.view') ? 'active' : '' }}">View Final Results</a></li>
                            <li><a href="{{ route('reports.final-result-report.index') }}" class="{{ request()->routeIs('reports.final-result-report.*') ? 'active' : '' }}">Final Result Report</a></li>
                            <li><a href="{{ route('reports.test-result-report.index') }}" class="{{ request()->routeIs('reports.test-result-report.*') ? 'active' : '' }}">Test Result Report</a></li>
                            <li><a href="{{ route('reports.detailed-test-result-report.index') }}" class="{{ request()->routeIs('reports.detailed-test-result-report.*') ? 'active' : '' }}">Detailed Test Result Report</a></li>
                            <li><a href="{{ route('reports.detailed-final-result-report.index') }}" class="{{ request()->routeIs('reports.detailed-final-result-report.*') ? 'active' : '' }}">Detailed Final Result Report</a></li>
                            <li><a href="{{ route('reports.subject-final-result-report.index') }}" class="{{ request()->routeIs('reports.subject-final-result-report.*') ? 'active' : '' }}">Subject Final Result Report</a></li>
                            <li><a href="{{ route('reports.test-attendance-report') }}" class="{{ request()->routeIs('reports.test-attendance-report') ? 'active' : '' }}">Test Attendance Report</a></li>
                            <li><a href="{{ route('attendance.reports.form') }}" class="{{ request()->routeIs('attendance.reports.form') ? 'active' : '' }}">Daily Attendance Report</a></li>
                            @if(in_array($activeRole, ['admin', 'principal', 'super_admin']) || (isset($user) && $user->is_super_admin))
                                <li><a href="{{ route('reports.teacher_attendance_status') }}" class="{{ request()->routeIs('reports.teacher_attendance_status') ? 'active' : '' }}"><i class="fas fa-clipboard-check text-warning me-1"></i> Teacher Attendance Status</a></li>
                                <li><a href="{{ route('reports.teacher_workload') }}" class="{{ request()->routeIs('reports.teacher_workload') ? 'active' : '' }}"><i class="fas fa-briefcase text-info me-1"></i> Teacher Workload Report</a></li>
                            @endif
                            <li><a href="{{ route('attendance.reports.whole_year.form') }}" class="{{ request()->routeIs('attendance.reports.whole_year.*') ? 'active' : '' }}">Whole-Year Attendance Report</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Admissions --}}
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs(['admission-applications.*', 'fee-heads.*', 'subject-rules.*', 'admissions.settings.*']) ? 'active' : '' }}" data-target="admissions-menu">
                            <i class="fas fa-file-alt"></i>
                            <span>Admissions</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="admissions-menu">
                            <li><a href="{{ route('admission-applications.index') }}" class="{{ request()->routeIs('admission-applications.index') ? 'active' : '' }}">All Applications</a></li>
                            <li><a href="{{ route('admission-applications.create') }}" class="{{ request()->routeIs('admission-applications.create') ? 'active' : '' }}">New Walk-in</a></li>
                            <li><a href="{{ route('admission-applications.merit-list') }}" class="{{ request()->routeIs('admission-applications.merit-list') ? 'active' : '' }}">Merit List</a></li>
                            @if($user->is_super_admin || in_array($activeRole, ['admin', 'principal']))
                                <li style="border-top: 1px dotted #444; margin: 5px 0;"></li>
                                <li><a href="{{ route('fee-heads.index') }}" class="{{ request()->routeIs('fee-heads.*') ? 'active' : '' }}">Fee Heads</a></li>
                                <li><a href="{{ route('subject-rules.index') }}" class="{{ request()->routeIs('subject-rules.*') ? 'active' : '' }}">Subject Rules</a></li>
                                <li><a href="{{ route('admissions.settings.index') }}" class="{{ request()->routeIs('admissions.settings.*') ? 'active' : '' }}">Admission Settings</a></li>
                            @endif
                        </ul>
                    </li>

                    {{-- Fee vouchers --}}
                    @if($activeRole != 'teacher')
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('vouchers.*') ? 'active' : '' }}" data-target="vouchers-menu">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Fee vouchers</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="vouchers-menu">
                            <li><a href="{{ route('vouchers.index') }}" class="{{ request()->routeIs('vouchers.index') ? 'active' : '' }}">All Vouchers</a></li>
                            @unless(in_array($activeRole, ['bs_clerk', 'bs_admin', 'bs_accountant']))
                                <li><a href="{{ route('vouchers.bulk-class12') }}" class="{{ request()->routeIs('vouchers.bulk-class12') ? 'active' : '' }}">Bulk Class 12</a></li>
                            @endunless
                        </ul>
                    </li>
                    @endif

                    {{-- Fine Management --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'super_admin', 'bs_admin', 'bs_clerk', 'accountant', 'hssc_accountant', 'bs_accountant']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('fines.*') ? 'active' : '' }}" data-target="fines-menu">
                            <i class="fas fa-file-invoice-dollar text-warning"></i>
                            <span>Fine Management</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="fines-menu">
                            <li><a href="{{ route('fines.index') }}" class="{{ request()->routeIs('fines.index') ? 'active' : '' }}">All Fines &amp; Dues</a></li>
                            <li><a href="{{ route('fines.generate') }}" class="{{ request()->routeIs('fines.generate') ? 'active' : '' }}">Calculate &amp; Generate</a></li>
                            <li><a href="{{ route('fines.rules') }}" class="{{ request()->routeIs('fines.rules') ? 'active' : '' }}">Policy Rules &amp; Rates</a></li>
                        </ul>
                    </li>
                    @endif


                    {{-- Academic Info --}}
                    @if($activeRole != 'teacher')
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs(['subject.*', 'bise.*', 'rooms.*', 'timetables.*', 'designations.*', 'quota.*', 'bloodGroup.*', 'subject-groups.*']) ? 'active' : '' }}" data-target="academic-menu">
                            <i class="fas fa-university"></i>
                            <span>Academic Info</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="academic-menu">
                            <li><a href="{{ route('rooms.index') }}" class="{{ request()->routeIs('rooms.*') ? 'active' : '' }}">Rooms & Blocks</a></li>
                             <li><a href="{{ route('subject.index') }}" class="{{ request()->routeIs('subject.*') ? 'active' : '' }}">Subject Catalog</a></li>
                            <li><a href="{{ route('subject-groups.index') }}" class="{{ request()->routeIs('subject-groups.*') ? 'active' : '' }}">Subject Groups</a></li>
                            <li><a href="{{ route('admin.timetables.index') }}" class="{{ request()->routeIs('admin.timetables.*') ? 'active' : '' }}">Class Timetables</a></li>
                            <li><a href="{{ route('time-slots.index') }}" class="{{ request()->routeIs('time-slots.*') ? 'active' : '' }}">Time Slots</a></li>
                            <li><a href="{{ route('bise.biseindex') }}" class="{{ request()->routeIs('bise.*') ? 'active' : '' }}">BISE Records</a></li>
                            <li><a href="{{ route('designations.index') }}" class="{{ request()->routeIs('designations.*') ? 'active' : '' }}">Designations</a></li>
                            <li><a href="{{ route('quota.index') }}" class="{{ request()->routeIs('quota.*') ? 'active' : '' }}">Quota Settings</a></li>
                            <li><a href="{{ route('bloodGroup.index') }}" class="{{ request()->routeIs('bloodGroup.*') ? 'active' : '' }}">Blood Groups</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Logistics & Alumni --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->is(['admin/assets*', 'admin/budgets*', 'admin/transport*', 'admin/hostel*', 'admin/alumni*']) ? 'active' : '' }}" data-target="hssc-ops">
                            <i class="fas fa-truck-loading"></i>
                            <span>Logistics & Alumni</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="hssc-ops">
                            <li><a href="{{ route('assets.index') }}" class="{{ request()->routeIs('assets.index') ? 'active' : '' }}">Asset Inventory</a></li>
                            <li><a href="{{ route('assets.categories') }}" class="{{ request()->routeIs('assets.categories') ? 'active' : '' }}">Asset Categories</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="{{ route('budgets.index') }}" class="{{ request()->routeIs('budgets.index') ? 'active' : '' }}">Budget Planning</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="{{ route('transport.index') }}" class="{{ request()->routeIs('transport.index') ? 'active' : '' }}">Transport Fleet</a></li>
                            <li><a href="{{ route('hostel.index') }}" class="{{ request()->routeIs('hostel.index') ? 'active' : '' }}">Hostel Mgmt</a></li>
                            <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                            <li><a href="{{ route('alumni.index') }}" class="{{ request()->routeIs('alumni.index') ? 'active' : '' }}">Alumni Directory</a></li>
                            <li><a href="{{ route('alumni.batches') }}" class="{{ request()->routeIs('alumni.batches') ? 'active' : '' }}">Graduation Batches</a></li>
                        </ul>
                    </li>
                    @endif
                @endif
                
                {{-- Academic Tasks (Teachers) --}}
                @if($activeRole == 'teacher')
                <li class="sidebar-heading">Personal Spaces</li>
                <li>
                    <a href="#" class="menu-toggle {{ request()->routeIs(['attendance.*', 'he.attendance.*', 'he.results.*', 'struck-off.*']) ? 'active' : '' }}" data-target="teacher-tasks-menu">
                        <i class="fas fa-tasks"></i>
                        <span>Academic Tasks</span>
                        <i class="fas fa-chevron-right menu-toggle-icon"></i>
                    </a>
                    <ul class="submenu" id="teacher-tasks-menu">
                        {{-- HSSC --}}
                        <li class="submenu-header text-muted small ps-3 mt-2 Review ps-3 mt-2 mb-1">HSSC</li>
                        <li><a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.index') ? 'active' : '' }}">Mark Attendance</a></li>
                        <li><a href="{{ route('attendance.reports.form') }}" class="{{ request()->routeIs('attendance.reports.*') ? 'active' : '' }}">Attendance History</a></li>
                        <li><a href="{{ route('struck-off.index') }}" class="{{ request()->routeIs('struck-off.index') ? 'active' : '' }}"><i class="fas fa-user-slash text-danger me-1"></i> Struck-Off Students</a></li>
                        <li><a href="{{ route('struck-off.teacher') }}" class="{{ request()->routeIs('struck-off.teacher') ? 'active' : '' }}"><i class="fas fa-user-times text-warning me-1"></i> Strike Off Eligible</a></li>
                        
                        {{-- ADP / BS --}}
                        <li class="submenu-header text-muted small ps-3 mt-2 mb-1">ADP / BS</li>
                        <li><a href="{{ route('he.attendance.index') }}" class="{{ request()->routeIs('he.attendance.index') ? 'active' : '' }}">Mark HE Attendance</a></li>
                        <li><a href="{{ route('he.results.create') }}" class="{{ request()->routeIs('he.results.create') ? 'active' : '' }}">HE Results Entry</a></li>
                    </ul>
                </li>
                @endif
            @endif

            {{-- ADP / BS Content --}}
            @if($sidebarView == 'BS')
                @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'teacher', 'bs_accountant']))
                    {{-- Student affairs --}}
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs(['admissions.*', 'he.roll-numbers.*']) || ($activeRole != 'teacher' && request()->routeIs('struck-off.*')) ? 'active' : '' }}" data-target="he-students-menu">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student affairs</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-students-menu">
                            <li><a href="{{ route('admissions.index') }}" class="{{ request()->routeIs('admissions.*') ? 'active' : '' }}">Students</a></li>
                            <li><a href="{{ route('he.roll-numbers.index') }}" class="{{ request()->routeIs('he.roll-numbers.*') ? 'active' : '' }}">University Roll Nos</a></li>
                            @if($activeRole != 'teacher')
                            <li><a href="{{ route('struck-off.index') }}" class="{{ request()->routeIs('struck-off.index') ? 'active' : '' }}"><i class="fas fa-user-slash text-danger me-1"></i> Struck-Off Students</a></li>
                            <li><a href="{{ route('struck-off.teacher') }}" class="{{ request()->routeIs('struck-off.teacher') ? 'active' : '' }}"><i class="fas fa-user-times text-warning me-1"></i> Strike Off Eligible</a></li>
                            @endif
                        </ul>
                    </li>

                    {{-- Staff Details --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'bs_accountant']))
                    <li>
                        <a href="#" class="menu-toggle {{ (request()->routeIs('staff.*') && !request()->routeIs(['staff.student_assignment', 'staff.award_list.*'])) ? 'active' : '' }}" data-target="he-staff-menu">
                            <i class="fas fa-user-tie"></i>
                            <span>Staff Details</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-staff-menu">
                            <li><a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.index') ? 'active' : '' }}">All Staff</a></li>
                            @if($activeRole != 'teacher')
                                <li><a href="{{ route('staff.create') }}" class="{{ request()->routeIs('staff.create') ? 'active' : '' }}">Add New</a></li>
                            @endif
                            @if(in_array($activeRole, ['admin', 'principal', 'super_admin']) || (isset($user) && $user->is_super_admin))
                                <li><a href="{{ route('leave-approvals.index') }}" class="{{ request()->routeIs('leave-approvals.*') ? 'active' : '' }}"><i class="fas fa-check-circle text-success me-1"></i> Leave Approvals</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    {{-- Examination --}}
                    @if($activeRole != 'teacher')
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs(['he.teacher_assignment*', 'he.attendance.*', 'he.datesheets.*', 'he.seating_plans.*', 'he.timetables.*']) ? 'active' : '' }}" data-target="he-exams-menu">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Examination</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-exams-menu">
                            @if($activeRole == 'teacher')
                                <li><a href="{{ route('he.attendance.index') }}" class="{{ request()->routeIs('he.attendance.*') ? 'active' : '' }}">Mark Attendance</a></li>
                            @else
                                <li><a href="{{ route('attendance.gate.index') }}" class="{{ request()->routeIs('attendance.gate.*') ? 'active' : '' }}">Gate Attendance Scanner</a></li>
                            @endif
                            <li><a href="{{ route('he.datesheets.index') }}" class="{{ request()->routeIs('he.datesheets.*') ? 'active' : '' }}">Datesheets</a></li>
                            <li><a href="{{ route('he.seating_plans.index') }}" class="{{ request()->routeIs('he.seating_plans.*') ? 'active' : '' }}">Seating Plans</a></li>
                            <li><a href="{{ route('he.timetables.index') }}" class="{{ request()->routeIs('he.timetables.*') ? 'active' : '' }}">Timetables</a></li>
                            <li><a href="{{ route('he.teacher_assignment') }}" class="{{ request()->routeIs('he.teacher_assignment') ? 'active' : '' }}">Teacher Assignment</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Invigilators --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('he.invigilators.*') ? 'active' : '' }}" data-target="he-invigilators-menu">
                            <i class="fas fa-user-shield"></i>
                            <span>Invigilators</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-invigilators-menu">
                            <li><a href="{{ route('he.invigilators.index') }}" class="{{ request()->routeIs('he.invigilators.index') ? 'active' : '' }}">Assignments</a></li>
                            <li><a href="{{ route('he.invigilators.create') }}" class="{{ request()->routeIs('he.invigilators.create') ? 'active' : '' }}">Auto-Assign Duty</a></li>
                            <li><a href="{{ route('he.invigilators.assign') }}" class="{{ request()->routeIs('he.invigilators.assign') ? 'active' : '' }}">Assign by Date</a></li>
                            <li><a href="{{ route('he.invigilators.duty_roster') }}" class="{{ request()->routeIs('he.invigilators.duty_roster') ? 'active' : '' }}">Duty Roster</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Results --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'bs_clerk', 'teacher', 'bs_admin']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('he.results.*') ? 'active' : '' }}" data-target="he-results-menu">
                            <i class="fas fa-poll"></i>
                            <span>Results</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-results-menu">
                            <li><a href="{{ route('he.results.index') }}" class="{{ request()->routeIs('he.results.index') ? 'active' : '' }}">Results Entry</a></li>
                            <li><a href="{{ route('he.results.summary') }}" class="{{ request()->routeIs('he.results.summary') ? 'active' : '' }}">Results Summary</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Custom Reports --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('he.reports.*') ? 'active' : '' }}" data-target="he-reports-menu">
                            <i class="fas fa-chart-pie"></i>
                            <span>Custom Reports</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-reports-menu">
                            <li><a href="{{ route('he.reports.award_list.form') }}" class="{{ request()->routeIs('he.reports.award_list.*') ? 'active' : '' }}">Award Lists</a></li>
                            <li><a href="{{ route('he.reports.tabulation_sheet.form') }}" class="{{ request()->routeIs('he.reports.tabulation_sheet.*') ? 'active' : '' }}">Tabulation Sheets</a></li>
                            <li><a href="{{ route('he.reports.transcript.search') }}" class="{{ request()->routeIs('he.reports.transcript.*') ? 'active' : '' }}">Transcripts</a></li>
                            <li><a href="{{ route('he.reports.attendance.form') }}" class="{{ request()->routeIs('he.reports.attendance.*') ? 'active' : '' }}">Attendance Reports</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Admissions --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('admission-applications.*') ? 'active' : '' }}" data-target="he-admissions-menu">
                            <i class="fas fa-door-open"></i>
                            <span>Admissions</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-admissions-menu">
                            <li><a href="{{ route('admission-applications.index') }}" class="{{ request()->routeIs('admission-applications.*') ? 'active' : '' }}">Applications</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Fee vouchers --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin', 'bs_accountant']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs('vouchers.*') ? 'active' : '' }}" data-target="he-vouchers-menu">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Fee vouchers</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-vouchers-menu">
                            <li><a href="{{ route('vouchers.index') }}" class="{{ request()->routeIs('vouchers.index') ? 'active' : '' }}">Voucher History</a></li>
                            @unless(in_array($activeRole, ['hssc_clerk', 'hssc_admin', 'hssc_accountant']))
                                <li><a href="{{ route('vouchers.bulk-he') }}" class="{{ request()->routeIs('vouchers.bulk-he') ? 'active' : '' }}">Bulk Semester Vouchers</a></li>
                            @endunless
                        </ul>
                    </li>
                    @endif

                    {{-- Academic Info --}}
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin']))
                    <li>
                        <a href="#" class="menu-toggle {{ request()->routeIs(['programs.*', 'bs-subjects.*', 'semesters.*']) ? 'active' : '' }}" data-target="he-academic-menu">
                            <i class="fas fa-university"></i>
                            <span>Academic Info</span>
                            <i class="fas fa-chevron-right menu-toggle-icon"></i>
                        </a>
                        <ul class="submenu" id="he-academic-menu">
                            <li><a href="{{ route('programs.index') }}" class="{{ request()->routeIs('programs.*') ? 'active' : '' }}">Programs</a></li>
                            <li><a href="{{ route('bs-subjects.index') }}" class="{{ request()->routeIs('bs-subjects.*') ? 'active' : '' }}">Subjects</a></li>
                            <li><a href="{{ route('semesters.index') }}" class="{{ request()->routeIs('semesters.*') ? 'active' : '' }}">Curriculum</a></li>
                        </ul>
                    </li>
                    @endif
                @endif
            @endif

            {{-- Global Learning & Communication --}}
            @if(in_array($activeRole, ['student', 'teacher', 'admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin']))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['lms.*', 'library.*']) ? 'active' : '' }}" data-target="learning-blocks">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Learning & Library</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="learning-blocks">
                    <li><a href="{{ route('lms.dashboard') }}" class="{{ request()->routeIs('lms.dashboard') ? 'active' : '' }}">LMS Dashboard</a></li>
                    <li><a href="{{ route('lms.assignments.index') }}" class="{{ request()->routeIs('lms.assignments.*') ? 'active' : '' }}">Assignments</a></li>
                    @if(in_array($activeRole, ['admin', 'principal', 'clerk']))
                        <li><a href="{{ route('library.index') }}" class="{{ request()->routeIs('library.index') ? 'active' : '' }}">Library Home</a></li>
                    @endif
                </ul>
            </li>
            @endif

            @if($user->is_super_admin || in_array($activeRole, ['admin', 'principal', 'clerk', 'bs_clerk', 'bs_admin']))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['notifications.*', 'announcements.*']) ? 'active' : '' }}" data-target="global-comm">
                    <i class="fas fa-bullhorn"></i>
                    <span>Communication</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="global-comm">
                    <li><a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">In-App Alerts</a></li>
                    <li><a href="{{ route('announcements.index') }}" class="{{ request()->routeIs('announcements.*') ? 'active' : '' }}">Bulk Announcements</a></li>
                </ul>
            </li>
            @endif

            {{-- Personal Items --}}
            @if(!$userRoles->contains('name', 'parent') && !$userRoles->contains('name', 'student'))
            <li>
                <a href="{{ route('my-leaves.index') }}" class="{{ request()->routeIs('my-leaves.*') ? 'active' : '' }}">
                    <i class="fas fa-suitcase-rolling"></i>
                    <span>My Leaves</span>
                </a>
            </li>
            <li>
                <a href="{{ route('public.duty_roster_lookup') }}" class="{{ request()->routeIs('public.duty_roster_lookup') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Duty Roster</span>
                </a>
            </li>
            @endif

            {{-- Website Management --}}
            @if(in_array($activeRole, ['admin', 'principal', 'clerk']))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['albums.*', 'updates.*', 'committees.*', 'principals.*', 'settings.*', 'downloads.*', 'admin.database.audit.*']) ? 'active' : '' }}" data-target="website-menu">
                    <i class="fas fa-globe"></i>
                    <span>Website Setup</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="website-menu">
                    <li><a href="{{ route('albums.index') }}" class="{{ request()->routeIs('albums.*') ? 'active' : '' }}">Photo Albums</a></li>
                    <li><a href="{{ route('updates.index') }}" class="{{ request()->routeIs('updates.*') ? 'active' : '' }}">News & Updates</a></li>
                    <li><a href="{{ route('committees.index') }}" class="{{ request()->routeIs('committees.*') ? 'active' : '' }}">Committees</a></li>
                    <li><a href="{{ route('principals.index') }}" class="{{ request()->routeIs('principals.*') ? 'active' : '' }}">Principals History</a></li>
                    <li><a href="{{ route('downloads.index') }}" class="{{ request()->routeIs('downloads.*') ? 'active' : '' }}">Downloads</a></li>
                    @if($activeRole == 'admin' || $user->is_super_admin)
                        <li style="border-top: 1px solid #23282d; margin: 5px 0;"></li>
                        <li><a href="{{ route('settings.edit') }}" class="{{ request()->routeIs('settings.edit') ? 'active' : '' }}">General Settings</a></li>
                        <li><a href="{{ route('settings.colors') }}" class="{{ request()->routeIs('settings.colors') ? 'active' : '' }}">Color Settings</a></li>
                        <li><a href="{{ route('admin.database.audit.index') }}" class="{{ request()->routeIs('admin.database.audit.*') ? 'active' : '' }}"><i class="fas fa-database text-warning me-1"></i> Database Audit</a></li>
                    @endif
                </ul>
            </li>
            @endif

            {{-- User Management --}}
            @if($user->is_super_admin || $userRoles->contains('name', 'principal'))
            <li>
                <a href="#" class="menu-toggle {{ request()->routeIs(['users.*', 'role-permissions.*']) ? 'active' : '' }}" data-target="users-menu-parent">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                    <i class="fas fa-chevron-right menu-toggle-icon"></i>
                </a>
                <ul class="submenu" id="users-menu-parent">
                    <li><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Manage Users</a></li>
                    <li><a href="{{ route('role-permissions.index') }}" class="{{ request()->routeIs('role-permissions.*') ? 'active' : '' }}">Role Permissions</a></li>
                </ul>
            </li>
            @endif

            {{-- Notifications (Available to all users) --}}
            <li>
                <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    @if(isset($topbarUnreadCount) && $topbarUnreadCount > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $topbarUnreadCount }}</span>
                    @elseif(Auth::check() && Auth::user()->unreadNotifications->count() > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ Auth::user()->unreadNotifications->count() }}</span>
                    @endif
                </a>
            </li>

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
                @php
                    $navUser = Auth::user();
                    $navPendingReminders = collect();
                    if ($navUser && $navUser->staff) {
                        $navPendingReminders = \App\Models\AttendanceReminder::with('subject')
                            ->where('staff_id', $navUser->staff->id)
                            ->where('is_used', false)
                            ->orderByDesc('created_at')
                            ->get();
                    }
                    $navUnreadDbCount = $navUser ? $navUser->unreadNotifications->count() : 0;
                    $topbarUnreadCount = $navUnreadDbCount + $navPendingReminders->count();
                    $recentNotifications = $navUser ? $navUser->notifications()->take(5)->get() : collect();
                @endphp

                {{-- Notification Bell Dropdown --}}
                <div class="dropdown me-2 notification-bell-container">
                    <button class="btn btn-light position-relative p-2 rounded-circle border-0 shadow-none notification-bell-btn" 
                            type="button" 
                            id="topbarNotificationDropdown" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false" 
                            title="Notifications">
                        <i class="fas fa-bell fs-5 {{ $topbarUnreadCount > 0 ? 'text-primary' : 'text-secondary' }}"></i>
                        @if($topbarUnreadCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white notification-pulse">
                                {{ $topbarUnreadCount > 99 ? '99+' : $topbarUnreadCount }}
                            </span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 notification-dropdown-menu p-0" 
                         aria-labelledby="topbarNotificationDropdown" 
                         style="width: 380px; max-width: 90vw; border-radius: 14px; overflow: hidden;">
                        
                        {{-- Dropdown Header --}}
                        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="fas fa-bell text-primary"></i> Notifications
                                    @if($topbarUnreadCount > 0)
                                        <span class="badge bg-danger rounded-pill small">{{ $topbarUnreadCount }} new</span>
                                    @endif
                                </h6>
                            </div>
                            @if($navUnreadDbCount > 0)
                                <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none small text-primary fw-semibold">
                                        Mark all read
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Dropdown Scrollable Content --}}
                        <div class="notification-dropdown-scroll" style="max-height: 380px; overflow-y: auto;">
                            {{-- Attendance Reminders for Teacher --}}
                            @if($navPendingReminders->isNotEmpty())
                                <div class="px-3 py-2 bg-warning bg-opacity-10 border-bottom border-warning">
                                    <div class="small fw-bold text-dark d-flex align-items-center gap-1">
                                        <i class="fas fa-clipboard-user text-warning"></i>
                                        Attendance Marking Reminders ({{ $navPendingReminders->count() }})
                                    </div>
                                </div>
                                @foreach($navPendingReminders as $rem)
                                    <div class="p-3 border-bottom bg-warning bg-opacity-10 position-relative">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="badge bg-warning text-dark fw-bold small">
                                                {{ $rem->class_level }}th Class ({{ $rem->admission_year }})
                                            </span>
                                            <span class="small text-danger fw-bold">
                                                {{ \Carbon\Carbon::parse($rem->attendance_date)->format('d M Y') }}
                                            </span>
                                        </div>
                                        <div class="fw-bold text-dark small mb-1">
                                            {{ $rem->subject->name ?? 'Subject' }}
                                            @if($rem->section)
                                                <span class="badge bg-info text-dark ms-1">Sec {{ $rem->section }}</span>
                                            @endif
                                        </div>
                                        <p class="small text-muted mb-2 lh-sm">
                                            One-time permission granted by Principal to mark attendance for this date.
                                        </p>
                                        <a href="{{ route('attendance.create', [
                                            'subject_id' => $rem->subject_id,
                                            'class' => $rem->class_level,
                                            'year' => $rem->admission_year,
                                            'date' => \Carbon\Carbon::parse($rem->attendance_date)->toDateString(),
                                            'section' => $rem->section,
                                            'reminder_id' => $rem->id
                                        ]) }}" class="btn btn-warning btn-sm w-100 fw-bold shadow-sm text-dark">
                                            <i class="fas fa-pen-fancy me-1"></i> Mark Attendance Now
                                        </a>
                                    </div>
                                @endforeach
                            @endif

                            {{-- System Notifications --}}
                            @if($recentNotifications->isNotEmpty())
                                <div class="list-group list-group-flush">
                                    @foreach($recentNotifications as $notif)
                                        @php
                                            $nData = $notif->data;
                                            $nUnread = $notif->unread();
                                        @endphp
                                        <div class="list-group-item list-group-item-action p-3 border-bottom {{ $nUnread ? 'bg-light' : '' }}">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div class="small">
                                                    @if(($nData['type'] ?? '') === 'mark_change')
                                                        <div class="fw-bold text-dark mb-1">
                                                            <span class="badge bg-warning text-dark me-1">Marks Changed</span>
                                                            {{ $nData['student_name'] ?? 'Student' }}
                                                        </div>
                                                        <div class="text-muted">
                                                            {{ $nData['subject'] ?? 'Subject' }}: {{ $nData['prev_theory'] ?? 'N/A' }} → {{ $nData['new_theory'] ?? 'N/A' }}
                                                        </div>
                                                    @elseif(!empty($nData['action']))
                                                        <div class="fw-bold text-dark mb-1">
                                                            <span class="badge {{ $nData['action'] === 'Assigned' ? 'bg-success' : 'bg-danger' }} me-1">{{ $nData['action'] }}</span>
                                                            {{ $nData['student_name'] ?? 'Student' }}
                                                        </div>
                                                        <div class="text-muted">
                                                            {{ $nData['subject_name'] ?? 'Subject' }} ({{ $nData['admission_year'] ?? '' }})
                                                        </div>
                                                    @else
                                                        <div class="fw-bold text-dark mb-1">
                                                            {{ $nData['subject'] ?? $nData['title'] ?? 'Notification' }}
                                                        </div>
                                                        <div class="text-muted">
                                                            {{ \Illuminate\Support\Str::limit($nData['body'] ?? $nData['message'] ?? '', 80) }}
                                                        </div>
                                                    @endif
                                                    <div class="text-muted extra-small mt-1" style="font-size: 0.75rem;">
                                                        <i class="far fa-clock me-1"></i>{{ $notif->created_at->diffForHumans() }}
                                                    </div>
                                                </div>
                                                @if($nUnread)
                                                    <form action="{{ route('notifications.markAsRead', $notif->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-link text-muted p-0" title="Mark as Read">
                                                            <i class="fas fa-circle text-primary" style="font-size: 0.55rem;"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($navPendingReminders->isEmpty() && $recentNotifications->isEmpty())
                                <div class="text-center py-4 px-3">
                                    <i class="fas fa-bell-slash text-muted fs-3 mb-2"></i>
                                    <p class="small text-muted mb-0">No new notifications</p>
                                </div>
                            @endif
                        </div>

                        {{-- Dropdown Footer --}}
                        <div class="p-2 bg-light border-top text-center">
                            <a href="{{ route('notifications.index') }}" class="btn btn-link btn-sm text-primary text-decoration-none fw-bold w-100">
                                View All Notifications <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

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