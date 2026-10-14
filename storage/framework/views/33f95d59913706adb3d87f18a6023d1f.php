<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HSSC Attendance Report - <?php echo e($subject->name); ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; margin: 30px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .data-table th { background-color: #f2f2f2; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Report</button>
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer;">Back</button>
    </div>

    <div class="header">
        <?php if($settings->logo_path): ?>
            <img src="<?php echo e($settings->logo_url); ?>" alt="Logo" style="max-height: 80px; margin-bottom: 10px;">
        <?php endif; ?>
        <h2><?php echo e($settings->full_name ?? 'INSTITUTE NAME'); ?></h2>
        <h3>HSSC Attendance Summary Report</h3>
        <h4>From: <?php echo e(\Carbon\Carbon::parse($startDate)->format('d M Y')); ?> To: <?php echo e(\Carbon\Carbon::parse($endDate)->format('d M Y')); ?></h4>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Class:</strong></td>
            <td width="35%">Class <?php echo e($class); ?> (Year <?php echo e($year); ?>)</td>
            <td width="15%"><strong>Subject:</strong></td>
            <td width="35%"><?php echo e($subject->name); ?> (<?php echo e($subject->code); ?>)</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">S#</th>
                <th width="10%">Roll No</th>
                <th width="30%" style="text-align: left;">Student Name</th>
                <th width="10%">Present</th>
                <th width="10%">Absent</th>
                <th width="10%">Leave</th>
                <th width="10%">Total Lectures</th>
                <th width="15%">Attendance %</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $admission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $studentAttendance = $attendanceData->get($admission->student_id, collect());
                    $present = $studentAttendance->where('status', 'present')->count();
                    $absent = $studentAttendance->where('status', 'absent')->count();
                    $leave = $studentAttendance->where('status', 'leave')->count();
                    $late = $studentAttendance->where('status', 'late')->count();
                    
                    $presentTotal = $present + $late; // Late counts as present
                    $total = $studentAttendance->count();
                    $percentage = $total > 0 ? round(($presentTotal / $total) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?php echo e($index + 1); ?></td>
                    <td><?php echo e($admission->college_roll_no); ?></td>
                    <td style="text-align: left;"><?php echo e($admission->student->full_name); ?></td>
                    <td><?php echo e($present); ?> <?php if($late > 0): ?> <small class="text-muted">(+<?php echo e($late); ?> Late)</small> <?php endif; ?></td>
                    <td><?php echo e($absent); ?></td>
                    <td><?php echo e($leave); ?></td>
                    <td><?php echo e($total); ?></td>
                    <td style="font-weight: bold; <?php echo e($percentage < 75 ? 'color: #dc3545;' : ''); ?>">
                        <?php echo e($percentage); ?>%
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; font-size: 11px;">
        <p><strong>Note:</strong> Minimum attendance required for examination is usually 75%. Students highlighted in red are short of attendance.</p>
    </div>

    <div style="margin-top: 60px; display: flex; justify-content: space-around;">
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>Subject Teacher</strong>
        </div>
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>Section In-Charge</strong>
        </div>
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <strong>Principal</strong>
        </div>
    </div>
</body>
</html>
<?php /**PATH /home/sohaib-waqas/Music/cms/resources/views/attendance/reports/report.blade.php ENDPATH**/ ?>