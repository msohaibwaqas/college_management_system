<?php

namespace App\Http\Controllers;

use App\Models\InvigilatorAssignment;
use App\Models\InvigilatorDutyRule;
use App\Models\ExamConductor;
use App\Models\HeSeatingPlan;
use App\Models\Staff;
use App\Models\Designation;
use App\Models\Room;
use App\Models\HeDatesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Services\InvigilationService;

class HeInvigilatorController extends Controller
{
    public function __construct(
        protected InvigilationService $invigilationService
    ) {}

    /**
     * Display list of HE invigilator assignments
     */
    public function index(Request $request)
    {
        $query = InvigilatorAssignment::with(['heSeatingPlan.datesheet.bsSubject', 'room', 'staff.designationModel'])
            ->whereNotNull('he_seating_plan_id');

        if ($request->filled('date')) {
            $query->where('exam_date', $request->date);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        $assignments = $query->orderBy('exam_date')->orderBy('room_id')->get();
        $rooms = Room::where('is_active', true)->forExams()->get();
        $staff = Staff::teaching()->active()->with('designationModel')->get();
        
        // Get exam dates from HE seating plans
        $examDates = HeSeatingPlan::whereHas('datesheet')->with('datesheet')->get()
            ->map(function($plan) {
                return (object)['date' => $plan->datesheet->exam_date];
            })->unique('date')->sortBy('date');
        
        // Calculate duty counts for each staff member in single batch query
        $dutyCounts = $this->invigilationService->getDutyCounts();
        
        // Get exemptions and duty rules
        $exemptions = ExamConductor::with('staff')->get();
        $dutyRules = InvigilatorDutyRule::with('designation')->get()->keyBy('designation_id');

        return view('he.invigilators.index', compact('assignments', 'rooms', 'staff', 'examDates', 'dutyCounts', 'exemptions', 'dutyRules'));
    }

    /**
     * Show dynamic assignment view for HE
     */
    public function assignView()
    {
        // Get exam dates from HE seating plans
        $examDates = HeSeatingPlan::whereHas('datesheet')->with('datesheet')->get()
            ->map(function($plan) {
                return (object)['date' => $plan->datesheet->exam_date];
            })->unique('date')->sortBy('date');

        $staffData = $this->invigilationService->getStaffRoster();

        return view('he.invigilators.assign', compact('staffData', 'examDates'));
    }

    /**
     * Get HE rooms and existing assignments for a specific date (AJAX)
     */
    public function getRoomsByDate(Request $request)
    {
        $date = $request->get('date');
        
        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        // Get HE seating plans for this date
        $hePlans = HeSeatingPlan::whereHas('datesheet', function($q) use ($date) {
            $q->where('exam_date', $date);
        })->with('datesheet.bsSubject', 'datesheet.program')->get();

        if ($hePlans->isEmpty()) {
            return response()->json(['error' => 'No HE seating plan found for this date'], 404);
        }

        $allRoomsData = [];
        $existingAssignmentsData = [];

        // Add HE rooms
        foreach ($hePlans as $plan) {
            $rooms = $plan->details->groupBy('room_id');
            $subjectName = $plan->datesheet->bsSubject->name ?? 'HE';
            
            foreach ($rooms as $roomId => $details) {
                $room = $details->first()->room;
                $allRoomsData[] = [
                    'id' => $roomId,
                    'name' => "{$room->name} ({$subjectName})",
                    'section' => 'HE',
                    'plan_id' => $plan->id,
                    'key' => "he_{$plan->id}_{$roomId}"
                ];
            }

            // Get existing HE assignments
            $assignments = InvigilatorAssignment::where('he_seating_plan_id', $plan->id)
                ->with('staff.designationModel')
                ->get()
                ->groupBy('room_id');

            foreach ($assignments as $roomId => $roomAssignments) {
                $existingAssignmentsData["he_{$plan->id}_{$roomId}"] = $roomAssignments->map(function($a) {
                    return [
                        'id' => $a->id,
                        'staff_id' => $a->staff_id,
                        'staff_name' => $a->staff->first_name . ' ' . $a->staff->last_name,
                        'designation' => $a->staff->designationModel->name ?? $a->staff->designation ?? 'N/A',
                    ];
                });
            }
        }

        return response()->json([
            'rooms' => $allRoomsData,
            'existing_assignments' => $existingAssignmentsData,
        ]);
    }

    /**
     * Store multiple HE invigilator assignments at once
     */
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

                $result = $this->invigilationService->syncRoomAssignments('he_seating_plan_id', $planId, $roomId, $examDate, $newStaffIds);
                $successCount += $result['added'];
                $deletedCount += $result['removed'];
                $errors = array_merge($errors, $result['errors']);
            }
        });

        $message = "HE Assignments updated: {$successCount} added, {$deletedCount} removed.";
        
        if (!empty($errors)) {
            return back()->withErrors($errors)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Show report for HE duty roster
     */
    public function dutyRoster(Request $request)
    {
        $date = $request->get('date');
        $examDates = $this->invigilationService->getRosterDates('HE');
        $assignments = $date ? $this->invigilationService->getDutyRoster($date, 'HE') : collect();
        $institute = \App\Models\InstituteSetting::first();

        return view('he.invigilators.duty_roster', compact('assignments', 'examDates', 'date', 'institute'));
    }

    public function create()
    {
        $designations = Designation::all();
        $dutyRules = InvigilatorDutyRule::with('designation')->get()->keyBy('designation_id');
        $staff = Staff::teaching()->active()->with('designationModel')->get();
        $examConductors = ExamConductor::with('staff.designationModel')->get();
        
        $examDates = HeDatesheet::select('exam_date')
            ->distinct()->orderBy('exam_date')->pluck('exam_date')
            ->map(fn($d) => Carbon::parse($d));

        return view('he.invigilators.create', compact('designations', 'dutyRules', 'staff', 'examConductors', 'examDates'));
    }

    /**
     * Execute HE auto-assignment
     */
    public function autoAssign(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'invigilators_per_room' => 'required|integer|min:1|max:4',
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $perRoom = (int) $request->invigilators_per_room;

            $this->invigilationService->autoAssignHe($startDate, $endDate, $perRoom);

            return redirect()->route('he.invigilators.index')->with('success', 'HE Invigilators auto-assigned.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
