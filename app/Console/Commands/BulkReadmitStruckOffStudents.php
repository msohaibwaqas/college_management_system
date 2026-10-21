<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StruckOffService;
use App\Models\User;

class BulkReadmitStruckOffStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'struckoff:bulk-readmit {--user-id=1 : Admin User ID performing the readmission} {--note= : Readmission note}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk readmit all eligible struck-off students as per college policy';

    /**
     * Execute the console command.
     */
    public function handle(StruckOffService $struckOffService): int
    {
        $userId = (int) $this->option('user-id');
        $note = $this->option('note') ?? 'Bulk readmitted as per policy reset';

        $this->info("Starting bulk readmission for struck-off students...");

        $count = $struckOffService->bulkReadmit($userId, $note);

        $this->info("Successfully readmitted {$count} students!");
        $this->info("Previous absences prior to readmission have been protected.");

        return Command::SUCCESS;
    }
}
