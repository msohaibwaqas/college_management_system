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
            <div class="col-auto">
                <span class="text-muted"><i class="far fa-clock me-1"></i> {{ now()->format('F d, Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-2">
            <div class="stat-card primary">
                <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                <div class="stat-value">{{ number_format($totalStudents) }}</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card success">
                <div class="stat-icon text-success"><i class="fas fa-user-check"></i></div>
                <div class="stat-value">{{ number_format($activeStudents) }}</div>
                <div class="stat-label">Active Students</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card info">
                <div class="stat-icon text-info"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-value">{{ number_format($graduatedStudents) }}</div>
                <div class="stat-label">Graduated</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card warning">
                <div class="stat-icon text-warning"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-value">{{ number_format($activeTeachers) }}</div>
                <div class="stat-label">Active Teachers</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card dark">
                <div class="stat-icon text-secondary"><i class="fas fa-user-tie"></i></div>
                <div class="stat-value">{{ number_format($totalStaff) }}</div>
                <div class="stat-label">Total Staff</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="stat-card danger">
                <div class="stat-icon text-danger"><i class="fas fa-book"></i></div>
                <div class="stat-value">{{ number_format($totalSubjects) }}</div>
                <div class="stat-label">Subjects</div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
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
                backgroundColor: '#e67e22',
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Horizontal bar chart
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
                x: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>
@endsection
