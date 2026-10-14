@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>University Roll Numbers (HE)</h5>
        </div>
        
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="GET" action="{{ route('he.roll-numbers.index') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="program_id" class="form-label">Program</label>
                        <select name="program_id" id="program_id" class="form-select" required>
                            <option value="">Select Program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" {{ $programId == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="admission_year" class="form-label">Admission Year</label>
                        <select name="admission_year" id="admission_year" class="form-select" required>
                            <option value="">Select Year</option>
                            @foreach($admissionYears as $year)
                                <option value="{{ $year }}" {{ $admissionYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search (Optional)</label>
                        <input type="text" name="search" id="search" value="{{ $search }}" class="form-control" placeholder="Name or Roll No">
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Search Students
                        </button>
                        <a href="{{ route('he.roll-numbers.index') }}" class="btn btn-secondary ms-2">Reset</a>
                    </div>
                </div>
            </form>

            @if($programId && $admissionYear)
                <div class="alert alert-info mb-4">
                    <strong>Viewing Students:</strong> Program: {{ $programs->where('id', $programId)->first()->name ?? 'N/A' }}, Year: {{ $admissionYear }}
                </div>
            @endif

            @if(isset($students) && count($students) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>College Roll No</th>
                                <th>Student Name</th>
                                <th>Father Name</th>
                                <th>University Roll No</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $admission)
                                <tr>
                                    <td>{{ $students->firstItem() + $index }}</td>
                                    <td class="fw-bold">{{ $admission->roll_no ?? 'N/A' }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $admission->student->full_name }}</div>
                                        <small class="text-muted">ID: {{ $admission->student->id }}</small>
                                    </td>
                                    <td>{{ $admission->student->father->name ?? '-' }}</td>
                                    <td class="text-primary fw-bold">
                                        {{ $admission->uni_roll_no ?? 'Not Assigned' }}
                                    </td>
                                    <td>
                                        <form action="{{ route('he.roll-numbers.update') }}" method="POST" class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="he_admission_id" value="{{ $admission->id }}">
                                            <input type="text" name="uni_roll_no" value="{{ $admission->uni_roll_no }}" class="form-control form-control-sm" style="width: 180px;" placeholder="Assign Roll No" required>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $students->links() }}
                </div>
            @elseif($programId && $admissionYear)
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-user-slash fa-3x mb-3"></i>
                    <p>No students found for the selected Program and Admission Year.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
