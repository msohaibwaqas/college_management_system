@extends('layouts.app')

@section('title', 'My Performance')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Academic Performance</h1>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subject-wise Results</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Exam Type</th>
                                    <th>Month/Year</th>
                                    <th>Total</th>
                                    <th>Obtained</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $result)
                                <tr>
                                    <td>{{ $result->subject->name }}</td>
                                    <td>{{ $result->exam_type }}</td>
                                    <td>{{ $result->month }} / {{ $result->year }}</td>
                                    <td>{{ $result->total_marks }}</td>
                                    <td><strong class="text-primary">{{ $result->obtained_marks }}</strong></td>
                                    <td>
                                        @php $perc = ($result->obtained_marks / $result->total_marks) * 100; @endphp
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="progress progress-sm mr-2">
                                                    <div class="progress-bar bg-{{ $perc >= 50 ? 'success' : 'danger' }}" role="progressbar" style="width: {{ $perc }}%"></div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <span class="ms-2 small">{{ round($perc, 1) }}%</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">No results published yet.</td>
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
