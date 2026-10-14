<?php $__env->startSection('title', 'Mark Attendance'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Mark Attendance: <?php echo e($subject->name); ?> (<?php echo e($class); ?>th Class)</h1>
        <a href="<?php echo e(route('attendance.index')); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Classes
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="<?php echo e(route('attendance.create')); ?>" method="GET" class="mb-4">
                <input type="hidden" name="subject_id" value="<?php echo e($subject->id); ?>">
                <input type="hidden" name="class" value="<?php echo e($class); ?>">
                <input type="hidden" name="year" value="<?php echo e($year); ?>">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?php echo e($date); ?>" required>
                    </div>
                    <?php if(isset($availableSections) && count($availableSections) > 0): ?>
                    <div class="col-md-4">
                        <label for="section" class="form-label">Filter by Section</label>
                        <select name="section" id="section" class="form-select">
                            <option value="">All Students (<?php echo e(count($students) + (isset($section) && $section ? \App\Models\StudentTeacherSubject::where('staff_id', auth()->user()->staff_id)->where('subject_id', $subject->id)->where('class_level', $class)->where('academic_year', $year)->count() - count($students) : 0)); ?>)</option>
                            <?php $__currentLoopData = $availableSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($sec); ?>" <?php echo e(isset($section) && $section == $sec ? 'selected' : ''); ?>>
                                    Section <?php echo e($sec); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <option value="unassigned" <?php echo e(isset($section) && $section == 'unassigned' ? 'selected' : ''); ?>>
                                Unassigned Students
                            </option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary d-block w-100">Apply Filter</button>
                    </div>
                </div>
            </form>

            <form action="<?php echo e(route('attendance.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="subject_id" value="<?php echo e($subject->id); ?>">
                <input type="hidden" name="attendance_date" value="<?php echo e($date); ?>">
                <input type="hidden" name="class" value="<?php echo e($class); ?>">
                <input type="hidden" name="year" value="<?php echo e($year); ?>">
                <?php if(isset($section) && $section): ?>
                    <input type="hidden" name="section" value="<?php echo e($section); ?>">
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-success mark-all" data-status="present">
                                <i class="fas fa-check me-1"></i>All Present
                            </button>
                            <button type="button" class="btn btn-outline-danger mark-all" data-status="absent">
                                <i class="fas fa-times me-1"></i>All Absent
                            </button>
                        </div>
                    </div>
                </div>

                <?php $__env->startSection('styles'); ?>
<style>
    .col-name-father {
        white-space: nowrap !important;
    }
    @media (max-width: 768px) {
        .col-name-father {
            white-space: nowrap !important;
            font-size: 0.8rem !important;
            vertical-align: middle;
        }
        .attendance-table th,
        .attendance-table td {
            padding: 0.45rem 0.4rem !important;
        }
        .attendance-table .col-name-father .badge {
            font-size: 0.65rem !important;
            padding: 0.25em 0.4em !important;
        }
        .attendance-table .btn-group .btn {
            padding: 0.25rem 0.4rem !important;
            font-size: 0.75rem !important;
        }
    }
</style>
<?php $__env->stopSection(); ?>

               
                <div class="table-responsive">
                    <table class="table table-bordered align-middle attendance-table">
                        <thead>
                            <tr class="table-light">
                                <th class="text-nowrap">Roll No</th>
                                <th class="text-center">Status</th>
                                <th class="text-center text-nowrap">History</th>
                                <th class="col-name-father">Name</th>
                                <th class="col-name-father">Father Name</th>
                                <th class="text-nowrap">Contact No.</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $status = $existingAttendance[$student->id]->status ?? 'absent';
                                    $remarks = $existingAttendance[$student->id]->remarks ?? '';
                                ?>
                                <tr style="<?php echo e($student->row_bg_style ?? ''); ?>">
                                    <td class="text-nowrap fw-bold"><?php echo e($student->roll_number); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check attendance-radio" name="attendance[<?php echo e($student->id); ?>]" id="present_<?php echo e($student->id); ?>" value="present" <?php echo e($status == 'present' ? 'checked' : ''); ?>>
                                            <label class="btn btn-outline-success btn-sm" for="present_<?php echo e($student->id); ?>">P</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[<?php echo e($student->id); ?>]" id="absent_<?php echo e($student->id); ?>" value="absent" <?php echo e($status == 'absent' ? 'checked' : ''); ?>>
                                            <label class="btn btn-outline-danger btn-sm" for="absent_<?php echo e($student->id); ?>">A</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[<?php echo e($student->id); ?>]" id="leave_<?php echo e($student->id); ?>" value="leave" <?php echo e($status == 'leave' ? 'checked' : ''); ?>>
                                            <label class="btn btn-outline-warning btn-sm" for="leave_<?php echo e($student->id); ?>">L</label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[<?php echo e($student->id); ?>]" id="late_<?php echo e($student->id); ?>" value="late" <?php echo e($status == 'late' ? 'checked' : ''); ?>>
                                            <label class="btn btn-outline-info btn-sm" for="late_<?php echo e($student->id); ?>">Late</label>
                                        </div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if(isset($student->recent_status_label)): ?>
                                            <span class="badge px-2 py-1 fw-normal" style="<?php echo e($student->recent_badge_style); ?>">
                                                <?php echo e($student->recent_status_label); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-white">No Record</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-name-father">
                                        <?php echo e($student->first_name); ?> <?php echo e($student->last_name); ?>

                                        <?php if(!empty($student->effective_section)): ?>
                                            <span class="badge bg-secondary text-white ms-2"><?php echo e($student->effective_section); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-name-father"><?php echo e($student->father_name); ?></td>
                                    <td class="text-nowrap small text-muted"><?php echo e($student->hssc_reg_no ?? '-'); ?></td>
                                    <td>
                                        <input type="text" name="remarks[<?php echo e($student->id); ?>]" class="form-control form-control-sm" value="<?php echo e($remarks); ?>" placeholder="Optional">
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No students found for this class.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($students->count() > 0): ?>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-1"></i> Save Attendance
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    $('.mark-all').on('click', function() {
        var status = $(this).data('status');
        $('input[value="' + status + '"].attendance-radio').prop('checked', true);
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/attendance/create.blade.php ENDPATH**/ ?>