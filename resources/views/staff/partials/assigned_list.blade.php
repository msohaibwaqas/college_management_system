@if($students->isEmpty())
    <div class="p-3 text-center text-muted">
        <i class="fas fa-info-circle fa-2x mb-2"></i>
        <p>No students assigned to this teacher for this subject.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light sticky-top">
                <tr>
                    <th width="40">
                        <input type="checkbox" id="selectAllAssigned" onclick="$('.assigned-checkbox').prop('checked', this.checked)">
                    </th>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>Father Name</th>
                    <th>Section</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                    @php $sec = $student->subjectSections->first()?->section; @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="assigned-checkbox" value="{{ $student->id }}">
                        </td>
                        <td><strong>{{ $student->admissions->first()->college_roll_no ?? 'N/A' }}</strong></td>
                        <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                        <td>{{ $student->father->name ?? 'N/A' }}</td>
                        <td>
                            @if($sec)
                                <span class="badge bg-success">{{ $sec }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-2 bg-light border-top">
        <small class="text-muted">{{ $students->count() }} students assigned</small>
    </div>
@endif
