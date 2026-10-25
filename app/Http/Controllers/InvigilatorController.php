<?php

namespace App\Http\Controllers;

use App\Models\InvigilatorAssignment;
use App\Models\InvigilatorDutyRule;
use App\Models\ExamConductor;
use App\Models\Designation;
use App\Models\Staff;
use App\Models\InstituteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\InvigilationService;

class InvigilatorController extends Controller
{
    public function __construct(
        protected InvigilationService $invigilationService
    ) {}

    /**
     * Display list of invigilator assignments
     */
    public function index(Request $request)
    {
        $data = $this->invigilationService->getHsscIndexData($request);
        return view('invigilators.index', $data);
    }

    /**
     * Show dynamic assignment view
     */
    public function assignView()
    {
        $data = $this->invigilationService->getHsscAssignViewData();
        return view('invigilators.assign', $data);
    }

    /**
     * Store manually assigned invigilator
     */
    public function store(Request $request)
    {
        $request->validate([
            'exam_date' => 'required|date',
            'room_id' => 'required|exists:rooms,id',
            'staff_id' => 'required|exists:staff,id',
        ]);

        $result = $this->invigilationService->assignManualInvigilator(
            $request->exam_date,
            (int) $request->room_id,
            (int) $request->staff_id
        );

        if (!$result['success']) {
            return back()->withErrors($result['errors']);
        }

        return back()->with('success', 'Invigilator assigned successfully.');
    }

    /**
     * Get rooms and existing assignments for a specific date (AJAX)
     */
    public function getRoomsByDate(Request $request)
    {
        $date = $request->get('date');
        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        $data = $this->invigilationService->getHsscRoomsByDate($date);
        if (isset($data['error'])) {
            return response()->json(['error' => $data['error']], 404);
        }

        return response()->json($data);
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'exam_date' => 'required|date',
            'assignments' => 'required|array',
            'assignments.*.room_id' => 'required|exists:rooms,id',
            'assignments.*.staff_id' => 'required|exists:staff,id',
            'assignments.*.plan_id' => 'required|integer',
        ]);

        $examDate = $request->exam_date;
        $assignments = $request->assignments;

        $errors = [];
        $successCount = 0;
        $deletedCount = 0;

        DB::transaction(function () use ($assignments, $examDate, &$errors, &$successCount, &$deletedCount) {
            $grouped = collect($assignments)->groupBy(fn($a) => "{$a['plan_id']}_{$a['room_id']}");

            foreach ($grouped as $roomAssignments) {
                $planId = $roomAssignments->first()['plan_id'];
                $roomId = $roomAssignments->first()['room_id'];
                $newStaffIds = $roomAssignments->pluck('staff_id')->unique()->toArray();

                $result = $this->invigilationService->syncRoomAssignments('seating_plan_id', $planId, $roomId, $examDate, $newStaffIds);
                $successCount += $result['added'];
                $deletedCount += $result['removed'];
                $errors = array_merge($errors, $result['errors']);
            }
        });

        $message = "Assignments updated: {$successCount} added, {$deletedCount} removed.";
        
        if (!empty($errors)) {
            return back()->withErrors($errors)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Delete all assignments for a specific date
     */
    public function deleteByDate(Request $request)
    {
        $request->validate([
            'exam_date' => 'required|date',
        ]);

        $deletedCount = InvigilatorAssignment::where('exam_date', $request->exam_date)->delete();

        return back()->with('success', "{$deletedCount} assignment(s) deleted successfully.");
    }

    /**
     * Show duty roster report
     */
    public function dutyRoster(Request $request)
    {
        $date = $request->get('date');
        $examDates = $this->invigilationService->getRosterDates('HSSC');
        $assignments = $date ? $this->invigilationService->getDutyRoster($date, 'HSSC') : collect();
        $institute = InstituteSetting::first();

        return view('invigilators.duty_roster', compact('assignments', 'examDates', 'date', 'institute'));
    }

    public function create()
    {
        $data = $this->invigilationService->getHsscCreateFormData();
        return view('invigilators.create', $data);
    }

    /**
     * Execute auto-assignment algorithm
     */
    public function autoAssign(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'exempted_staff' => 'nullable|array',
            'exempted_staff.*' => 'exists:staff,id',
            'invigilators_per_room' => 'required|integer|min:1|max:4',
        ]);

        try {
            $report = $this->invigilationService->autoAssignHssc(
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date),
                (int) $request->invigilators_per_room,
                $request->exempted_staff ?? []
            );

            return redirect()->route('invigilators.index')
                ->with('success', 'Invigilators assigned successfully.')
                ->with('report', $report);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, InvigilatorAssignment $assignment)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
        ]);

        $assignment->update([
            'staff_id' => $request->staff_id,
            'is_auto_assigned' => false,
        ]);

        return back()->with('success', 'Assignment updated successfully.');
    }

    /**
     * Remove an assignment
     */
    public function destroy(InvigilatorAssignment $assignment)
    {
        $assignment->delete();
        return back()->with('success', 'Assignment removed successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invigilator_assignments,id',
        ]);

        InvigilatorAssignment::whereIn('id', $request->ids)->delete();

        return back()->with('success', 'Selected assignments deleted successfully.');
    }

    /**
     * Manage duty rules
     */
    public function dutyRules()
    {
        $teachingDesignations = [
            'Professor',
            'Associate Professor',
            'Assistant Professor',
            'Lecturer',
            'College Teaching Intern',
            'CTI'
        ];
        
        $designations = Designation::whereIn('name', $teachingDesignations)->get();
        $dutyRules = InvigilatorDutyRule::with('designation')->get();

        return view('invigilators.duty_rules', compact('designations', 'dutyRules'));
    }

    /**
     * Store or update duty rule
     */
    public function storeDutyRule(Request $request)
    {
        $request->validate([
            'designation_id' => 'required|exists:designations,id',
            'max_duty_days' => 'required|integer|min:1',
        ]);

        InvigilatorDutyRule::updateOrCreate(
            ['designation_id' => $request->designation_id],
            ['max_duty_days' => $request->max_duty_days]
        );

        return back()->with('success', 'Duty rule saved successfully.');
    }

    /**
     * Manage exemptions
     */
    public function exemptions()
    {
        $staff = Staff::teaching()->active()->with('designationModel')->get();
        $exemptions = ExamConductor::with('staff.designationModel')->get();

        return view('invigilators.exemptions', compact('staff', 'exemptions'));
    }

    /**
     * Store exemption
     */
    public function storeExemption(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'exam_start_date' => 'required|date',
            'exam_end_date' => 'required|date|after_or_equal:exam_start_date',
            'reason' => 'nullable|string',
        ]);

        ExamConductor::create($request->all());

        return back()->with('success', 'Exemption added successfully.');
    }

    /**
     * Delete exemption
     */
    public function destroyExemption(ExamConductor $exemption)
    {
        $exemption->delete();
        return back()->with('success', 'Exemption removed successfully.');
    }
}
