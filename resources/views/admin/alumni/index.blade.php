@extends('layouts.app')

@section('title', 'Alumni Directory')

@section('content')
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-user-friends me-2"></i>Alumni Directory</h1>
            <p class="page-subtitle">Historical records of graduated students and their career progress.</p>
        </div>
        <a href="{{ route('alumni.batches') }}" class="btn btn-primary">
            <i class="fas fa-graduation-cap me-1"></i> Graduation Batches
        </a>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Student Name</th>
                            <th>Program</th>
                            <th>Graduation Year</th>
                            <th>Current City</th>
                            <th>Employer</th>
                            <th class="pe-4 text-end">Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alumni as $profile)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center">
                                        {{ substr($profile->student->first_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $profile->student->full_name }}</div>
                                        <div class="small text-muted">ID: {{ $profile->student->admission_no }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $profile->student->admissions->first()->program_name ?? 'N/A' }}</td>
                            <td>{{ $profile->student->graduation_records->first()->graduation_date->year ?? 'N/A' }}</td>
                            <td>{{ $profile->current_city ?? '---' }}</td>
                            <td>{{ $profile->employer ?? '---' }}</td>
                            <td class="pe-4 text-end">
                                <a href="mailto:{{ $profile->email }}" class="btn btn-sm btn-light"><i class="fas fa-envelope"></i></a>
                                <a href="tel:{{ $profile->mobile }}" class="btn btn-sm btn-light"><i class="fas fa-phone"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($alumni->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-search fs-1 mb-3 d-block"></i>
                    No alumni records found yet.
                </div>
            @endif
        </div>
        <div class="card-footer bg-white border-0">
            {{ $alumni->links() }}
        </div>
    </div>
</div>

<style>
.avatar-sm { width: 35px; height: 35px; font-size: 14px; }
</style>
@endsection
