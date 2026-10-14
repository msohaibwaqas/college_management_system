@extends('layouts.app')
@section('title', 'Subject Group Details')

@section('content')
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('subject-groups.index') }}">Subject Groups</a></li>
            <li class="breadcrumb-item active">{{ $subjectGroup->name }}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Group Info</h5>
                    <a href="{{ route('subject-groups.edit', $subjectGroup) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small d-block">Group Name</label>
                        <span class="fs-5 fw-bold">{{ $subjectGroup->name }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small d-block">Description</label>
                        <span>{{ $subjectGroup->description ?: 'No description provided.' }}</span>
                    </div>
                    <div class="text-muted small">
                        Created on: {{ $subjectGroup->created_at->format('M d, Y') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Included Subjects</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject Name</th>
                                <th>Code</th>
                                <th>Class Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjectGroup->subjects as $subject)
                                <tr>
                                    <td>{{ $subject->name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $subject->code }}</span></td>
                                    <td>{{ $subject->class_level }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
