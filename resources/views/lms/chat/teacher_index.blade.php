@extends('layouts.app')

@section('title', 'Messenger - Student List')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Students</h1>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Students Assigned to You</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Admission No</th>
                                    <th>Class</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
                                        <td class="d-flex align-items-center">
                                            <img src="{{ optional($student->user)->profile_picture_url ?? asset('uploads/default.jpg') }}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <div class="font-weight-bold">
                                                    {{ $student->full_name }}
                                                    @if($student->user && $student->user->unread_count > 0)
                                                        <span class="badge bg-danger rounded-pill ms-1" title="New messages">
                                                            {{ $student->user->unread_count }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $student->admission_no }}</td>
                                        <td>{{ $student->current_class }}</td>
                                        <td>
                                            @if($student->user)
                                            <a href="{{ route('lms.messages.show', ['recipient' => $student->user->id]) }}" class="btn btn-sm btn-primary px-3">
                                                <i class="fas fa-comment shadow-sm"></i> Chat
                                            </a>
                                            @else
                                            <span class="badge bg-secondary">No Account</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">No students assigned to you yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
