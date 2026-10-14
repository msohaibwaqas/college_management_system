<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedSections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:cleanup-orphaned-sections {--dry-run : Output records count without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned student subject section records and teacher assignments when subjects change';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in DRY-RUN mode (no database changes will be made)...');
        } else {
            $this->info('Starting cleanup of orphaned student subject sections...');
        }

        DB::beginTransaction();

        try {
            // 1. Find orphaned student_subject_sections records
            $orphanedSectionsQuery = DB::table('student_subject_sections as sss')
                ->whereNull('sss.deleted_at')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('student_elective_subjects as ses')
                      ->whereColumn('ses.student_id', 'sss.student_id')
                      ->whereColumn('ses.subject_id', 'sss.subject_id');
                })
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('student_compulsory_subjects as scs')
                      ->whereColumn('scs.student_id', 'sss.student_id')
                      ->whereColumn('scs.subject_id', 'sss.subject_id');
                });

            $orphanedSectionIds = $orphanedSectionsQuery->pluck('sss.id')->toArray();
            $orphanedSectionCount = count($orphanedSectionIds);

            if ($orphanedSectionCount > 0) {
                $this->info("Found {$orphanedSectionCount} orphaned record(s) in 'student_subject_sections'.");
                if (!$isDryRun) {
                    DB::table('student_subject_sections')
                        ->whereIn('id', $orphanedSectionIds)
                        ->delete();
                }
            } else {
                $this->info("No orphaned records found in 'student_subject_sections'.");
            }

            // 2. Find orphaned student_teacher_subjects records
            $orphanedTeacherQuery = DB::table('student_teacher_subjects as sts')
                ->whereNull('sts.deleted_at')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('student_elective_subjects as ses')
                      ->whereColumn('ses.student_id', 'sts.student_id')
                      ->whereColumn('ses.subject_id', 'sts.subject_id');
                })
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('student_compulsory_subjects as scs')
                      ->whereColumn('scs.student_id', 'sts.student_id')
                      ->whereColumn('scs.subject_id', 'sts.subject_id');
                });

            $orphanedTeacherIds = $orphanedTeacherQuery->pluck('sts.id')->toArray();
            $orphanedTeacherCount = count($orphanedTeacherIds);

            if ($orphanedTeacherCount > 0) {
                $this->info("Found {$orphanedTeacherCount} orphaned record(s) in 'student_teacher_subjects'.");
                if (!$isDryRun) {
                    DB::table('student_teacher_subjects')
                        ->whereIn('id', $orphanedTeacherIds)
                        ->delete();
                }
            } else {
                $this->info("No orphaned records found in 'student_teacher_subjects'.");
            }

            DB::commit();

            if ($isDryRun) {
                $this->info("\nDry run completed! No records were deleted.");
            } else {
                $this->info("\nCleanup completed successfully!");
                $this->info("Total student_subject_sections cleaned: {$orphanedSectionCount}");
                $this->info("Total student_teacher_subjects cleaned: {$orphanedTeacherCount}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during cleanup: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
