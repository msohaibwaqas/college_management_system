<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDatabaseOrphans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:audit-orphans {--fix : Delete or fix orphaned records automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and clean orphaned records and invalid section strings across database tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldFix = $this->option('fix');

        $this->info("=================================================");
        $this->info("      DATABASE ORPHAN RECORDS AUDIT TOOL         ");
        $this->info("=================================================");

        if ($shouldFix) {
            $this->warn("MODE: AUTO-FIX ENABLED (Invalid records will be cleaned)");
        } else {
            $this->info("MODE: DRY RUN (Run with --fix to clean records)");
        }

        $auditLog = [];

        // 1. Invalid combined section text containing '+' in student_teacher_subjects
        $invalidStsPlus = DB::table('student_teacher_subjects')
            ->where('attendance_section', 'like', '%+%')
            ->count();
        $auditLog[] = ["student_teacher_subjects (Invalid '+' section text)", $invalidStsPlus];

        if ($shouldFix && $invalidStsPlus > 0) {
            DB::table('student_teacher_subjects')
                ->where('attendance_section', 'like', '%+%')
                ->update(['attendance_section' => null]);
            $this->info("✔ Fixed {$invalidStsPlus} invalid '+' section strings (set to NULL).");
        }

        // 1b. Mismatched section strings between student_teacher_subjects and student_subject_sections
        $mismatchedSections = DB::table('student_teacher_subjects as sts')
            ->join('student_subject_sections as sss', function($j) {
                $j->on('sts.student_id', '=', 'sss.student_id')
                  ->on('sts.subject_id', '=', 'sss.subject_id');
            })
            ->whereColumn('sts.attendance_section', '!=', 'sss.section')
            ->count();
        $auditLog[] = ["student_teacher_subjects (Mismatched section names)", $mismatchedSections];

        if ($shouldFix && $mismatchedSections > 0) {
            DB::statement("
                UPDATE student_teacher_subjects sts
                INNER JOIN student_subject_sections sss 
                    ON sts.student_id = sss.student_id 
                   AND sts.subject_id = sss.subject_id
                SET sts.attendance_section = sss.section
                WHERE sts.attendance_section != sss.section
            ");
            $this->info("✔ Synced {$mismatchedSections} mismatched section strings to match student_subject_sections.");
        }

        // 2. StudentTeacherSubject with missing Student
        $stsNoStudent = DB::table('student_teacher_subjects as sts')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'sts.student_id');
            })->count();
        $auditLog[] = ["student_teacher_subjects (Missing Student)", $stsNoStudent];

        if ($shouldFix && $stsNoStudent > 0) {
            DB::table('student_teacher_subjects')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_teacher_subjects.student_id');
                })->delete();
            $this->info("✔ Cleaned {$stsNoStudent} orphaned teacher assignment records (No Student).");
        }

        // 3. StudentSubjectSection with missing Student
        $sssNoStudent = DB::table('student_subject_sections as sss')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'sss.student_id');
            })->count();
        $auditLog[] = ["student_subject_sections (Missing Student)", $sssNoStudent];

        if ($shouldFix && $sssNoStudent > 0) {
            DB::table('student_subject_sections')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_subject_sections.student_id');
                })->delete();
            $this->info("✔ Cleaned {$sssNoStudent} orphaned student section records (No Student).");
        }

        // 4. StudentElectiveSubject with missing Student
        $sesNoStudent = DB::table('student_elective_subjects as ses')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'ses.student_id');
            })->count();
        $auditLog[] = ["student_elective_subjects (Missing Student)", $sesNoStudent];

        if ($shouldFix && $sesNoStudent > 0) {
            DB::table('student_elective_subjects')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'student_elective_subjects.student_id');
                })->delete();
            $this->info("✔ Cleaned {$sesNoStudent} orphaned elective subject enrollments (No Student).");
        }

        // 5. StudentElectiveSubject with missing Subject
        $sesNoSubject = DB::table('student_elective_subjects as ses')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'ses.subject_id');
            })->count();
        $auditLog[] = ["student_elective_subjects (Missing Subject)", $sesNoSubject];

        if ($shouldFix && $sesNoSubject > 0) {
            DB::table('student_elective_subjects')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'student_elective_subjects.subject_id');
                })->delete();
            $this->info("✔ Cleaned {$sesNoSubject} orphaned elective subject enrollments (No Subject).");
        }

        // 6. TimetableEntry with missing Subject
        $ttNoSubject = DB::table('timetable_entries as te')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('subjects as sub')->whereColumn('sub.id', 'te.subject_id');
            })->count();
        $auditLog[] = ["timetable_entries (Missing Subject)", $ttNoSubject];

        if ($shouldFix && $ttNoSubject > 0) {
            DB::table('timetable_entries')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('subjects')->whereColumn('subjects.id', 'timetable_entries.subject_id');
                })->delete();
            $this->info("✔ Cleaned {$ttNoSubject} orphaned timetable_entries (No Subject).");
        }

        // 7. TimetableEntry with missing Staff
        $ttNoStaff = DB::table('timetable_entries as te')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('staff as st')->whereColumn('st.id', 'te.staff_id');
            })->count();
        $auditLog[] = ["timetable_entries (Missing Teacher)", $ttNoStaff];

        if ($shouldFix && $ttNoStaff > 0) {
            DB::table('timetable_entries')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('staff')->whereColumn('staff.id', 'timetable_entries.staff_id');
                })->delete();
            $this->info("✔ Cleaned {$ttNoStaff} orphaned timetable_entries (No Teacher).");
        }

        // 8. TimetableEntry with missing Room
        $ttNoRoom = DB::table('timetable_entries as te')
            ->whereNotNull('te.room_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('rooms as r')->whereColumn('r.id', 'te.room_id');
            })->count();
        $auditLog[] = ["timetable_entries (Missing Room)", $ttNoRoom];

        if ($shouldFix && $ttNoRoom > 0) {
            DB::table('timetable_entries')
                ->whereNotNull('room_id')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('rooms')->whereColumn('rooms.id', 'timetable_entries.room_id');
                })->update(['room_id' => null]);
            $this->info("✔ Reset {$ttNoRoom} timetable_entries with invalid Room ID to NULL.");
        }

        // 9. Attendances with missing Student
        $attNoStudent = DB::table('attendances as a')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'a.student_id');
            })->count();
        $auditLog[] = ["attendances (Missing Student)", $attNoStudent];

        if ($shouldFix && $attNoStudent > 0) {
            DB::table('attendances')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'attendances.student_id');
                })->delete();
            $this->info("✔ Cleaned {$attNoStudent} orphaned attendance records.");
        }

        // 10. Results with missing Student
        $resNoStudent = DB::table('results as r')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'r.student_id');
            })->count();
        $auditLog[] = ["results (Missing Student)", $resNoStudent];

        if ($shouldFix && $resNoStudent > 0) {
            DB::table('results')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'results.student_id');
                })->delete();
            $this->info("✔ Cleaned {$resNoStudent} orphaned exam results (No Student).");
        }

        // 11. Fee Vouchers with missing Student
        $fvNoStudent = DB::table('fee_vouchers as fv')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('students as s')->whereColumn('s.id', 'fv.student_id');
            })->count();
        $auditLog[] = ["fee_vouchers (Missing Student)", $fvNoStudent];

        if ($shouldFix && $fvNoStudent > 0) {
            DB::table('fee_vouchers')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('students')->whereColumn('students.id', 'fee_vouchers.student_id');
                })->delete();
            $this->info("✔ Cleaned {$fvNoStudent} orphaned fee vouchers (No Student).");
        }

        // 12. Fee Voucher Items with missing Voucher
        $fviNoVoucher = DB::table('fee_voucher_items as fvi')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('fee_vouchers as fv')->whereColumn('fv.id', 'fvi.fee_voucher_id');
            })->count();
        $auditLog[] = ["fee_voucher_items (Missing Voucher)", $fviNoVoucher];

        if ($shouldFix && $fviNoVoucher > 0) {
            DB::table('fee_voucher_items')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('fee_vouchers')->whereColumn('fee_vouchers.id', 'fee_voucher_items.fee_voucher_id');
                })->delete();
            $this->info("✔ Cleaned {$fviNoVoucher} orphaned fee voucher items (No Voucher).");
        }

        $this->table(['Check / Table Name', 'Orphan / Invalid Records Count'], $auditLog);

        if (!$shouldFix) {
            $this->comment("\nTip: Run 'php artisan db:audit-orphans --fix' to clean up all detected records.");
        }

        return Command::SUCCESS;
    }
}
