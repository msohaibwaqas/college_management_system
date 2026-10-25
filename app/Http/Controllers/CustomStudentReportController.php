<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Report\StudentReportQueryService;
use App\Exports\CustomReportExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller managing custom student reports and spreadsheet exports.
 * Delegates all domain query-building and aggregation logic to StudentReportQueryService.
 */
class CustomStudentReportController extends Controller
{
    public function __construct(
        protected StudentReportQueryService $reportService
    ) {}

    /**
     * Display the dynamic custom report & query builder.
     */
    public function index(Request $request)
    {
        $context = $this->reportService->buildReportContext($request);
        return view('reports.custom-student-report.index', $context);
    }

    /**
     * Export the generated report to Excel (.xlsx).
     */
    public function export(Request $request)
    {
        $context = $this->reportService->buildReportContext($request);
        $exportData = $this->reportService->buildExportData($context);

        return Excel::download(
            new CustomReportExport(
                $exportData['rows'],
                $exportData['headings'],
                $exportData['title']
            ),
            $exportData['fileName']
        );
    }
}
