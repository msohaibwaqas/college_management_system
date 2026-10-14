@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2>Assign Invigilators by Date</h2>
            <a href="{{ route('invigilators.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Date Selection -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5><i class="fas fa-calendar"></i> Select Exam Date</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label>Exam Date <span class="text-danger">*</span></label>
                    <select id="examDateSelect" class="form-control">
                        <option value="">Select Date</option>
                        @foreach($examDates as $date)
                            <option value="{{ $date->date->format('Y-m-d') }}">
                                {{ $date->date->format('d M Y (l)') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Invigilators Per Room <span class="text-danger">*</span></label>
                    <input type="number" id="invigilatorsPerRoom" class="form-control" min="1" max="4" value="2">
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button type="button" id="loadRoomsBtn" class="btn btn-primary btn-block">
                        <i class="fas fa-sync"></i> Load Rooms
                    </button>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" id="deleteAllBtn" class="btn btn-danger btn-block" style="display:none;">
                        <i class="fas fa-trash"></i> Delete All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rooms Assignment Section -->
    <div id="roomsSection" style="display:none;">
        <form id="assignmentForm" method="POST" action="{{ route('invigilators.store_bulk') }}">
            @csrf
            <input type="hidden" name="exam_date" id="hiddenExamDate">
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-users"></i> Assign Invigilators to Rooms</h5>
                </div>
                <div class="card-body">
                    <div id="roomsContainer"></div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Save All Assignments
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Staff data with duty counts
const staffData = @json($staffData);

let selectedStaffIds = new Set();
let roomsData = [];
let existingAssignments = {};

// Load rooms when button is clicked
document.getElementById('loadRoomsBtn').addEventListener('click', function() {
    const examDate = document.getElementById('examDateSelect').value;
    
    if (!examDate) {
        alert('Please select an exam date');
        return;
    }
    
    // Show loading
    document.getElementById('roomsContainer').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Loading rooms...</p></div>';
    document.getElementById('roomsSection').style.display = 'block';
    
    // Fetch rooms via AJAX
    fetch(`{{ route('invigilators.rooms_by_date') }}?date=${examDate}`)
        .then(response => response.json())
        .then(data => {
            roomsData = data.rooms;
            existingAssignments = data.existing_assignments;
            document.getElementById('hiddenExamDate').value = examDate;
            document.getElementById('deleteAllBtn').style.display = 'block';
            renderRooms();
        })
        .catch(error => {
            alert('Error loading rooms: ' + error.message);
            document.getElementById('roomsSection').style.display = 'none';
        });
});

// Render rooms with staff dropdowns
function renderRooms() {
    const container = document.getElementById('roomsContainer');
    const invigilatorsPerRoom = parseInt(document.getElementById('invigilatorsPerRoom').value);
    
    selectedStaffIds.clear();
    
    let html = '';
    
    roomsData.forEach((room, roomIndex) => {
        const roomKey = room.key;
        const planType = room.section.toLowerCase();
        
        html += `
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-door-open"></i> ${room.name} 
                        <span class="badge badge-${room.section == 'HSSC' ? 'info' : 'success'}">${room.section}</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row" id="room-${roomKey}">
        `;
        
        // Create dropdowns for each invigilator slot
        for (let i = 0; i < invigilatorsPerRoom; i++) {
            const existingStaffId = existingAssignments[roomKey] && existingAssignments[roomKey][i] 
                ? existingAssignments[roomKey][i].staff_id 
                : null;
            
            if (existingStaffId) {
                selectedStaffIds.add(existingStaffId);
            }
            
            html += `
                <div class="col-md-4 mb-2">
                    <label>Invigilator ${i + 1}</label>
                    <select name="assignments[${roomIndex}_${i}][staff_id]" class="form-control staff-select" data-room-key="${roomKey}" data-slot="${i}">
                        <option value="">Select Staff</option>
                    </select>
                    <input type="hidden" name="assignments[${roomIndex}_${i}][room_id]" value="${room.id}">
                    <input type="hidden" name="assignments[${roomIndex}_${i}][plan_id]" value="${room.plan_id}">
                    <input type="hidden" name="assignments[${roomIndex}_${i}][plan_type]" value="${planType}">
                </div>
            `;
        }
        
        html += `
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Populate all dropdowns
    populateAllDropdowns();
    
    // Add change event listeners
    document.querySelectorAll('.staff-select').forEach(select => {
        select.addEventListener('change', function() {
            updateAllDropdowns();
        });
    });
}

// Populate all staff dropdowns
function populateAllDropdowns() {
    document.querySelectorAll('.staff-select').forEach(select => {
        const currentValue = select.value;
        const roomKey = select.dataset.roomKey;
        const slot = select.dataset.slot;
        
        // Check if there's an existing assignment for this slot
        const existingStaff = existingAssignments[roomKey] && existingAssignments[roomKey][slot] 
            ? existingAssignments[roomKey][slot].staff_id 
            : null;
        
        let options = '<option value="">Select Staff</option>';
        
        staffData.forEach(staff => {
            // Skip if exempted
            if (staff.is_exempted) return;

            // Skip if duty limit reached (unless it's the current dropdown's value or existing assignment)
            if (staff.duty_count >= staff.max_limit && staff.id != currentValue && staff.id != existingStaff) return;
            
            // Skip if already selected in another dropdown (unless it's the current dropdown's value or existing assignment)
            if (selectedStaffIds.has(staff.id) && staff.id != currentValue && staff.id != existingStaff) return;
            
            let isSelected = false;
            if (currentValue) {
                isSelected = (staff.id == currentValue);
            } else {
                isSelected = (staff.id == existingStaff);
            }
            
            const selected = isSelected ? 'selected' : '';
            options += `<option value="${staff.id}" ${selected}>${staff.name} (${staff.designation}) - ${staff.duty_count}/${staff.max_limit}</option>`;
        });
        
        select.innerHTML = options;
    });
}

// Update all dropdowns when a selection changes
function updateAllDropdowns() {
    // Rebuild selected staff set
    selectedStaffIds.clear();
    document.querySelectorAll('.staff-select').forEach(select => {
        if (select.value) {
            selectedStaffIds.add(parseInt(select.value));
        }
    });
    
    // Repopulate all dropdowns
    populateAllDropdowns();
}

// Delete all assignments for selected date
document.getElementById('deleteAllBtn').addEventListener('click', function() {
    const examDate = document.getElementById('examDateSelect').value;
    
    if (!confirm(`Are you sure you want to delete ALL invigilator assignments for ${examDate}?`)) {
        return;
    }
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('invigilators.delete_by_date') }}';
    
    form.innerHTML = `
        @csrf
        @method('DELETE')
        <input type="hidden" name="exam_date" value="${examDate}">
    `;
    
    document.body.appendChild(form);
    form.submit();
});
</script>
@endsection