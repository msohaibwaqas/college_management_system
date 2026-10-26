<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Services\Report\FinalResultReportService;

class FinalResultReportController extends Controller
{
    public function __construct(
        protected FinalResultReportService $reportService
    ) {}

    /**
     * Show the final result report form
     */
    public function index(Request $request)
    {
        // Get distinct admission years
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $reportData = [];
        $selectedYear = $request->admission_year;
        $customPassPercentage = $request->pass_percentage;
        $selectedPart = $request->input('result_part', 'combine');
        
        // Option D: Toggle Board Grace Marks
        // If include_grace is present in request, evaluate boolean; otherwise default to true
        $includeGrace = $request->has('include_grace') 
            ? $request->boolean('include_grace') 
            : true;

        if ($selectedYear) {
            $reportData = $this->reportService->generateReportData(
                $selectedYear, 
                $customPassPercentage, 
                $selectedPart, 
                $includeGrace
            );
        }

        return view('reports.final-result-report.index', compact(
            'admissionYears', 
            'reportData', 
            'selectedYear', 
            'customPassPercentage', 
            'selectedPart',
            'includeGrace'
        ));
    }
}
