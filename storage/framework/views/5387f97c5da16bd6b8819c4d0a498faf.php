<?php $__env->startSection('body-class', 'print-hidden-body'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <a href="<?php echo e(route('reports.subject-enrollment.index')); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Form
            </a>
        </div>
        <div class="col-auto">
            <form action="<?php echo e(route('reports.subject-enrollment.pdf')); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="admission_year" value="<?php echo e($admissionYear); ?>">
                <input type="hidden" name="subject_id" value="<?php echo e($subject->id); ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>Export as PDF
                </button>
            </form>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>
    </div>

    <div id="printable-area" class="card shadow-sm print-visible-area">
        <div class="card-body p-4">
            <!-- Header -->
            <div class="text-center mb-4 pb-3 border-bottom">
                <h3 class="fw-bold"><?php echo e(config('app.name', 'Government College')); ?></h3>
                <h5 class="text-muted">Subject Enrollment Report</h5>
            </div>

            <!-- Report Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Admission Year:</th>
                            <td><strong><?php echo e($admissionYear); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Subject Name:</th>
                            <td><strong><?php echo e($subject->name); ?></strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Subject Code:</th>
                            <td><strong><?php echo e($subject->code); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Subject Type:</th>
                            <td><span class="badge <?php echo e($subject->type == 'Compulsory' ? 'bg-info' : 'bg-warning'); ?>"><?php echo e($subject->type); ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if(isset($subject->id) && $subject->id === 'all_electives'): ?>
                <!-- All Students Elective Subjects Section -->
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    Student Elective Subjects List (Total Students: <?php echo e($students->count()); ?>)
                </h6>

                <?php if($students->count() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm align-middle print-table">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th class="text-center" style="width: 110px;">Roll No.</th>
                                    <th>Student Name</th>
                                    <th>Father Name</th>
                                    <th class="text-center" style="width: 70px;">Class</th>
                                    <th>Elective Subjects</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $admission = $student->admissions->first();
                                        $electivesList = $student->electives->pluck('subject.name')->filter()->implode(', ');
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo e($index + 1); ?></td>
                                        <td class="text-center font-monospace fw-bold"><?php echo e($admission ? $admission->college_roll_no : 'N/A'); ?></td>
                                        <td class="fw-semibold"><?php echo e($student->first_name); ?> <?php echo e($student->last_name); ?></td>
                                        <td><?php echo e(optional($student->father)->name ?? 'N/A'); ?></td>
                                        <td class="text-center"><?php echo e($admission ? $admission->current_class : '-'); ?></td>
                                        <td>
                                            <?php if($student->electives->count() > 0): ?>
                                                <?php $__currentLoopData = $student->electives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1">
                                                        <?php echo e(optional($e->subject)->name ?? 'Unknown'); ?>

                                                    </span>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">No electives assigned</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No students found for the selected admission year.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Roll Numbers Grid Section -->
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    Student Roll Numbers (Total: <?php echo e($students->count()); ?>)
                </h6>
                
                <?php if($students->count() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-top">
                            <tbody>
                                <?php $__currentLoopData = $students->chunk(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chunk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <?php $__currentLoopData = $chunk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <td class="text-center p-2" style="width: 10%; vertical-align: top; height: 50px;">
                                        <div class="fw-bold fs-6 mb-1 text-primary"><?php echo e($student->college_roll_no); ?></div>
                                        <?php if(!empty($student->elective_subjects)): ?>
                                            <div class="text-secondary small lh-sm" style="font-size: 0.72rem; word-break: break-word;">
                                                <?php echo e($student->elective_subjects); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($chunk->count() < 10): ?>
                                        <?php for($i = $chunk->count(); $i < 10; $i++): ?>
                                        <td class="text-center" style="width: 10%;">&nbsp;</td>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No students enrolled in this subject for the selected admission year.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Footer -->
            <div class="mt-4 pt-3 border-top">
                <small class="text-muted">
                    Generated on: <?php echo e(now()->format('F d, Y h:i A')); ?>

                </small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 12mm;
    }
    body * {
        visibility: hidden;
    }
    .print-visible-area, .print-visible-area * {
        visibility: visible;
    }
    .print-visible-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
    }
    .table {
        border-collapse: collapse !important;
        width: 100% !important;
    }
    .table th, .table td {
        border: 1px solid #333 !important;
        padding: 4px 6px !important;
        font-size: 11px !important;
        color: #000 !important;
    }
    .badge {
        border: none !important;
        background: transparent !important;
        color: #000 !important;
        padding: 0 !important;
        font-weight: normal !important;
    }
    .badge + .badge::before {
        content: ", ";
    }
    tr {
        page-break-inside: avoid;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/reports/subject-enrollment/report.blade.php ENDPATH**/ ?>