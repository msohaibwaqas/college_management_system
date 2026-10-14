<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Generate HSSC Attendance Report</h4>
                </div>
                <div class="card-body p-4">
                    <form action="<?php echo e(route('attendance.reports.generate')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Admission Year <span class="text-danger">*</span></label>
                                <select name="admission_year" id="admission_year" class="form-control select2" required>
                                    <option value="">-- Select Year --</option>
                                    <?php $__currentLoopData = $admissionYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($year); ?>"><?php echo e($year); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Class <span class="text-danger">*</span></label>
                                <select name="current_class" id="current_class" class="form-control select2" required>
                                    <option value="">-- Select Class --</option>
                                    <option value="11">Class 11 (1st Year)</option>
                                    <option value="12">Class 12 (2nd Year)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                                <select name="subject_id" id="subject_id" class="form-control select2" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php $__currentLoopData = $subjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subject): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($subject->id); ?>"><?php echo e($subject->name); ?> (<?php echo e($subject->code); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo e(date('Y-m-01')); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo e(date('Y-m-d')); ?>" required>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-bold">
                                <i class="fas fa-file-invoice me-2"></i>Generate Attendance Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5'
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/attendance/reports/form.blade.php ENDPATH**/ ?>