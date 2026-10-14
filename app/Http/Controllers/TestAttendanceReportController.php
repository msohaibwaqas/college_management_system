<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Models\Result;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class TestAttendanceReportController extends Controller
{
    /**
     * Show the test attendance report
     */
    public function index(Request $request)
    {
        // Get distinct admission years for the filter dropdown
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $selectedYear = $request->admission_year;
        $reportData = [];

        if ($selectedYear) {
            // Fetch aggregated attendance data for the selected admission year
            // We group by subject, exam type, exam name, and the date (using DATE(created_at))
            // Get results grouped by subject, exam type, and exam name
            $reportData = Result::where('results.academic_year', $selectedYear)
                ->with('subject')
                ->select(
                    'results.subject_id',
                    'results.exam_type',
                    'results.exam_name',
                    DB::raw('DATE(results.created_at) as test_date'),
                    DB::raw('COUNT(*) as total_students'),
                    DB::raw('SUM(CASE WHEN results.is_absent = 1 THEN 1 ELSE 0 END) as absent_count'),
                    DB::raw('SUM(CASE WHEN results.is_absent = 0 THEN 1 ELSE 0 END) as present_count')
                )
                ->groupBy('results.subject_id', 'results.exam_type', 'results.exam_name', DB::raw('DATE(results.created_at)'))
                ->orderBy('test_date', 'desc')
                ->orderBy('results.exam_type')
                ->get();

            // Map month abbreviations to month numbers for datesheet lookup
            $monthMap = [
                'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
                'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
                'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
            ];

            // Load all datesheets for this year for lookup
            $datesheets = \App\Models\Datesheet::where('admission_year', $selectedYear)->get();

            // Match each result row to the correct datesheet entry by subject + month from exam_type
            foreach ($reportData as $row) {
                $examTypeLower = strtolower($row->exam_type);
                $matchedMonth = null;
                foreach ($monthMap as $abbr => $num) {
                    if (str_starts_with($examTypeLower, $abbr)) {
                        $matchedMonth = $num;
                        break;
                    }
                }

                if ($matchedMonth) {
                    $match = $datesheets->first(function ($ds) use ($row, $matchedMonth) {
                        return $ds->subject_id == $row->subject_id
                            && $ds->test_date->month == $matchedMonth;
                    });
                    if ($match) {
                        $row->test_date = $match->test_date->format('Y-m-d');
                    }
                }
            }
        }

        return view('reports.test-attendance-report.index', compact('admissionYears', 'reportData', 'selectedYear'));
    }
}
