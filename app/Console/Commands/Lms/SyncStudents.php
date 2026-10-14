<?php

namespace App\Console\Commands\Lms;

use Illuminate\Console\Command;

class SyncStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:sync-students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize student records with user accounts for LMS access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting student-user synchronization...');

        // Handle HSSC Admissions
        $activeAdmissions = \App\Models\Admissions::whereIn('status', ['Active', 'Admitted'])->with('student.contact')->get();
        foreach ($activeAdmissions as $admission) {
            $this->syncStudentUser($admission->student, 'std_' . strtolower(str_replace([' ', '/', '-'], '_', $admission->college_roll_no)), $count);
        }

        // Handle Higher Education (BS/ADP) Admissions
        $activeHeAdmissions = \App\Models\HeAdmission::whereIn('status', ['Active', 'Admitted'])->with('student.contact')->get();
        foreach ($activeHeAdmissions as $heAdmission) {
            $this->syncStudentUser($heAdmission->student, 'std_' . strtolower(str_replace([' ', '/', '-'], '_', $heAdmission->roll_no)), $count);
        }

        $this->info("Synchronization complete. Total accounts newly created or linked: {$count}");
    }

    private function syncStudentUser($student, $username, &$count)
    {
        if (!$student) return;

        // Check if user already exists for this student by ID
        $user = \App\Models\User::where('student_id', $student->id)->first();

        if (!$user) {
            // Check if a user with this username already exists (but without student_id)
            $user = \App\Models\User::where('username', $username)->first();
            
            if ($user) {
                // Update existing user to link student_id
                $user->update(['student_id' => $student->id]);
            } else {
                // Create new user
                $email = $student->contact->email ?? ($username . '@scms.local');
                try {
                    $user = \App\Models\User::create([
                        'username' => $username,
                        'email' => $email,
                        'password' => 'password123',
                        'status' => 'active',
                        'student_id' => $student->id,
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to create account for {$student->full_name}: " . $e->getMessage());
                    return;
                }
            }
        }

        // Ensure student role is assigned
        $role = \App\Models\Roles::where('name', 'student')->first();
        if ($role && !$user->hasRole('student')) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
