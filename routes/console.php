<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule student status updates
// This will run daily at 1:00 AM to check and update student promotions and graduations
Schedule::command('students:update-status')->dailyAt('01:00');
