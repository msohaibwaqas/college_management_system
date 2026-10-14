@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Subject Assignment Rules</h1>
        <a href="{{ route('subject-rules.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Create New Rule
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Configuration Rules</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Class Level</th>
                            <th>Nationality</th>
                            <th>Religion</th>
                            <th>Assignment Type</th>
                            <th>Assigned Subjects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rules as $rule)
                            <tr>
                                <td>{{ $rule->class_level ?: 'Any' }}</td>
                                <td><span class="badge bg-secondary text-white">{{ ucfirst($rule->nationality_type) }}</span></td>
                                <td><span class="badge bg-secondary text-white">{{ ucfirst($rule->religion_type) }}</span></td>
                                <td><span class="badge bg-info text-white">{{ ucfirst($rule->assignment_type) }}</span></td>
                                <td>
                                    @foreach($rule->subjects as $subject)
                                        <span class="badge bg-success text-white mb-1">{{ $subject->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <a href="{{ route('subject-rules.edit', $rule) }}" class="btn btn-sm btn-info text-white"><i class="fas fa-edit"></i> Edit</a>
                                    <form action="{{ route('subject-rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this Rule?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
