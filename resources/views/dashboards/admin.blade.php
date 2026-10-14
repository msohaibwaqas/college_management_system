@extends('layouts.app')

@section('styles')

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="dashboard-container">
    <!-- Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Overview of college performance and statistics</p>
            </div>
            <div class="col-auto d-flex align-items-center">
                <form action="{{ route('clear-cache') }}" method="POST" class="d-inline me-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to clear the system cache? This may temporarily slow down the first page load.')">
                        <i class="fas fa-broom me-1"></i> Clear Cache
                    </button>
                </form>
                <span class="text-muted"><i class="far fa-clock me-1"></i> {{ now()->format('F d, Y') }}</span>
            </div>
        </div>
    </div>

    @if(!empty($pendingLeavesCount) && $pendingLeavesCount > 0)
        <div class="alert alert-warning border border-warning shadow-sm mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning text-dark p-3 me-3">
                        <i class="fas fa-suitcase-rolling fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading mb-1 fw-bold text-dark"><i class="fas fa-bell me-1"></i>Staff Leave Applications Pending Approval</h5>
                        <p class="mb-0 text-secondary">You have <strong>{{ $pendingLeavesCount }}</strong> staff leave application(s) awaiting your decision.</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('leave-approvals.index', ['status' => 'pending']) }}" class="btn btn-warning fw-bold text-dark px-4 py-2 shadow-sm">
                        <i class="fas fa-check-double me-1"></i> Review & Approve Leaves ({{ $pendingLeavesCount }})
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Key Metrics -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-2">
            <div class="stat-card primary hover-scale">
                <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                <div class="stat-value text-primary">{{ number_format($totalStudents) }}</div>
                <div class="stat-label fw-bold">Total Students</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card success hover-scale">
                <div class="stat-icon text-success"><i class="fas fa-user-check"></i></div>
                <div class="stat-value text-success">{{ number_format($activeStudents) }}</div>
                <div class="stat-label fw-bold">Active Students</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card info hover-scale">
                <div class="stat-icon text-info"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-value text-info">{{ number_format($graduatedStudents) }}</div>
                <div class="stat-label fw-bold">Graduated</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card warning hover-scale">
                <div class="stat-icon text-warning"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-value text-warning">{{ number_format($activeTeachers) }}</div>
                <div class="stat-label fw-bold">Teachers</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card dark hover-scale">
                <div class="stat-icon text-secondary"><i class="fas fa-user-tie"></i></div>
                <div class="stat-value text-secondary">{{ number_format($totalStaff) }}</div>
                <div class="stat-label fw-bold">Total Staff</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card danger hover-scale">
                <div class="stat-icon text-danger"><i class="fas fa-book"></i></div>
                <div class="stat-value text-danger">{{ number_format($totalSubjects) }}</div>
                <div class="stat-label fw-bold">Subjects</div>
            </div>
        </div>
    </div>

    <!-- Student Demographics -->
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 small text-uppercase fw-bold"><i class="fas fa-graduation-cap me-2"></i>HSSC Statistics</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><i class="fas fa-mars me-2"></i>Male Students</span>
                        <span class="fw-bold fs-5 font-monospace text-primary">{{ number_format($hsscGender['Male'] ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <span class="text-muted"><i class="fas fa-venus me-2"></i>Female Students</span>
                        <span class="fw-bold fs-5 font-monospace text-danger">{{ number_format($hsscGender['Female'] ?? 0) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase small text-muted">Total HSSC</span>
                        <span class="fw-bold fs-4 text-primary">{{ number_format(array_sum($hsscGender)) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0 small text-uppercase fw-bold"><i class="fas fa-university me-2"></i>ADP Statistics</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><i class="fas fa-mars me-2"></i>Male Students</span>
                        <span class="fw-bold fs-5 font-monospace text-success">{{ number_format($adpGender['Male'] ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <span class="text-muted"><i class="fas fa-venus me-2"></i>Female Students</span>
                        <span class="fw-bold fs-5 font-monospace text-danger">{{ number_format($adpGender['Female'] ?? 0) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase small text-muted">Total ADP</span>
                        <span class="fw-bold fs-4 text-success">{{ number_format(array_sum($adpGender)) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-info text-white py-3">
                    <h5 class="mb-0 small text-uppercase fw-bold"><i class="fas fa-book-reader me-2"></i>BS Statistics</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><i class="fas fa-mars me-2"></i>Male Students</span>
                        <span class="fw-bold fs-5 font-monospace text-info">{{ number_format($bsGender['Male'] ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-0">
                        <span class="text-muted"><i class="fas fa-venus me-2"></i>Female Students</span>
                        <span class="fw-bold fs-5 font-monospace text-danger">{{ number_format($bsGender['Female'] ?? 0) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase small text-muted">Total BS</span>
                        <span class="fw-bold fs-4 text-info">{{ number_format(array_sum($bsGender)) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status & Database Management -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 border-start border-5 {{ $systemHealth['integrity'] ? 'border-success' : 'border-danger' }} h-100">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-3 border-end">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle p-3 {{ $systemHealth['integrity'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                        <i class="fas {{ $systemHealth['integrity'] ? 'fa-shield-alt' : 'fa-exclamation-triangle' }} fs-3"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1 small text-uppercase fw-bold">System Status</h6>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold fs-5">{{ $systemHealth['status'] }}</span>
                                        <span class="ms-2 badge {{ $systemHealth['integrity'] ? 'bg-success' : 'bg-danger' }} rounded-pill" style="font-size: 0.6rem;">SECURED</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 border-end ps-md-4">
                            <h6 class="text-muted mb-1 small text-uppercase fw-bold">Data Integrity</h6>
                            <div class="text-dark fw-bold">
                                <i class="fas fa-database me-1 text-primary"></i> 
                                {{ number_format($systemHealth['total_data']) }} Data Points Registered
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        <div class="col-md-3 border-end ps-md-4">
                            <h6 class="text-muted mb-1 small text-uppercase fw-bold">Engine Version</h6>
                            <div class="text-dark fw-bold">
                                <i class="fas fa-code-branch me-1 text-info"></i> 
                                V{{ $systemHealth['version'] }} (Stable)
                            </div>
                            <div class="small text-muted mt-1">Uptime: {{ $systemHealth['uptime'] }}</div>
                        </div>
                        <div class="col-md-3 ps-md-4 text-md-end">
                            <h6 class="text-muted mb-1 small text-uppercase fw-bold">Last Heartbeat</h6>
                            <div class="text-dark fw-bold">
                                <i class="fas fa-heartbeat me-1 text-danger"></i> 
                                {{ $systemHealth['heartbeat'] }} / Healthy
                            </div>
                            <div class="small text-muted mt-1">Network: Connected</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 border-start border-5 border-primary h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted mb-3 small text-uppercase fw-bold"><i class="fas fa-tools me-2 text-primary"></i>Database Management</h6>
                    <div class="d-grid gap-3">
                        <a href="{{ route('database.backup') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center py-2">
                            <i class="fas fa-download me-2"></i> Download Full Backup
                        </a>
                        
                        <form action="{{ route('database.restore') }}" method="POST" enctype="multipart/form-data" class="restore-form">
                            @csrf
                            <div class="input-group input-group-sm">
                                <input type="file" name="backup_file" class="form-control" accept=".sql,.gz,.sql.gz" required>
                                <button class="btn btn-danger" type="submit" onclick="return confirm('WARNING: This will overwrite the entire database. Are you sure?')">
                                    <i class="fas fa-upload me-1"></i> Restore
                                </button>
                            </div>
                            <div class="form-text text-center mt-1" style="font-size: 0.7rem;">Supported: .sql &amp; .sql.gz (Server Max Upload: {{ ini_get('upload_max_filesize') }})</div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Students Admitted per Year</h3>
                </div>
                <div class="chart-container">
                    <canvas id="admissionsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Active Students by Class</h3>
                </div>
                <div class="chart-container">
                    <canvas id="classDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Student Status</h3>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Subject Enrollment (Class 11 vs 12)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="subjectEnrollmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3: Gender Distribution by Program -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Gender Distribution by Program</h3>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="genderProgramChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Active Teachers per Subject</h3>
                </div>
                <div class="chart-container chart-container-large">
                    <canvas id="teachersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Religion Statistics Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header bg-dark text-white py-3 px-4 rounded-top">
                    <h3 class="chart-title mb-0 fs-5 text-white"><i class="fas fa-pray me-2"></i>Religion Statistics by Admission Year</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Admission Year</th>
                                <th class="text-center">Muslims</th>
                                <th class="text-center">Christians</th>
                                <th class="text-center">Sikhs</th>
                                <th class="text-center">Hindus</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($religionStats as $stat)
                            <tr>
                                <td class="ps-4 fw-bold text-primary">{{ $stat->admission_year }}</td>
                                <td class="text-center">{{ number_format($stat->muslims) }}</td>
                                <td class="text-center">{{ number_format($stat->christians) }}</td>
                                <td class="text-center">{{ number_format($stat->sikhs) }}</td>
                                <td class="text-center">{{ number_format($stat->hindus) }}</td>
                                <td class="text-center fw-bold bg-light">
                                    {{ number_format($stat->muslims + $stat->christians + $stat->sikhs + $stat->hindus) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No religion statistics available</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Admissions Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <h3 class="chart-title">Recent Admissions</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Roll No</th>
                                <th>Class</th>
                                <th>Admission Year</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAdmissions as $admission)
                            <tr>
                                <td class="fw-bold">{{ $admission->student->full_name ?? 'N/A' }}</td>
                                <td>{{ $admission->college_roll_no }}</td>
                                <td><span class="badge badge-custom badge-class">Class {{ $admission->current_class }}</span></td>
                                <td>{{ $admission->admission_year }}</td>
                                <td>
                                    @if($admission->status == 'Active')
                                        <span class="badge badge-custom badge-active">Active</span>
                                    @elseif($admission->status == 'Graduated')
                                        <span class="badge badge-custom badge-graduated">Graduated</span>
                                    @elseif($admission->status == 'Dropped' || $admission->status == 'Left')
                                        <span class="badge badge-custom badge-dropped">{{ $admission->status }}</span>
                                    @else
                                        <span class="badge badge-custom bg-secondary text-white">{{ $admission->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $admission->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No recent admissions found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Common Chart Options
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#64748b';
    
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    };

    // 1. Admissions per Year Chart
    const admissionsCtx = document.getElementById('admissionsChart').getContext('2d');
    const admissionsData = @json($studentsPerYear);
    
    new Chart(admissionsCtx, {
        type: 'bar',
        data: {
            labels: admissionsData.map(item => item.admission_year),
            datasets: [{
                label: 'Students Admitted',
                data: admissionsData.map(item => item.total),
                backgroundColor: '#3498db',
                borderRadius: 4,
                barThickness: 30
            }]
        },
        options: commonOptions
    });

    // 2. Active Students by Class Chart
    const classCtx = document.getElementById('classDistributionChart').getContext('2d');
    const classData = @json($activeStudentsByClass);
    
    new Chart(classCtx, {
        type: 'doughnut',
        data: {
            labels: classData.map(item => 'Class ' + item.current_class),
            datasets: [{
                data: classData.map(item => item.total),
                backgroundColor: ['#2ecc71', '#9b59b6', '#f1c40f'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20 }
                }
            },
            cutout: '70%'
        }
    });

    // 3. Student Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = @json($studentStatusDistribution);
    
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: statusData.map(item => item.status),
            datasets: [{
                data: statusData.map(item => item.total),
                backgroundColor: ['#27ae60', '#2980b9', '#c0392b', '#f39c12', '#7f8c8d'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20 }
                }
            }
        }
    });

    // 4. Subject Enrollment Chart (Stacked)
    const subjectCtx = document.getElementById('subjectEnrollmentChart').getContext('2d');
    const subjectData = @json($studentsPerSubjectByYear);
    
    // Sort by total students desc and take top 15 to avoid overcrowding
    const sortedSubjectData = subjectData.sort((a, b) => b.total - a.total).slice(0, 15);

    new Chart(subjectCtx, {
        type: 'bar',
        data: {
            labels: sortedSubjectData.map(item => item.subject_name),
            datasets: [
                {
                    label: 'Class 11',
                    data: sortedSubjectData.map(item => item.class_11),
                    backgroundColor: '#3498db',
                    borderRadius: 4
                },
                {
                    label: 'Class 12',
                    data: sortedSubjectData.map(item => item.class_12),
                    backgroundColor: '#2ecc71',
                    borderRadius: 4
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' } }
            }
        }
    });

    // 5. Teachers per Subject Chart
    const teachersCtx = document.getElementById('teachersChart').getContext('2d');
    const teachersData = @json($teachersPerSubject);
    
    // Fetch dynamic colors from institute settings
    @php
        $chartColors = \App\Models\InstituteSetting::getColors();
        $primaryColor = $chartColors['primary'] ?? '#3498db';
        $secondaryColor = $chartColors['secondary'] ?? '#2ecc71';
    @endphp

    // Filter out subjects with 0 teachers and sort
    const activeTeachersData = teachersData.filter(item => item.teacher_count > 0)
                                          .sort((a, b) => b.teacher_count - a.teacher_count);

    new Chart(teachersCtx, {
        type: 'bar',
        data: {
            labels: activeTeachersData.map(item => item.name),
            datasets: [{
                label: 'Active Teachers',
                data: activeTeachersData.map(item => item.teacher_count),
                backgroundColor: '{{ $primaryColor }}',
                hoverBackgroundColor: '{{ $secondaryColor }}',
                borderRadius: 6,
                barThickness: 15,
                maxBarThickness: 25
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Horizontal bar chart
            plugins: {
                legend: {
                    display: false // Hide legend to save space
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.x + ' Qualified Teachers';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#64748b'
                    },
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    }
                },
                y: {
                    ticks: {
                        color: '#334155',
                        font: { weight: '500' }
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            }
        }
    });

    // 6. Program Gender Distribution Chart
    const genderProgramCtx = document.getElementById('genderProgramChart').getContext('2d');
    
    new Chart(genderProgramCtx, {
        type: 'bar',
        data: {
            labels: ['HSSC', 'ADP', 'BS'],
            datasets: [
                {
                    label: 'Male',
                    data: [
                        {{ $hsscGender['Male'] ?? 0 }},
                        {{ $adpGender['Male'] ?? 0 }},
                        {{ $bsGender['Male'] ?? 0 }}
                    ],
                    backgroundColor: '#3498db',
                    borderRadius: 4
                },
                {
                    label: 'Female',
                    data: [
                        {{ $hsscGender['Female'] ?? 0 }},
                        {{ $adpGender['Female'] ?? 0 }},
                        {{ $bsGender['Female'] ?? 0 }}
                    ],
                    backgroundColor: '#e74c3c',
                    borderRadius: 4
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } }
            }
        }
    });
</script>
@endsection
