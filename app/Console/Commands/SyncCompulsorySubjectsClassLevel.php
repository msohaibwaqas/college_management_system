<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCompulsorySubjectsClassLevel extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'students:sync-compulsory-class-levels';

    /**
     * The console command description.
     */
    protected $description = 'Sync class_level in student_compulsory_subjects with current_class from admissions table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting sync of compulsory subjects class levels...');
        
        DB::beginTransaction();
        
        try {
            // Update compulsory subjects for Class 12 students
            $class12Updated = DB::table('student_compulsory_subjects')
                ->join('admissions', 'student_compulsory_subjects.student_id', '=', 'admissions.student_id')
                ->where('admissions.current_class', '12')
                ->whereIn('admissions.status', ['Active', 'Admitted'])
                ->where('student_compulsory_subjects.class_level', '11')
                ->update(['student_compulsory_subjects.class_level' => '12']);
            
            $this->info("Updated {$class12Updated} compulsory subject records to Class 12");
            
            // Verify the counts
            $class11Count = DB::table('student_compulsory_subjects')
                ->where('class_level', '11')
                ->count();
            
            $class12Count = DB::table('student_compulsory_subjects')
                ->where('class_level', '12')
                ->count();
            
            $this->info("\nCurrent counts:");
            $this->info("Class 11 compulsory subjects: {$class11Count}");
            $this->info("Class 12 compulsory subjects: {$class12Count}");
            
            DB::commit();
            
            $this->info("\nSync completed successfully!");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error syncing class levels: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
