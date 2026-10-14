<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\Room;
use App\Models\Staff;
use App\Models\Admissions;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $groups = SubjectGroup::orderBy('name')->get();
        $days = Timetable::getDaysOfWeek();
        $periods = Timetable::getPeriodTimes();

        $timetables = collect();
        $selectedYear = null;
        $selectedDay = $request->input('day_of_week', 'Monday');

        if ($request->filled('admission_year')) {
            $selectedYear = $request->admission_year;
            $timetables = Timetable::with(['subject', 'room', 'staff', 'group'])
                ->where('admission_year', $selectedYear)
                ->where('day_of_week', $selectedDay)
                ->orderBy('period_number')
                ->get();
        }

        return view('timetables.index', compact(
            'admissionYears', 'timetables', 'selectedYear',
            'groups', 'days', 'selectedDay', 'periods'
        ));
    }

    public function create()
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $subjects = Subject::orderBy('name')->get();
        $rooms = Room::where('is_active', true)->forLectures()->orderBy('name')->get();
        $staff = Staff::orderBy('first_name')->get();
        $days = Timetable::getDaysOfWeek();
        $periods = Timetable::getPeriodTimes();
        $groups = SubjectGroup::orderBy('name')->get();

        return view('timetables.create', compact('admissionYears', 'subjects', 'rooms', 'staff', 'days', 'periods', 'groups'));
    }

    public function store(Request $request)
    {
        $rules = [
            'admission_year' => 'required|integer',
            'subject_group_id' => 'required|exists:subject_groups,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'period_number' => 'required|integer|min:1|max:7',
            'room_id' => 'nullable|exists:rooms,id',
            'staff_id' => 'nullable|exists:staff,id',
            'is_break' => 'sometimes|boolean',
        ];

        if (!$request->boolean('is_break')) {
            $rules['subject_id'] = 'required|exists:subjects,id';
        }

        $request->validate($rules);

        $periodTimes = Timetable::getPeriodTimes();
        $times = $periodTimes[$request->period_number];

        $isBreak = $request->boolean('is_break');

        if (!$isBreak) {
            // Check for conflicts
            $conflicts = $this->checkConflicts(
                $request->admission_year,
                $request->subject_group_id,
                $request->day_of_week,
                $request->period_number,
                $request->subject_id,
                $request->room_id,
                $request->staff_id
            );

            if (!empty($conflicts)) {
                return back()->withErrors(['conflict' => implode(' ', $conflicts)])->withInput();
            }
        }

        Timetable::create([
            'admission_year' => $request->admission_year,
            'subject_group_id' => $request->subject_group_id,
            'day_of_week' => $request->day_of_week,
            'period_number' => $request->period_number,
            'subject_id' => $isBreak ? null : $request->subject_id,
            'room_id' => $isBreak ? null : $request->room_id,
            'staff_id' => $isBreak ? null : $request->staff_id,
            'is_break' => $isBreak,
            'start_time' => $times['start'],
            'end_time' => $times['end'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.timetables.index', [
            'admission_year' => $request->admission_year,
            'day_of_week' => $request->day_of_week,
        ])->with('success', 'Timetable entry created successfully!');
    }

    public function generateForm()
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        // Find active years for Class 11 and 12
        $year11 = Admissions::where('current_class', '11')->where('status', 'Active')->value('admission_year');
        $year12 = Admissions::where('current_class', '12')->where('status', 'Active')->value('admission_year');

        return view('timetables.generate', compact('admissionYears', 'year11', 'year12'));
    }

    public function generateTimetable(Request $request)
    {
        $request->validate([
            'admission_year_11' => 'nullable|integer',
            'admission_year_12' => 'nullable|integer',
        ]);

        $year11 = $request->admission_year_11;
        $year12 = $request->admission_year_12;

        if (!$year11 && !$year12) {
            return back()->withErrors(['error' => 'Please select at least one admission year.']);
        }

        // Delete existing timetables
        if ($year11) Timetable::where('admission_year', $year11)->delete();
        if ($year12) Timetable::where('admission_year', $year12)->delete();

        $generated = 0;
        $errors = [];

        DB::transaction(function () use ($year11, $year12, &$generated, &$errors) {
            $rooms = Room::where('is_active', true)->orderBy('name')->get();
            if ($rooms->isEmpty()) {
                $errors[] = "No active rooms available";
                return;
            }

            $days = Timetable::getDaysOfWeek();
            $periods = Timetable::getPeriodTimes();

            // Prepare batches and requirements
            $batches = [];
            $compatibilityMatrices = [];
            
            foreach (['11' => $year11, '12' => $year12] as $class => $year) {
                if (!$year) continue;

                $subjects = $this->getSubjectsForBatch($year);
                $requirements = [];
                foreach ($subjects as $subject) {
                    // Class 11: > 50 marks -> 6 days, <= 50 -> 3 days
                    // Class 12: > 50 marks -> 6 days, <= 50 -> 3 days
                    $total = ($class == '11') ? $subject->total_11 : $subject->total_12;
                    $count = ($total > 50) ? 6 : 3;
                    
                    $requirements[] = [
                        'subject' => $subject,
                        'count' => $count,
                        'scheduled' => 0
                    ];
                }
                $batches[$year] = $requirements;
                $compatibilityMatrices[$year] = $this->getSubjectCompatibilityMatrix($year, $subjects);
            }

            $roomUsage = []; // [day][period] = [roomId1, roomId2]
            $scheduleMap = []; // [year][day] = [subjectId1, subjectId2] (Used for checking "already scheduled today")

            // Initialize room usage
            foreach ($days as $day) {
                foreach ($periods as $periodNum => $times) {
                    $roomUsage[$day][$periodNum] = [];
                }
            }

            // Generate
            foreach ($days as $day) {
                foreach ($periods as $periodNum => $times) {
                    
                    // For each batch, try to schedule as many parallel subjects as possible
                    foreach ($batches as $year => &$requirements) {
                        
                        $scheduledInThisSlot = []; // Subject IDs scheduled in this slot for this batch

                        // Greedy approach: keep adding compatible subjects until no more fit or no rooms
                        $madeProgress = true;
                        while ($madeProgress) {
                            $madeProgress = false;
                            
                            $bestReqIndex = -1;
                            $maxRemaining = -1;

                            // Find the best candidate subject
                            foreach ($requirements as $index => $req) {
                                if ($req['scheduled'] >= $req['count']) continue;
                                if (in_array($req['subject']->id, $scheduledInThisSlot)) continue;

                                // Check if already scheduled today (Spread constraint)
                                if (isset($scheduleMap[$year][$day]) && in_array($req['subject']->id, $scheduleMap[$year][$day])) {
                                    continue;
                                }

                                // Check compatibility with ALREADY scheduled subjects in this slot
                                $compatible = true;
                                foreach ($scheduledInThisSlot as $scheduledId) {
                                    if (!$compatibilityMatrices[$year][$req['subject']->id][$scheduledId]) {
                                        $compatible = false;
                                        break;
                                    }
                                }
                                if (!$compatible) continue;

                                $remaining = $req['count'] - $req['scheduled'];
                                $isCompulsory = $req['subject']->type === 'Compulsory' ? 1 : 0;
                                
                                $score = ($isCompulsory * 1000) + ($remaining * 10);
                                
                                if ($score > $maxRemaining) {
                                    $maxRemaining = $score;
                                    $bestReqIndex = $index;
                                }
                            }

                            if ($bestReqIndex != -1) {
                                $candidate = &$requirements[$bestReqIndex];

                                // Find available room
                                $assignedRoom = null;
                                foreach ($rooms as $room) {
                                    if (!in_array($room->id, $roomUsage[$day][$periodNum])) {
                                        $assignedRoom = $room;
                                        break;
                                    }
                                }

                                if ($assignedRoom) {
                                    Timetable::create([
                                        'admission_year' => $year,
                                        'day_of_week' => $day,
                                        'period_number' => $periodNum,
                                        'subject_id' => $candidate['subject']->id,
                                        'room_id' => $assignedRoom->id,
                                        'staff_id' => null,
                                        'start_time' => $times['start'],
                                        'end_time' => $times['end'],
                                        'created_by' => Auth::id(),
                                        'updated_by' => Auth::id(),
                                    ]);

                                    $candidate['scheduled']++;
                                    $roomUsage[$day][$periodNum][] = $assignedRoom->id;
                                    $scheduleMap[$year][$day][] = $candidate['subject']->id;
                                    $scheduledInThisSlot[] = $candidate['subject']->id;
                                    $generated++;
                                    $madeProgress = true;
                                }
                            }
                        }
                    }
                }
            }
        });

        if (!empty($errors)) {
            return redirect()->route('timetables.generate.form')
                ->withErrors(['error' => implode(', ', $errors)]);
        }

        return redirect()->route('admin.timetables.index')
            ->with('success', "Timetable generated successfully! $generated periods created.");
    }

    private function getSubjectCompatibilityMatrix($year, $subjects)
    {
        $matrix = [];
        $subjectIds = $subjects->pluck('id')->toArray();
        
        // Pre-fetch student lists for all subjects to minimize DB queries
        $subjectStudents = [];
        foreach ($subjectIds as $id) {
            $subjectStudents[$id] = DB::table('student_compulsory_subjects')
                ->where('subject_id', $id)
                ->join('admissions', 'student_compulsory_subjects.student_id', '=', 'admissions.student_id')
                ->where('admissions.admission_year', $year)
                ->pluck('student_compulsory_subjects.student_id')
                ->merge(
                    DB::table('student_elective_subjects')
                    ->where('subject_id', $id)
                    ->join('admissions', 'student_elective_subjects.student_id', '=', 'admissions.student_id')
                    ->where('admissions.admission_year', $year)
                    ->pluck('student_elective_subjects.student_id')
                )
                ->unique()
                ->toArray();
        }

        foreach ($subjectIds as $id1) {
            foreach ($subjectIds as $id2) {
                if ($id1 == $id2) {
                    $matrix[$id1][$id2] = false; // Not compatible with self
                    continue;
                }
                
                // Check intersection
                $intersection = array_intersect($subjectStudents[$id1], $subjectStudents[$id2]);
                $matrix[$id1][$id2] = empty($intersection);
            }
        }
        
        return $matrix;
    }

    public function destroy(Timetable $timetable)
    {
        $year = $timetable->admission_year;
        $day = $timetable->day_of_week;
        $timetable->delete();

        return redirect()->route('admin.timetables.index', [
            'admission_year' => $year,
            'day_of_week' => $day,
        ])->with('success', 'Timetable entry deleted successfully!');
    }

    public function destroyAll(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
        ]);

        $deleted = Timetable::where('admission_year', $request->admission_year)->delete();

        return redirect()->route('timetables.index', ['admission_year' => $request->admission_year])
            ->with('success', "Deleted $deleted timetable entries.");
    }

    private function checkConflicts($year, $groupId, $day, $period, $subjectId, $roomId, $staffId)
    {
        $conflicts = [];

        // Check if room is already occupied
        if ($roomId) {
            $roomConflict = Timetable::where('admission_year', $year)
                ->where('day_of_week', $day)
                ->where('period_number', $period)
                ->where('room_id', $roomId)
                ->exists();

            if ($roomConflict) {
                $conflicts[] = "Room is already occupied in this slot.";
            }
        }

        // Check if staff is already assigned
        if ($staffId) {
            $staffConflict = Timetable::where('day_of_week', $day)
                ->where('period_number', $period)
                ->where('staff_id', $staffId)
                ->exists();

            if ($staffConflict) {
                $conflicts[] = "Staff member is already assigned in this slot.";
            }
        }

        // Check if this group+period+day already has an entry for this year
        $slotConflict = Timetable::where('admission_year', $year)
            ->where('subject_group_id', $groupId)
            ->where('day_of_week', $day)
            ->where('period_number', $period)
            ->where('subject_id', $subjectId)
            ->exists();

        if ($slotConflict) {
            $conflicts[] = "This subject is already scheduled for this group in this slot.";
        }

        return $conflicts;
    }

    private function getSubjectsForBatch($year)
    {
        // Get all subjects taken by students in this batch
        $subjectIds = Student::join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->whereIn('admissions.status', ['Admitted', 'Active'])
            ->join('student_compulsory_subjects', 'students.id', '=', 'student_compulsory_subjects.student_id')
            ->pluck('student_compulsory_subjects.subject_id')
            ->merge(
                Student::join('admissions', 'students.id', '=', 'admissions.student_id')
                    ->where('admissions.admission_year', $year)
                    ->whereIn('admissions.status', ['Admitted', 'Active'])
                    ->join('student_elective_subjects', 'students.id', '=', 'student_elective_subjects.student_id')
                    ->pluck('student_elective_subjects.subject_id')
            )
            ->unique();

        return Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
    }
}
