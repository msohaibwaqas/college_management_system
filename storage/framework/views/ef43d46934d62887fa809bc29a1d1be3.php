<?php $__env->startSection('title', 'Teacher Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Teacher Dashboard</h1>
    </div>

    <div class="row">
        <?php if(isset($error)): ?>
            <div class="col-12">
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            </div>
        <?php endif; ?>

        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 hover-scale">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">My Assigned Classes (Total)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($myClasses->count() + $myHeClasses->count()); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 hover-scale">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Students Assigned</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($myClasses->sum('student_count') + $myHeClasses->sum('student_count')); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4 border-left-primary">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Assigned HSSC Classes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Year</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $myClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($class->subject_name); ?></td>
                                    <td><?php echo e($class->current_class); ?>th</td>
                                    <td><?php echo e($class->admission_year); ?></td>
                                    <td><span class="badge bg-info text-white"><?php echo e($class->student_count); ?></span></td>
                                    <td>
                                        <a href="<?php echo e(route('attendance.create', [
                                            'subject_id' => $class->subject_id, 
                                            'class' => $class->current_class, 
                                            'year' => $class->admission_year
                                        ])); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-check me-1"></i> Attendance
                                        </a>
                                        <a href="<?php echo e(route('attendance.report.generate', [
                                            'subject_id' => $class->subject_id,
                                            'current_class' => $class->current_class,
                                            'admission_year' => $class->admission_year,
                                            'start_date' => date('Y-m-01'),
                                            'end_date' => date('Y-m-t')
                                        ])); ?>" class="btn btn-sm btn-info text-white">
                                            <i class="fas fa-file-alt me-1"></i> Report
                                        </a>
                                        <a href="<?php echo e(route('results.create', [
                                            'subject_id' => $class->subject_id,
                                            'class' => $class->current_class,
                                            'year' => $class->admission_year
                                        ])); ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-marker me-1"></i> Marks
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No HSSC classes assigned yet.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">My BS/ADP Assigned Classes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th>Subject</th>
                                    <th>Semester</th>
                                    <th>Year</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $myHeClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($class->program_name); ?></td>
                                    <td><?php echo e($class->subject_name); ?> (<?php echo e($class->subject_code); ?>)</td>
                                    <td><?php echo e($class->current_semester); ?><sup><?php echo e(match((int)$class->current_semester) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' }); ?></sup></td>
                                    <td><?php echo e($class->admission_year); ?></td>
                                    <td><span class="badge bg-success text-white"><?php echo e($class->student_count); ?></span></td>
                                    <td>
                                        <a href="<?php echo e(route('he.attendance.create', [
                                            'subject_id' => $class->subject_id, 
                                            'program_id' => $class->program_id,
                                            'semester' => $class->current_semester,
                                            'year' => $class->admission_year
                                        ])); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-user-check me-1"></i> Attendance
                                        </a>
                                        <a href="<?php echo e(route('he.attendance.report', [
                                            'subject_id' => $class->subject_id,
                                            'program_id' => $class->program_id,
                                            'semester' => $class->current_semester,
                                            'start_date' => date('Y-m-01'),
                                            'end_date' => date('Y-m-t')
                                        ])); ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-file-alt me-1"></i> Report
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-marker me-1"></i> Marks
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?php echo e(route('he.results.create', [
                                                    'bs_subject_id' => $class->subject_id, 
                                                    'program_id' => $class->program_id,
                                                    'semester_no' => $class->current_semester,
                                                    'academic_year' => $class->admission_year,
                                                    'exam_type' => 'Mid'
                                                ])); ?>">Mid Term</a></li>
                                                <li><a class="dropdown-item" href="<?php echo e(route('he.results.create', [
                                                    'bs_subject_id' => $class->subject_id, 
                                                    'program_id' => $class->program_id,
                                                    'semester_no' => $class->current_semester,
                                                    'academic_year' => $class->admission_year,
                                                    'exam_type' => 'Sessional'
                                                ])); ?>">Sessional</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No BS/ADP classes assigned yet.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/dashboards/teacher.blade.php ENDPATH**/ ?>