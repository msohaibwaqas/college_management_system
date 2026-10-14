<?php $__env->startSection('title', 'Mark Attendance'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Mark Attendance</h1>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select Class to Mark Attendance</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Admission Year</th>
                            <th>Students</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $myClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($class->subject_name); ?></td>
                            <td><?php echo e($class->current_class); ?>th</td>
                            <td><?php echo e($class->admission_year); ?></td>
                            <td>
                                <span class="badge bg-info text-white me-1" title="Total Students"><?php echo e($class->student_count); ?></span>
                                <?php if(isset($class->section_count) && $class->section_count > 0): ?>
                                    <span class="badge bg-secondary text-white" title="Total Sections"><?php echo e($class->section_count); ?> Sections</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo e(route('attendance.create', [
                                    'subject_id' => $class->subject_id, 
                                    'class' => $class->current_class, 
                                    'year' => $class->admission_year
                                ])); ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-check-circle me-1"></i> Mark Attendance
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center">No classes assigned yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/attendance/index.blade.php ENDPATH**/ ?>