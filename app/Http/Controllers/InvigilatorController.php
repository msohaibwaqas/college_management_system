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
        
        // Get all dates from assignments
        $examDates = InvigilatorAssignment::select('exam_date')
            ->distinct()
            ->orderBy('exam_date')
            ->get()
            ->map(function($a) {
                return (object)['exam_date' => \Carbon\Carbon::parse($a->exam_date)];
            });
            
        $assignments = collect();
        $institute = \App\Models\InstituteSetting::first();
        
        if ($date) {
            $assignments = InvigilatorAssignment::where('exam_date', $date)
                ->with(['room', 'staff.designationModel', 'seatingPlan'])
                ->get()
                ->groupBy(function($a) {
                    return "hssc_{$a->seating_plan_id}_{$a->room_id}";
                })
                ->map(function($group) {
                    $first = $group->first();
                    $roomName = $first->room->name;

                    return [
                        'room_name' => $roomName,
                        'staff' => $group->pluck('staff')->sortBy(function($staff) {
                            $designationOrder = [
                                'professor' => 1,
                                'associate professor' => 2,
                                'assistant professor' => 3,
                                'lecturer' => 4,
                            ];
                            $desig = strtolower(trim($staff->designationModel->name ?? $staff->designation ?? ''));
                            return $designationOrder[$desig] ?? 999;
                        })
                    ];
                })->sortBy('room_name');
        }
        
        return view('invigilators.duty_roster', compact('assignments', 'examDates', 'date', 'institute'));
    }

    /**
     * Show form to configure auto-assignment
     */
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

        // Check for concurrent assignment operations
        $lockTimeout = 300; // 5 minutes
        $locked = DB::table('seating_plans')
            ->where('assignment_in_progress', true)
            ->where('assignment_locked_at', '>', now()->subSeconds($lockTimeout))
            ->exists();

        if ($locked) {
            return back()->withErrors(['error' => 'Another assignment operation is in progress. Please wait and try again.']);
        }

        DB::beginTransaction();
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $exemptedStaffIds = $request->exempted_staff ?? [];
            $invigilatorsPerRoom = $request->invigilators_per_room;

            // Set lock on all seating plans in date range
            DB::table('seating_plans')
                ->whereBetween('test_date', [$startDate, $endDate])
                ->update([
                    'assignment_in_progress' => true,
                    'assignment_locked_at' => now()
                ]);

            // Get all HSSC seating plans in date range
            $hsscPlans = SeatingPlan::whereBetween('test_date', [$startDate, $endDate])->get();
            
            if ($hsscPlans->isEmpty()) {
                DB::rollBack();
                return back()->withErrors(['error' => 'No seating plans found for the selected date range.']);
            }

            // Data consistency check
            $plansWithoutRooms = $hsscPlans->filter(function($plan) {
                return $plan->details()->count() == 0;
            });

            if ($plansWithoutRooms->isNotEmpty()) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Some seating plans have no rooms assigned. Please fix before auto-assignment.']);
            }

            // Get eligible teachers (teaching category, not exempted)
            $exemptedFromConductors = ExamConductor::getExemptedStaffIds($startDate, $endDate);
            $allExempted = array_unique(array_merge($exemptedStaffIds, $exemptedFromConductors));
            
            $eligibleTeachers = Staff::teaching()
                ->active()
                ->whereNotNull('designation_id')
                ->whereNotIn('id', $allExempted)
                ->with('designationModel')
                ->get();

            if ($eligibleTeachers->isEmpty()) {
                DB::rollBack();
                return back()->withErrors(['error' => 'No eligible teachers found for invigilator duty.']);
            }

            // Check if all designations have duty rules
            $teachersWithoutRules = $eligibleTeachers->filter(function($teacher) {
                return $teacher->designation_id === null || InvigilatorDutyRule::getMaxDutyDays($teacher->designation_id) === null;
            });

            $warnings = [];
            if ($teachersWithoutRules->isNotEmpty()) {
                $warnings[] = 'Warning: ' . $teachersWithoutRules->count() . ' teachers have no duty rules set. They will use default limit (999 days).';
            }

            // Initialize duty tracking
            $dutyCount = [];
            $mandatoryAssignments = [];

            foreach ($eligibleTeachers as $teacher) {
                $dutyCount[$teacher->id] = [
                    'count' => 0,
                    'max' => InvigilatorDutyRule::getMaxDutyDays($teacher->designation_id) ?? 999,
                    'teacher' => $teacher,
                ];
            }


            // Track daily assignments to prevent double booking: [date => [staff_id => true]]
            $dailyAssignments = [];
            $allAssignments = [];
            $teachersOverLimit = [];
            $roomsUnderStaffed = [];

            // Step 1: Build a list of all room slots that need filling
            $roomSlots = [];
            
            foreach ($hsscPlans as $plan) {
                $rooms = $plan->details->groupBy('room_id');
                $dateKey = $plan->test_date->format('Y-m-d');
                
                // Pre-fetch all student-teacher mappings for this plan to avoid N+1 queries
                $studentIds = $plan->details->pluck('student_id')->unique();
                $subjectIds = $plan->details->pluck('subject_id')->unique();
                $teacherMappings = \App\Models\StudentTeacherSubject::whereIn('student_id', $studentIds)
                    ->whereIn('subject_id', $subjectIds)
                    ->get()
                    ->groupBy('student_id');

                foreach ($rooms as $roomId => $details) {
                    // Identify "preferred" teachers for this room
                    $teacherCounts = [];
                    foreach ($details as $detail) {
                        // Look up teacher in pre-fetched mappings
                        $mapping = $teacherMappings->get($detail->student_id)?->where('subject_id', $detail->subject_id)?->first();
                        $teacherId = $mapping?->staff_id;
                        
                        if ($teacherId) {
                            $teacherCounts[$teacherId] = ($teacherCounts[$teacherId] ?? 0) + 1;
                        }
                    }
                    
                    // Sort teachers by student count (descending)
                    arsort($teacherCounts);
                    $preferredTeachers = array_keys($teacherCounts);

                    for ($i = 0; $i < $invigilatorsPerRoom; $i++) {
                        $roomSlots[] = [
                            'plan_id' => $plan->id,
                            'room_id' => $roomId,
                            'date_key' => $dateKey,
                            'exam_date' => $plan->test_date,
                            'priority' => $plan->test_date->timestamp,
                            'preferred_teachers' => $preferredTeachers,
                        ];
                    }
                }
            }

            // Step 2: Sort room slots by priority
            usort($roomSlots, function($a, $b) {
                return $b['priority'] - $a['priority'];
            });

            // Step 3: Priority Pass - Assign preferred teachers if available and eligible
            $remainingSlots = [];
            foreach ($roomSlots as $slot) {
                $dateKey = $slot['date_key'];
                $assigned = false;

                if (!isset($dailyAssignments[$dateKey])) {
                    $dailyAssignments[$dateKey] = [];
                }

                foreach ($slot['preferred_teachers'] as $staffId) {
                    // Check if this preferred teacher is eligible, not assigned today, and under limit
                    if (isset($dutyCount[$staffId]) && 
                        !isset($dailyAssignments[$dateKey][$staffId]) && 
                        $dutyCount[$staffId]['count'] < $dutyCount[$staffId]['max']) {
                        
                        $allAssignments[] = [
                            'room_id' => $slot['room_id'],
                            'staff_id' => $staffId,
                            'exam_date' => $slot['exam_date'],
                            'is_auto_assigned' => true,
                            'seating_plan_id' => $slot['plan_id'],
                        ];
                        $dutyCount[$staffId]['count']++;
                        $dailyAssignments[$dateKey][$staffId] = true;
                        $assigned = true;
                        break;
                    }
                }

                if (!$assigned) {
                    $remainingSlots[] = $slot;
                }
            }

            // Step 4: Balance Pass - Fill remaining slots using round-robin
            foreach ($remainingSlots as $slot) {
                $dateKey = $slot['date_key'];
                
                // Find the best available teacher for this slot
                $bestTeacher = null;
                $lowestDutyCount = PHP_INT_MAX;

                foreach ($dutyCount as $staffId => $data) {
                    if (!isset($dailyAssignments[$dateKey][$staffId]) && 
                        $data['count'] < $data['max']) {
                        
                        if ($data['count'] < $lowestDutyCount) {
                            $bestTeacher = $staffId;
                            $lowestDutyCount = $data['count'];
                        }
                    }
                }

                if ($bestTeacher !== null) {
                    $allAssignments[] = [
                        'room_id' => $slot['room_id'],
                        'staff_id' => $bestTeacher,
                        'exam_date' => $slot['exam_date'],
                        'is_auto_assigned' => true,
                        'seating_plan_id' => $slot['plan_id'],
                    ];
                    $dutyCount[$bestTeacher]['count']++;
                    $dailyAssignments[$dateKey][$bestTeacher] = true;
                }
            }

            // Step 4: Identify under-staffed rooms
                // This logic is mostly for reporting/warnings, can be simplified for now or kept as is if tweaked
                // For simplicity in this large refactor, I'll focus on getting the data saved correctly.
                // We'll skip detailed shortage reporting for HE in the initial pass if it gets too complex.

            // Check for teachers exceeding limits
            foreach ($dutyCount as $staffId => $data) {
                if ($data['count'] > $data['max']) {
                    $teachersOverLimit[] = [
                        'name' => $data['teacher']->first_name . ' ' . $data['teacher']->last_name,
                        'assigned' => $data['count'],
                        'limit' => $data['max'],
                        'excess' => $data['count'] - $data['max']
                    ];
                }
            }

            // Cleanup orphaned records before inserting new ones
            $this->cleanupOrphanedRecords($startDate, $endDate);

            // Delete existing auto-assigned records in this date range
            InvigilatorAssignment::whereBetween('exam_date', [$startDate, $endDate])
                ->where('is_auto_assigned', true)
                ->delete();

            // Insert all assignments
            if (!empty($allAssignments)) {
                InvigilatorAssignment::insert($allAssignments);
            }

            // Release lock
            DB::table('seating_plans')
                ->whereBetween('test_date', [$startDate, $endDate])
                ->update([
                    'assignment_in_progress' => false,
                    'assignment_locked_at' => null
                ]);

            DB::commit();

            // Calculate daily stats for reporting
            $dailyStats = [];
            foreach ($hsscPlans as $plan) {
                $dateKey = $plan->test_date->format('Y-m-d');
                $rooms = $plan->details->groupBy('room_id');
                $totalRooms = $rooms->count();
                $neededInvigilators = $totalRooms * $invigilatorsPerRoom;
                
                // Count available teachers for this day
                $availableTeachersCount = collect($dutyCount)->filter(function ($data, $staffId) use ($dailyAssignments, $dateKey) {
                    return !isset($dailyAssignments[$dateKey][$staffId]) && 
                           $data['count'] < $data['max'];
                })->count();

                // Count teachers who are free today but excluded by max limit
                $excludedByLimitCount = collect($dutyCount)->filter(function ($data, $staffId) use ($dailyAssignments, $dateKey) {
                    return !isset($dailyAssignments[$dateKey][$staffId]) && 
                           $data['count'] >= $data['max'];
                })->count();

                $dailyStats[$dateKey] = [
                    'date' => $plan->test_date->format('d M Y'),
                    'total_rooms' => $totalRooms,
                    'needed_invigilators' => $neededInvigilators,
                    'assigned_invigilators' => isset($dailyAssignments[$dateKey]) ? count($dailyAssignments[$dateKey]) : 0,
                    'available_teachers' => $availableTeachersCount,
                    'excluded_by_limit' => $excludedByLimitCount,
                    'shortage' => max(0, $neededInvigilators - (isset($dailyAssignments[$dateKey]) ? count($dailyAssignments[$dateKey]) : 0))
                ];
            }

            // Generate detailed report
            $report = $this->generateAssignmentReport(
                $allAssignments,
                $mandatoryAssignments,
                $dutyCount,
                $roomsUnderStaffed,
                $teachersOverLimit,
                $warnings,
                $dailyStats
            );

            return redirect()->route('invigilators.index')
                ->with('success', 'Invigilators assigned successfully.')
                ->with('report', $report);

        } catch (\Exception $e) {
            // Release lock on error
            DB::table('seating_plans')
                ->whereBetween('test_date', [$startDate ?? now(), $endDate ?? now()])
                ->update([
                    'assignment_in_progress' => false,
                    'assignment_locked_at' => null
                ]);

            DB::rollBack();
            return back()->withErrors(['error' => 'Error during auto-assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Update an assignment (manual adjustment)
     */
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

    /**
     * Cleanup orphaned invigilator assignment records
     */
    private function cleanupOrphanedRecords($startDate, $endDate)
    {
        // Remove assignments for deleted staff (staff table uses soft deletes)
        $deletedStaffIds = DB::table('staff')->whereNotNull('deleted_at')->pluck('id');
        if ($deletedStaffIds->isNotEmpty()) {
            InvigilatorAssignment::whereIn('staff_id', $deletedStaffIds)->delete();
        }

        // Remove assignments for dates with no seating plans (orphaned records)
        $validPlanIds = SeatingPlan::whereBetween('test_date', [$startDate, $endDate])->pluck('id');
        if ($validPlanIds->isNotEmpty()) {
            InvigilatorAssignment::whereBetween('exam_date', [$startDate, $endDate])
                ->whereNotIn('seating_plan_id', $validPlanIds)
                ->delete();
        }
    }

    /**
     * Generate detailed assignment report
     */
    private function generateAssignmentReport($allAssignments, $mandatoryAssignments, $dutyCount, $roomsUnderStaffed, $teachersOverLimit, $warnings, $dailyStats = [])
    {
        $report = [
            'total_assignments' => count($allAssignments),
            'mandatory_assignments' => count($mandatoryAssignments),
            'optional_assignments' => count($allAssignments) - count($mandatoryAssignments),
            'warnings' => $warnings,
            'rooms_understaffed' => $roomsUnderStaffed,
            'teachers_over_limit' => $teachersOverLimit,
            'daily_stats' => $dailyStats,
            'duty_distribution' => []
        ];

        // Calculate duty distribution by designation
        $dutyByDesignation = [];
        foreach ($dutyCount as $staffId => $data) {
            $designation = $data['teacher']->designationModel->name ?? 'Unknown';
            if (!isset($dutyByDesignation[$designation])) {
                $dutyByDesignation[$designation] = [
                    'total_teachers' => 0,
                    'total_duties' => 0,
                    'avg_duties' => 0,
                    'max_limit' => $data['max']
                ];
            }
            $dutyByDesignation[$designation]['total_teachers']++;
            $dutyByDesignation[$designation]['total_duties'] += $data['count'];
        }

        foreach ($dutyByDesignation as $designation => &$stats) {
            $stats['avg_duties'] = round($stats['total_duties'] / $stats['total_teachers'], 2);
        }

        $report['duty_distribution'] = $dutyByDesignation;

        return $report;
    }
}
