@extends('layouts.app')

@section('title', 'Parent Dashboard')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Welcome, {{ Auth::user()->name }}</h1>
            <p class="page-subtitle">Guardian Portal - Institution Management System</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary px-3 py-2">PARENT ACCOUNT</span>
        </div>
    </div>

    <div class="row mt-4">
        @foreach($students as $student)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm border-0 h-100 student-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-light me-3">
                                <i class="fas fa-user-graduate text-primary fs-3"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $student->full_name }}</h5>
                                <p class="text-muted small mb-0">Roll No: {{ $student->admission_no }}</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Attendance (This Month)</span>
                                <span class="fw-bold text-success">92%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 92%"></div>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Pending Fee</span>
                                <span class="fw-bold text-danger">PKR {{ number_format($student->vouchers()->where('status', 'pending')->sum('amount')) }}</span>
                            </div>
                        </div>

                        <a href="{{ route('parent.child', $student->id) }}" class="btn btn-outline-primary w-100">
                            View Child Profile <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($students->isEmpty())
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fas fa-info-circle me-2"></i> No linked students found. Please contact the admission office to link your children to this account.
        </div>
    @endif
</div>

<style>
.avatar-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.student-card {
    transition: box-shadow 0.2s;
}
.student-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}
</style>
@endsection
