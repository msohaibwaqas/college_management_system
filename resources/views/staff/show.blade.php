@extends('layouts.app')
@section('title', 'Staff Details')

@section('content')
<style>
    .nav-tabs .nav-link {
        color: #000000 !important;
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        color: #000000 !important;
        font-weight: bold;
    }
</style>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-user me-2"></i>Staff Details</h2>
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
            <a href="{{ route('staff.edit', $staff) }}" class="btn btn-primary ms-2">
                <i class="fas fa-edit me-1"></i>Edit Staff
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="staffTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Info</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button" role="tab">Academic Qualifications</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="promotion-tab" data-bs-toggle="tab" data-bs-target="#promotion" type="button" role="tab">Promotion History</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab">Service History</button>
                        </li>
                        @if($staff->category == 'teaching')
                        <li class="nav-item">
                            <button class="nav-link" id="specialization-tab" data-bs-toggle="tab" data-bs-target="#specialization" type="button" role="tab">Qualifications (Teaching)</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">Current Assignments</button>
                        </li>
                        @endif
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="staffTabsContent">
                        <!-- Basic Information -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3 text-center border-end">
                                    <img src="{{ $staff->getProfilePictureUrl() }}" alt="Staff Photo" class="img-thumbnail rounded mb-3" style="width: 200px; height: 200px; object-fit: cover;">
                                    <h4>{{ $staff->first_name }} {{ $staff->last_name }}</h4>
                                    <p class="text-muted">{{ $staff->designation }} (BPS-{{ $staff->bps }})</p>
                                    <span class="badge {{ $staff->status == 'active' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($staff->status) }}</span>
                                </div>
                                <div class="col-md-9 mt-3 mt-md-0">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless">
                                                <tr><th width="40%">Staff ID:</th><td>{{ $staff->staff_id }}</td></tr>
                                                <tr><th>Father Name:</th><td>{{ $staff->father_name }}</td></tr>
                                                <tr><th>CNIC:</th><td>{{ $staff->cnic }}</td></tr>
                                                <tr><th>DOB:</th><td>{{ $staff->date_of_birth ? $staff->date_of_birth->format('d M, Y') : 'N/A' }}</td></tr>
                                                <tr><th>Gender:</th><td>{{ ucfirst($staff->gender) }}</td></tr>
                                                <tr><th>Blood Group:</th><td>{{ $staff->bloodGroup->code ?? 'N/A' }}</td></tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless">
                                                <tr><th width="40%">Phone:</th><td>{{ $staff->contact_number ?? 'N/A' }}</td></tr>
                                                <tr><th>WhatsApp:</th><td>{{ $staff->whatsapp_number ?? 'N/A' }}</td></tr>
                                                <tr><th>Email:</th><td>{{ $staff->email ?? 'N/A' }}</td></tr>
                                                <tr><th>Address:</th><td>{{ $staff->address ?? 'N/A' }}</td></tr>
                                                <tr><th>Category:</th><td>{{ ucfirst($staff->category) }}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row mt-2">
                                        <div class="col-md-6 text-center border-end">
                                            <h6>Entry Date (Govt Service)</h6>
                                            <p class="mb-0">{{ $staff->entry_date_govt_service ? $staff->entry_date_govt_service->format('d M, Y') : 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <h6>HED Joining Date</h6>
                                            <p class="mb-0">{{ $staff->hed_joining_date ? $staff->hed_joining_date->format('d M, Y') : 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Qualifications -->
                        <div class="tab-pane fade" id="academic" role="tabpanel">
                            <h5 class="mb-3">Academic Qualifications</h5>
                            @if($staff->academicQualifications->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Degree Level</th>
                                                <th>Subject/Major</th>
                                                <th>Board/University</th>
                                                <th>Total Marks</th>
                                                <th>Obtained</th>
                                                <th>% / Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($staff->academicQualifications as $qual)
                                                <tr>
                                                    <td>{{ $qual->formatted_degree_level }}</td>
                                                    <td>{{ $qual->subject }}</td>
                                                    <td>{{ $qual->group_field }}</td>
                                                    <td>{{ $qual->total_marks }}</td>
                                                    <td>{{ $qual->obtained_marks }}</td>
                                                    <td>{{ $qual->percentage }}% / {{ $qual->grade }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">No academic qualifications recorded.</div>
                            @endif
                        </div>

                        <!-- Promotion History -->
                        <div class="tab-pane fade" id="promotion" role="tabpanel">
                            <h5 class="mb-3">Promotion History</h5>
                            @if($staff->promotionHistory->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Order#</th>
                                                <th>Promotion Date</th>
                                                <th>Designation</th>
                                                <th>BPS</th>
                                                <th>Order Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($staff->promotionHistory as $promo)
                                                <tr>
                                                    <td>{{ $promo->promotion_order_number }}</td>
                                                    <td>{{ $promo->promotion_date ? $promo->promotion_date->format('d M, Y') : 'N/A' }}</td>
                                                    <td>{{ $promo->new_designation }}</td>
                                                    <td>{{ $promo->new_bps }}</td>
                                                    <td>{{ $promo->promotion_order_date ? $promo->promotion_order_date->format('d M, Y') : 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">No promotion history recorded.</div>
                            @endif
                        </div>

                        <!-- Service History -->
                        <div class="tab-pane fade" id="service" role="tabpanel">
                            <h5 class="mb-3">Previous Service History</h5>
                            @if($staff->serviceHistory->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Institution</th>
                                                <th>Designation</th>
                                                <th>BPS</th>
                                                <th>Period</th>
                                                <th>Reason for Leaving</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($staff->serviceHistory as $service)
                                                <tr>
                                                    <td>{{ $service->institution_name }}</td>
                                                    <td>{{ $service->designation }}</td>
                                                    <td>{{ $service->bps }}</td>
                                                    <td>{{ $service->start_date ? $service->start_date->format('M Y') : 'N/A' }} - {{ $service->is_current ? 'Present' : ($service->end_date ? $service->end_date->format('M Y') : 'N/A') }}</td>
                                                    <td>{{ $service->reason_for_leaving }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">No service history recorded.</div>
                            @endif
                        </div>

                        @if($staff->category == 'teaching')
                        <!-- Subject Specializations -->
                        <div class="tab-pane fade" id="specialization" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h5 class="mb-3">HSSC Specializations</h5>
                                    @if($staff->subjectSpecializations->count() > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($staff->subjectSpecializations as $spec)
                                                <span class="badge bg-info text-dark p-2">{{ $spec->subject->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No HSSC specializations assigned</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">BS/ADP Specializations</h5>
                                    @if($staff->bsSubjects->count() > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($staff->bsSubjects as $bsSubject)
                                                <span class="badge bg-primary text-white p-2">{{ $bsSubject->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No BS/ADP specializations assigned</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Current Assignments -->
                        <div class="tab-pane fade" id="assignments" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <h5>Current Student Assignments</h5>
                                    <div>
                                        <a href="{{ route('staff.student_assignment') }}" class="btn btn-sm btn-outline-primary me-2">Manage HSSC</a>
                                        <a href="{{ route('he.teacher_assignment') }}" class="btn btn-sm btn-outline-info">Manage BS/ADP</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                {{-- HSSC Assignments --}}
                                <div class="col-md-6 border-end">
                                    <h6 class="text-info"><i class="fas fa-users me-1"></i>HSSC Assignments</h6>
                                    @if($staff->studentTeacherSubjects->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Subject</th>
                                                        <th>Roll No</th>
                                                        <th>Student</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($staff->studentTeacherSubjects as $assign)
                                                        @if($assign->student)
                                                            <tr>
                                                                <td>{{ $assign->subject->name }}</td>
                                                                <td>{{ $assign->student->admissions->first()->college_roll_no ?? 'N/A' }}</td>
                                                                <td>{{ $assign->student->first_name }} {{ $assign->student->last_name }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-light py-2">No HSSC assignments found.</div>
                                    @endif
                                </div>

                                {{-- BS Assignments --}}
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-graduation-cap me-1"></i>BS/ADP Assignments</h6>
                                    @if($staff->heTeacherAssignments->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Subject</th>
                                                        <th>Roll No</th>
                                                        <th>Student</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($staff->heTeacherAssignments as $assign)
                                                        @if($assign->heAdmission && $assign->heAdmission->student)
                                                            <tr>
                                                                <td>{{ $assign->bsSubject->name ?? 'N/A' }}</td>
                                                                <td>{{ $assign->heAdmission->roll_no ?? 'N/A' }}</td>
                                                                <td>{{ $assign->heAdmission->student->first_name ?? 'N/A' }} {{ $assign->heAdmission->student->last_name ?? '' }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-light py-2">No BS/ADP assignments found.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
