<?php $__env->startSection('title', 'My BS/ADP Classes'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-chalkboard-teacher me-2"></i>My BS/ADP Classes</h2>
            <p class="text-muted">Select a class to mark attendance.</p>
        </div>
    </div>

    <?php if($myClasses->count() > 0): ?>
        <div class="row">
            <?php $__currentLoopData = $myClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2"></i><?php echo e($class->subject_name); ?>

                            </h5>
                            <small class="opacity-75">(<?php echo e($class->subject_code); ?>)</small>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-graduation-cap text-muted me-2"></i>
                                    <strong>Program:</strong> <?php echo e($class->program_name); ?>

                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-layer-group text-muted me-2"></i>
                                    <strong>Semester:</strong> <?php echo e($class->current_semester); ?>

                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-calendar-alt text-muted me-2"></i>
                                    <strong>Year:</strong> <?php echo e($class->admission_year); ?>

                                </li>
                                <li>
                                    <i class="fas fa-users text-muted me-2"></i>
                                    <strong>Students:</strong> 
                                    <span class="badge bg-info"><?php echo e($class->student_count); ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="<?php echo e(route('he.attendance.create', [
                                'subject_id' => $class->subject_id,
                                'program_id' => $class->program_id,
                                'semester' => $class->current_semester,
                                'year' => $class->admission_year
                            ])); ?>" class="btn btn-success w-100">
                                <i class="fas fa-clipboard-check me-1"></i>Mark Attendance
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                <h4>No Classes Assigned</h4>
                <p class="text-muted">You don't have any BS/ADP classes assigned yet.<br>
                Please contact the administrator to get classes assigned.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/he/attendance/index.blade.php ENDPATH**/ ?>