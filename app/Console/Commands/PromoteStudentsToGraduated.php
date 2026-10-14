<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admissions;
use Illuminate\Support\Facades\DB;

class PromoteStudentsToGraduated extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:promote-graduated {year=2023}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote students from a specific admission year to Graduated status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->argument('year');

        $this->info("Promoting students from admission year $year to 'Graduated'...");

        $count = Admissions::where('admission_year', $year)
            ->where('status', '!=', 'Graduated') // Only update those not already graduated
            ->count();

        if ($count === 0) {
            $this->info("No students found for admission year $year that need promotion.");
            return;
        }

        if ($this->confirm("Found $count students. Do you want to proceed?")) {
            DB::transaction(function () use ($year) {
                Admissions::where('admission_year', $year)
                    ->where('status', '!=', 'Graduated')
                    ->update(['status' => 'Graduated']);
            });

            $this->info("Successfully promoted $count students to 'Graduated'.");

            // Automatically archive their attendance records
            $this->info("Archiving attendance records for admission year $year...");
            $this->call('attendance:archive-graduated', ['--year' => $year]);
        } else {
            $this->info("Operation cancelled.");
        }
    }
}
