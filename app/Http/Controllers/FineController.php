<?php

namespace App\Http\Controllers;

use App\Models\FineRule;
use App\Models\StudentFine;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\Subject;
use App\Services\FineCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FineController extends Controller
{
    protected FineCalculationService $fineService;

    public function __construct(FineCalculationService $fineService)
    {
        $this->fineService = $fineService;
    }

    /**
     * Fine Ledger: list all generated student fines.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isTeacher = $user && $user->isTeacher();
        $canManage = $user && $user->isAdministrativeStaff();

        $query = StudentFine::with(['student', 'admission', 'fineRule', 'payerCollector', 'waiverAuthorizer'])
            ->orderByDesc('created_at');

        // Status Filter
        $statusFilter = $request->query('status', 'all');
        if ($statusFilter === 'unpaid') {
            $query->whereIn('status', ['unpaid', 'partially_paid']);
        } elseif (in_array($statusFilter, ['paid', 'waived'])) {
            $query->where('status', $statusFilter);
        }

        // Fine Type Filter
        if ($request->filled('fine_type') && $request->fine_type !== 'all') {
            $query->where('fine_type', $request->fine_type);
        }

        // Academic Year Filter
        if ($request->filled('year') && $request->year !== 'all') {
            $query->where('academic_year', $request->year);
        }

        // Class Level Filter
        if ($request->filled('class_level') && in_array($request->class_level, ['11', '12'])) {
            $query->whereHas('admission', function ($q) use ($request) {
                $q->where('current_class', $request->class_level);
            });
        }

        // Search Filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage < 1) $perPage = 25;

        $fines = $query->paginate($perPage)->withQueryString();

        $stats = $this->fineService->getStats($request->filled('year') && $request->year !== 'all' ? (int) $request->year : null);

        $availableYears = StudentFine::select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([(int) date('Y')]);
        }

        return view('fines.index', compact(
            'fines', 'stats', 'statusFilter', 'availableYears', 'isTeacher', 'canManage'
        ));
    }

    /**
     * Preview and Calculate fines panel.
     */
    public function generateForm(Request $request)
    {
        $user = Auth::user();
        $rules = FineRule::active()->orderBy('name')->get();
        $availableMonths = $this->fineService->getAvailableMonths();
        $availableExamNames = $this->fineService->getAvailableExamNames();
        $currentYear = (int) date('Y');

        $previewItems = collect();
        $hasPreview = false;

        if ($request->has('preview')) {
            $filters = [
                'fine_type'       => $request->input('fine_type', 'lecture_absent'),
                'fine_rule_id'    => $request->input('fine_rule_id'),
                'academic_year'   => (int) $request->input('academic_year', $currentYear),
                'class_level'     => $request->input('class_level'),
                'period'          => $request->input('period'),
                'exam_name'       => $request->input('exam_name'),
                'rate'            => $request->input('rate'),
                'pass_percentage' => $request->input('pass_percentage'),
            ];

            $previewItems = $this->fineService->preview($filters);
            $hasPreview = true;
        }

        return view('fines.generate', compact(
            'rules', 'availableMonths', 'availableExamNames', 'currentYear', 'previewItems', 'hasPreview'
        ));
    }

    /**
     * Commit and generate the previewed fines into database.
     */
    public function storeGenerated(Request $request)
    {
        $itemsJson = $request->input('selected_items');
        if (empty($itemsJson)) {
            return back()->with('error', 'No fine items selected for generation.');
        }

        $items = json_decode($itemsJson, true);
        if (!is_array($items) || empty($items)) {
            return back()->with('error', 'Invalid selection data.');
        }

        try {
            $result = $this->fineService->generate($items, Auth::id());
            $msg = "Fines generated successfully: {$result['created']} new fine record(s) created";
            if ($result['updated'] > 0) {
                $msg .= ", {$result['updated']} existing unpaid fine(s) updated";
            }
            $msg .= '.';

            return redirect()->route('fines.index')->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating fines: ' . $e->getMessage());
        }
    }

    /**
     * Fine Policy Rules Management.
     */
    public function rulesIndex()
    {
        $rules = FineRule::orderBy('fine_type')->orderBy('name')->get();
        $availableExamNames = $this->fineService->getAvailableExamNames();

        return view('fines.rules', compact('rules', 'availableExamNames'));
    }

    /**
     * Store new Fine Rule.
     */
    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:150',
            'fine_type'       => 'required|in:lecture_absent,test_absent,test_fail',
            'exam_name'       => 'nullable|string|max:150',
            'amount_per_unit' => 'required|numeric|min:0.01|max:100000',
            'pass_percentage' => 'nullable|numeric|min:1|max:100',
            'academic_year'   => 'nullable|integer|min:2000|max:2099',
            'class_level'     => 'nullable|in:11,12',
            'description'     => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['created_by'] = Auth::id();
        $validated['pass_percentage'] = $validated['pass_percentage'] ?? 33.00;

        FineRule::create($validated);

        return back()->with('success', "Fine policy rule '{$validated['name']}' created successfully.");
    }

    /**
     * Update existing Fine Rule.
     */
    public function updateRule(Request $request, FineRule $rule)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:150',
            'fine_type'       => 'required|in:lecture_absent,test_absent,test_fail',
            'exam_name'       => 'nullable|string|max:150',
            'amount_per_unit' => 'required|numeric|min:0.01|max:100000',
            'pass_percentage' => 'nullable|numeric|min:1|max:100',
            'academic_year'   => 'nullable|integer|min:2000|max:2099',
            'class_level'     => 'nullable|in:11,12',
            'description'     => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['updated_by'] = Auth::id();

        $rule->update($validated);

        return back()->with('success', "Fine policy rule '{$rule->name}' updated successfully.");
    }

    /**
     * Record payment against a student fine.
     */
    public function collectPayment(Request $request, StudentFine $fine)
    {
        $validated = $request->validate([
            'amount'            => 'required|numeric|min:0.01|max:' . $fine->balance_amount,
            'payment_reference' => 'nullable|string|max:100',
            'payment_note'      => 'nullable|string|max:500',
        ]);

        $fine->markPayment(
            (float) $validated['amount'],
            $validated['payment_reference'] ?? null,
            $validated['payment_note'] ?? null,
            Auth::id()
        );

        $studentName = $fine->student?->full_name ?? 'Student';
        return back()->with('success', "Payment of Rs. " . number_format($validated['amount'], 2) . " recorded for {$studentName}. Remaining balance: Rs. " . number_format($fine->fresh()->balance_amount, 2));
    }

    /**
     * Waive / Concession fine (Principal / Admin only).
     */
    public function waiveFine(Request $request, StudentFine $fine)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdministrativeStaff()) {
            return back()->with('error', 'Only Principal or Administrator can grant fine concessions/waivers.');
        }

        $validated = $request->validate([
            'amount'        => 'required|numeric|min:0.01|max:' . $fine->balance_amount,
            'waiver_reason' => 'required|string|min:3|max:500',
        ]);

        $fine->waiveFine((float) $validated['amount'], $validated['waiver_reason'], Auth::id());

        $studentName = $fine->student?->full_name ?? 'Student';
        return back()->with('success', "Fine waiver of Rs. " . number_format($validated['amount'], 2) . " authorized for {$studentName}.");
    }

    /**
     * Printable Fine Slip (3-Part Bank Challan).
     */
    public function printSlip(StudentFine $fine)
    {
        $fine->load(['student.father', 'admission', 'fineRule', 'payerCollector']);
        $settings = \App\Models\InstituteSetting::getSettings();

        return view('pdfs.fine-voucher', compact('fine', 'settings'));
    }

    /**
     * Printable Summary Report for Notice Board or Office Records.
     */
    public function printSummary(Request $request)
    {
        $query = StudentFine::with(['student', 'admission'])->orderBy('academic_year')->orderBy('fine_type');

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'unpaid') {
                $query->whereIn('status', ['unpaid', 'partially_paid']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('fine_type') && $request->fine_type !== 'all') {
            $query->where('fine_type', $request->fine_type);
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $query->where('academic_year', $request->year);
        }

        $fines = $query->get();
        $settings = \App\Models\InstituteSetting::getSettings();

        return view('fines.summary_print', compact('fines', 'settings'));
    }
}
