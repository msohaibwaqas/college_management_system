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
        $examDates = InvigilatorAssignment::whereNotNull('he_seating_plan_id')
            ->select('exam_date')->distinct()->orderBy('exam_date')->get()
            ->map(function($a) {
                return (object)['exam_date' => \Carbon\Carbon::parse($a->exam_date)];
            });
            
        $assignments = collect();
        $institute = \App\Models\InstituteSetting::first();
        
        if ($date) {
            $assignments = InvigilatorAssignment::where('exam_date', $date)
                ->whereNotNull('he_seating_plan_id')
                ->with(['room', 'staff.designationModel', 'heSeatingPlan.datesheet.bsSubject'])
                ->get()
                ->groupBy(function($a) {
                    return "he_{$a->he_seating_plan_id}_{$a->room_id}";
                })
                ->map(function($group) {
                    $first = $group->first();
                    $subjectName = $first->heSeatingPlan->datesheet->bsSubject->name ?? 'HE';
                    return [
                        'room_name' => "{$first->room->name} ({$subjectName})",
                        'staff' => $group->pluck('staff')->sortBy(function($staff) {
                            $order = ['professor' => 1, 'associate professor' => 2, 'assistant professor' => 3, 'lecturer' => 4];
                            $desig = strtolower(trim($staff->designationModel->name ?? $staff->designation ?? ''));
                            return $order[$desig] ?? 999;
                        })
                    ];
                })->sortBy('room_name');
        }
        
        return view('he.invigilators.duty_roster', compact('assignments', 'examDates', 'date', 'institute'));
    }

    /**
     * HE Configuration for auto-assignment
     */
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

        DB::beginTransaction();
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $perRoom = $request->invigilators_per_room;

            $hePlans = HeSeatingPlan::whereHas('datesheet', fn($q) => $q->whereBetween('exam_date', [$startDate, $endDate]))
                ->with('datesheet')->get();

            if ($hePlans->isEmpty()) {
                DB::rollBack();
                return back()->withErrors(['error' => 'No HE seating plans found.']);
            }

            $eligibleTeachers = Staff::teaching()->active()->whereNotNull('designation_id')->with('designationModel')->get();
            $dutyCount = [];
            foreach ($eligibleTeachers as $teacher) {
                $dutyCount[$teacher->id] = [
                    'count' => InvigilatorAssignment::where('staff_id', $teacher->id)->count(),
                    'max' => InvigilatorDutyRule::getMaxDutyDays($teacher->designation_id) ?? 15,
                    'teacher' => $teacher
                ];
            }

            $roomSlots = [];
            foreach ($hePlans as $plan) {
                $rooms = $plan->details->groupBy('room_id');
                $dateKey = $plan->datesheet->exam_date->format('Y-m-d');
                foreach ($rooms as $roomId => $details) {
                    for ($i = 0; $i < $perRoom; $i++) {
                        $roomSlots[] = [
                            'plan_id' => $plan->id,
                            'room_id' => $roomId,
                            'date_key' => $dateKey,
                            'exam_date' => $plan->datesheet->exam_date,
                        ];
                    }
                }
            }

            $allAssignments = [];
            $dailyAssignments = [];

            foreach ($roomSlots as $slot) {
                $dateKey = $slot['date_key'];
                $bestTeacher = null;
                $lowestCount = PHP_INT_MAX;

                foreach ($dutyCount as $id => $data) {
                    if (!isset($dailyAssignments[$dateKey][$id]) && $data['count'] < $data['max']) {
                        if ($data['count'] < $lowestCount) {
                            $bestTeacher = $id;
                            $lowestCount = $data['count'];
                        }
                    }
                }

                if ($bestTeacher) {
                    $allAssignments[] = [
                        'room_id' => $slot['room_id'],
                        'staff_id' => $bestTeacher,
                        'exam_date' => $slot['exam_date'],
                        'is_auto_assigned' => true,
                        'he_seating_plan_id' => $slot['plan_id'],
                    ];
                    $dutyCount[$bestTeacher]['count']++;
                    $dailyAssignments[$dateKey][$bestTeacher] = true;
                }
            }

            InvigilatorAssignment::whereBetween('exam_date', [$startDate, $endDate])
                ->where('is_auto_assigned', true)->whereNotNull('he_seating_plan_id')->delete();

            if (!empty($allAssignments)) {
                InvigilatorAssignment::insert($allAssignments);
            }

            DB::commit();
            return redirect()->route('he.invigilators.index')->with('success', 'HE Invigilators auto-assigned.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
