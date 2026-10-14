<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admissions;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateStudentStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'students:update-status {--force : Force update regardless of date}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically update student class and status based on admission year and current date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting student status update...');
        
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;
        
        $force = $this->option('force');
        
        $promotedCount = 0;
        $graduatedCount = 0;
        
        DB::beginTransaction();
        
        try {
            // Get all admitted students
            $admissions = Admissions::whereIn('status', ['Admitted', 'Active'])->get();
            
            foreach ($admissions as $admission) {
                $admissionYear = $admission->admission_year;
                $currentClass = $admission->current_class;
                
                // Calculate expected promotion and graduation dates
                // Admission: July of admission year
                // Class 11 -> Class 12 promotion: June of (admission_year + 1)
                // Graduation: April of (admission_year + 2)
                
                $promotionDate = Carbon::create($admissionYear + 1, 6, 1); // June of next year
                $graduationDate = Carbon::create($admissionYear + 2, 4, 30); // End of April, 2 years later
                
                // Check if student should be promoted from Class 11 to Class 12
                if ($currentClass == '11' && ($currentDate->gte($promotionDate) || $force)) {
                    $admission->update([
                        'current_class' => '12',
                        'academic_year' => $admissionYear + 1,
                        'status' => 'Admitted'
                    ]);
                    
                    // Update compulsory subjects class_level from 11 to 12
                    DB::table('student_compulsory_subjects')
                        ->where('student_id', $admission->student_id)
                        ->where('class_level', '11')
                        ->update(['class_level' => '12']);
                    
                    $promotedCount++;
                    $this->info("Promoted student ID {$admission->student_id} from Class 11 to Class 12");
                }
                
                // Check if student should be graduated
                if ($currentClass == '12' && ($currentDate->gte($graduationDate) || $force)) {
                    $admission->update([
                        'status' => 'Graduated'
                    ]);
                    
                    $graduatedCount++;
                    $this->info("Graduated student ID {$admission->student_id}");
                }
            }
            
            DB::commit();
            
            $this->info("\n=== Update Summary ===");
            $this->info("Students promoted to Class 12: {$promotedCount}");
            $this->info("Students graduated: {$graduatedCount}");
            $this->info("Total students updated: " . ($promotedCount + $graduatedCount));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error updating student status: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
