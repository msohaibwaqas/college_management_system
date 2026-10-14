@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Manage BISE Roll Numbers</h5>
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

            <form method="GET" action="{{ route('bise-roll-numbers.index') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="admission_year" class="form-label">Admission Year</label>
                        <select name="admission_year" id="admission_year" class="form-select" required>
                            <option value="">Select Year</option>
                            @foreach($admissionYears as $year)
                                <option value="{{ $year }}" {{ $admissionYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="search_term" class="form-label">Search (Roll No, Name, or Father Name)</label>
                        <input type="text" name="search_term" id="search_term" value="{{ $searchTerm }}" class="form-control" placeholder="Enter College Roll No, Student Name, or Father Name">
                    </div>
                    
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                        <a href="{{ route('bise-roll-numbers.index') }}" class="btn btn-secondary ms-2">Reset</a>
                    </div>
                </div>
            </form>

            @if($admissionYear)
                <div class="alert alert-info mb-4">
                    @if($targetField == 'bise_roll_11')
                        <strong>Mode:</strong> Entering BISE 11th Class Roll Numbers (Admission Year: {{ $admissionYear }})
                    @elseif($targetField == 'bise_roll_12')
                        <strong>Mode:</strong> Entering BISE 12th Class Roll Numbers (Admission Year: {{ $admissionYear }})
                    @else
                        <strong>Notice:</strong> No BISE Roll Number entry available for this Admission Year based on current logic (Current Year: {{ $currentYear }}).
                    @endif
                </div>
            @endif

            @if(isset($students) && count($students) > 0 && $targetField)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>College Roll No</th>
                                <th>Student Name</th>
                                <th>Father Name</th>
                                <th>Elective Subjects</th>
                                <th>Current BISE Roll No</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $admission)
                                <tr>
                                    <td class="align-middle fw-bold">{{ $admission->college_roll_no }}</td>
                                    <td class="align-middle">{{ $admission->student->full_name ?? 'Deleted Student' }}</td>
                                    <td class="align-middle">{{ $admission->student->father->name ?? '-' }}</td>
                                    <td class="align-middle">
                                        @php
                                            $electiveSubjects = $admission->student ? $admission->student->electives->pluck('subject_name')->implode(', ') : '-';
                                        @endphp
                                        <small>{{ $electiveSubjects ?: '-' }}</small>
                                    </td>
                                    <td class="align-middle text-muted">
                                        {{ $admission->$targetField ?? 'Not Set' }}
                                    </td>
                                    <td class="align-middle">
                                        <form action="{{ route('bise-roll-numbers.update') }}" method="POST" class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="admission_id" value="{{ $admission->id }}">
                                            <input type="hidden" name="target_field" value="{{ $targetField }}">
                                            <input type="text" name="bise_roll_no" value="{{ $admission->$targetField }}" class="form-control form-control-sm" style="width: 150px;" placeholder="Enter Roll No" required>
                                            <button type="submit" class="btn btn-success btn-sm">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($searchTerm)
                <div class="text-center py-4 text-muted">
                    No students found matching criteria.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
