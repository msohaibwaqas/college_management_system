<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DatesheetDownload;

class DatesheetDownloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DatesheetDownload::create([
            'title' => 'Final Exam Datesheet 2025',
            'description' => 'Datesheet for Class 11 and 12 Final Exams.',
            'file_path' => 'datesheets/sample_datesheet.pdf',
            'published_date' => now(),
        ]);

        DatesheetDownload::create([
            'title' => 'Mid-Term Schedule Class 11',
            'description' => 'Schedule for upcoming mid-term exams for first year students.',
            'file_path' => 'datesheets/sample_datesheet.pdf',
            'published_date' => now()->subDays(10),
        ]);
    }
}
