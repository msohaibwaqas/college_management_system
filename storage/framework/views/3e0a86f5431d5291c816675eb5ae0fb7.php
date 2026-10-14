<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-list-alt me-2"></i>Subject Enrollment Report</h2>
            <p class="text-muted">View students enrolled in a specific subject by admission year</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Generate Report</h5>
                </div>
                <div class="card-body">
                    <form id="reportForm" method="POST" action="<?php echo e(route('reports.subject-enrollment.show')); ?>">
                        <?php echo csrf_field(); ?>
                        
                        <!-- Step 1: Select Admission Year -->
                        <div class="mb-4">
                            <label for="admission_year" class="form-label fw-bold">
                                <i class="fas fa-calendar me-2"></i>Step 1: Select Admission Year
                            </label>
                            <select name="admission_year" id="admission_year" class="form-select form-select-lg" required>
                                <option value="">-- Select Admission Year --</option>
                                <?php $__currentLoopData = $admissionYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($year); ?>"><?php echo e($year); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <!-- Step 2: Select Subject -->
                        <div class="mb-4">
                            <label for="subject_id" class="form-label fw-bold">
                                <i class="fas fa-book me-2"></i>Step 2: Select Subject
                            </label>
                            <select name="subject_id" id="subject_id" class="form-select form-select-lg" required disabled>
                                <option value="">-- First select an admission year --</option>
                            </select>
                            <div id="subjectLoading" class="text-center mt-2" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2">Loading subjects...</span>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
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
        $('#admission_year').change(function() {
            const year = $(this).val();
            const subjectSelect = $('#subject_id');
            const loading = $('#subjectLoading');

            if (year) {
                subjectSelect.prop('disabled', true).html('<option value="">Loading...</option>');
                loading.show();

                $.ajax({
                    url: '<?php echo e(route("reports.subject-enrollment.get-subjects")); ?>',
                    method: 'GET',
                    data: { admission_year: year },
                    success: function(subjects) {
                        loading.hide();
                        subjectSelect.html('<option value="">-- Select Subject --</option>');
                        subjectSelect.append('<option value="all_electives" class="fw-bold text-primary">📋 All Students (All Elective Subjects List)</option>');
                        
                        if (subjects.length > 0) {
                            let compulsoryGroup = '<optgroup label="Compulsory Subjects">';
                            let electiveGroup = '<optgroup label="Elective Subjects">';
                            let hasCompulsory = false;
                            let hasElective = false;

                            subjects.forEach(function(subject) {
                                const option = `<option value="${subject.id}">${subject.name} (${subject.code})</option>`;
                                if (subject.type === 'Compulsory') {
                                    compulsoryGroup += option;
                                    hasCompulsory = true;
                                } else {
                                    electiveGroup += option;
                                    hasElective = true;
                                }
                            });

                            compulsoryGroup += '</optgroup>';
                            electiveGroup += '</optgroup>';

                            let html = '';
                            if (hasCompulsory) html += compulsoryGroup;
                            if (hasElective) html += electiveGroup;

                            subjectSelect.append(html);
                            subjectSelect.prop('disabled', false);
                        } else {
                            subjectSelect.prop('disabled', false);
                        }
                    },
                    error: function() {
                        loading.hide();
                        subjectSelect.html('<option value="">Error loading subjects</option>');
                        alert('Failed to load subjects. Please try again.');
                    }
                });
            } else {
                subjectSelect.prop('disabled', true).html('<option value="">-- First select an admission year --</option>');
            }
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/reports/subject-enrollment/index.blade.php ENDPATH**/ ?>