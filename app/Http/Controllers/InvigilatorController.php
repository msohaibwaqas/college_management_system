<?php

namespace App\Http\Controllers;

use App\Models\InvigilatorAssignment;
use App\Models\InvigilatorDutyRule;
use App\Models\ExamConductor;
use App\Models\SeatingPlan;
use App\Models\Staff;
use App\Models\Designation;
use App\Models\Room;
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
        $query = InvigilatorAssignment::with(['seatingPlan', 'room', 'staff.designationModel']);

        if ($request->filled('date')) {
            $query->forDate($request->date);
        }

        if ($request->filled('room_id')) {
            $query->forRoom($request->room_id);
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        $assignments = $query->orderBy('exam_date')->orderBy('room_id')->get();
        $rooms = Room::all();
        $staff = Staff::teaching()->active()->with('designationModel')->get();
        
        // Get exam dates from HSSC seating plans
        $examDates = SeatingPlan::select('test_date as date')
            ->distinct()
            ->orderBy('date')
            ->get()
            ->map(function($plan) {
                return (object)['date' => \Carbon\Carbon::parse($plan->date)];
            });
        
        // Calculate duty counts for each staff member in single batch query
        $dutyCounts = $this->invigilationService->getDutyCounts();
        
        // Get exemptions and duty rules
        $exemptions = ExamConductor::with('staff')->get();
        $dutyRules = InvigilatorDutyRule::with('designation')->get()->keyBy('designation_id');

        return view('invigilators.index', compact('assignments', 'rooms', 'staff', 'examDates', 'dutyCounts', 'exemptions', 'dutyRules'));
    }

    /**
     * Show dynamic assignment view
     */
    public function assignView()
    {
        $staff = Staff::teaching()->active()->with('designationModel')->get();
        
        // Get exam dates from HSSC seating plans
        $examDates = SeatingPlan::select('test_date as date')
            ->distinct()
            ->orderBy('date')
            ->get()
            ->map(function($plan) {
                return (object)['date' => \Carbon\Carbon::parse($plan->date)];
            });
        
        $staffData = $this->invigilationService->getStaffRoster();

        return view('invigilators.assign', compact('staffData', 'examDates'));
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

        $examDate = $request->exam_date;
        $roomId = $request->room_id;
        $staffId = $request->staff_id;

        // Find the seating plan for this date
        $seatingPlan = SeatingPlan::where('test_date', $examDate)->first();
        
        if (!$seatingPlan) {
            return back()->withErrors(['exam_date' => 'No seating plan found for this date.']);
        }

        // Check if staff is exempted for this date
        $isExempted = ExamConductor::where('staff_id', $staffId)
            ->where(function($query) use ($examDate) {
                $query->whereNull('exam_start_date')
                      ->orWhere(function($q) use ($examDate) {
                          $q->where('exam_start_date', '<=', $examDate)
                            ->where('exam_end_date', '>=', $examDate);
                      });
            })
            ->exists();

        if ($isExempted) {
            return back()->withErrors(['staff_id' => 'This staff member is exempted from duty on this date.']);
        }

        // Check if staff already assigned to a room on this date (one room per day rule)
        $alreadyAssignedToday = InvigilatorAssignment::where('staff_id', $staffId)
            ->where('exam_date', $examDate)
            ->exists();

        if ($alreadyAssignedToday) {
            return back()->withErrors(['staff_id' => 'This staff member is already assigned to another room on this date.']);
        }

        // Check if staff already assigned to this specific room
        $alreadyAssignedToRoom = InvigilatorAssignment::where('seating_plan_id', $seatingPlan->id)
            ->where('room_id', $roomId)
            ->where('staff_id', $staffId)
            ->exists();

        if ($alreadyAssignedToRoom) {
            return back()->withErrors(['staff_id' => 'This staff member is already assigned to this room.']);
        }

        // Check duty limit
        $staff = Staff::find($staffId);
        $currentDutyCount = InvigilatorAssignment::where('staff_id', $staffId)->count();
        $maxDutyDays = InvigilatorDutyRule::getMaxDutyDays($staff->designation_id) ?? 999;

        if ($currentDutyCount >= $maxDutyDays) {
            return back()->withErrors(['staff_id' => "This staff member has reached their maximum duty limit ({$maxDutyDays} days)."]);
        }

        // Create the assignment
        InvigilatorAssignment::create([
            'seating_plan_id' => $seatingPlan->id,
            'room_id' => $roomId,
            'staff_id' => $staffId,
            'exam_date' => $examDate,
            'is_auto_assigned' => false,
        ]);

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

        // Get HSSC seating plan for this date
        $hsscPlan = SeatingPlan::where('test_date', $date)->first();

        if (!$hsscPlan) {
            return response()->json(['error' => 'No HSSC seating plan found for this date'], 404);
        }

        $allRoomsData = [];
        $existingAssignmentsData = [];

        // Add HSSC rooms
        if ($hsscPlan) {
            $rooms = $hsscPlan->details->groupBy('room_id');
            foreach ($rooms as $roomId => $details) {
                $room = $details->first()->room;
                $allRoomsData[] = [
                    'id' => $roomId,
                    'name' => $room->name,
                    'section' => 'HSSC',
                    'plan_id' => $hsscPlan->id,
                    'key' => "hssc_{$hsscPlan->id}_{$roomId}"
                ];
            }

            // Get existing HSSC assignments
            $assignments = InvigilatorAssignment::where('seating_plan_id', $hsscPlan->id)
                ->with('staff.designationModel')
                ->get()
                ->groupBy('room_id');

            foreach ($assignments as $roomId => $roomAssignments) {
                $existingAssignmentsData["hssc_{$hsscPlan->id}_{$roomId}"] = $roomAssignments->map(function($a) {
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

        $examDate = $request->exam_date;

        $deletedCount = InvigilatorAssignment::where('exam_date', $examDate)->delete();

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
        $institute = \App\Models\InstituteSetting::first();

        return view('invigilators.duty_roster', compact('assignments', 'examDates', 'date', 'institute'));
    }

    public function create()
    {
        $designations = Designation::all();
        $dutyRules = InvigilatorDutyRule::with('designation')->get()->keyBy('designation_id');
        $staff = Staff::teaching()->active()->with('designationModel')->get();
        $examConductors = ExamConductor::with('staff.designationModel')->get();
        
        // Get unique exam dates from HSSC datesheets
        $hsscDates = \App\Models\Datesheet::select('test_date')
            ->distinct()
            ->orderBy('test_date')
            ->pluck('test_date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date);
            });

        // Merge and sort all dates
        $examDates = $hsscDates->unique()->sort()->values();

        return view('invigilators.create', compact('designations', 'dutyRules', 'staff', 'examConductors', 'examDates'));
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
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $exemptedStaffIds = $request->exempted_staff ?? [];
            $invigilatorsPerRoom = (int) $request->invigilators_per_room;

            $report = $this->invigilationService->autoAssignHssc(
                $startDate,
                $endDate,
                $invigilatorsPerRoom,
                $exemptedStaffIds
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
        // Only show teaching staff designations
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
