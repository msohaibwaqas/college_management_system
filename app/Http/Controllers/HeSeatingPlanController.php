<?php

namespace App\Http\Controllers;

use App\Models\HeDatesheet;
use App\Models\Room;
use App\Models\HeSeatingPlan;
use App\Models\HeSeatingPlanDetail;
use App\Models\HeAdmission;
use App\Models\HeStudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeSeatingPlanController extends Controller
{
    /**
     * Display a listing of seating plans.
     */
    public function index()
    {
        $plans = HeSeatingPlan::with(['datesheet.program', 'datesheet.bsSubject'])
            ->whereHas('datesheet')
            ->orderByDesc('created_at')
            ->paginate(25);
            
        return view('he.seating_plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new seating plan.
     */
    public function create()
    {
        // Get datesheets that don't have a seating plan yet
        $availableDatesheets = HeDatesheet::with(['program', 'bsSubject'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('he_seating_plans')
                    ->whereColumn('he_seating_plans.he_datesheet_id', 'he_datesheets.id')
                    ->whereNull('he_seating_plans.deleted_at');
            })
            ->orderBy('exam_date')
            ->get();

        return view('he.seating_plans.create', compact('availableDatesheets'));
    }

    /**
     * Store a newly created seating plan.
     */
    public function store(Request $request)
    {
        $request->validate([
            'he_datesheet_id' => 'required|exists:he_datesheets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $datesheet = HeDatesheet::findOrFail($request->he_datesheet_id);

        // Check if plan already exists
        if (HeSeatingPlan::where('he_datesheet_id', $datesheet->id)->exists()) {
            return back()->with('error', 'Seating plan for this exam already exists.');
        }

        // 1. Get Students enrolled in this subject for this semester
        $students = HeStudentSubject::with('heAdmission.student')
            ->where('bs_subject_id', $datesheet->bs_subject_id)
            ->where('semester_no', $datesheet->semester_no)
            ->where('status', 'Enrolled')
            ->whereHas('heAdmission', function($q) use ($datesheet) {
                $q->where('program_id', $datesheet->program_id)
                  ->where('admission_year', $datesheet->academic_year)
                  ->whereIn('status', ['Admitted', 'Active']);
            })
            ->get()
            ->sortBy(function($item) {
                return $item->heAdmission->college_roll_no ?? 'ZZZ' . $item->id;
            });

        if ($students->isEmpty()) {
            return back()->with('error', 'No enrolled students found for this subject/semester.');
        }

        // 2. Get Active Rooms
        $rooms = Room::where('is_active', true)->forExams()->orderBy('name')->get();
        if ($rooms->isEmpty()) {
            return back()->with('error', 'No active rooms available.');
        }

        // 3. Distribute
        DB::beginTransaction();
        try {
            $plan = HeSeatingPlan::create([
                'he_datesheet_id' => $datesheet->id,
                'title' => $request->title,
                'description' => $request->description,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $studentIndex = 0;
            $totalStudents = $students->count();
            $studentsArray = $students->values()->all();

            foreach ($rooms as $room) {
                if ($studentIndex >= $totalStudents) break;

                $capacity = $room->capacity;
                $seatedCount = 0;

                while ($seatedCount < $capacity && $studentIndex < $totalStudents) {
                    $studentSubject = $studentsArray[$studentIndex];
                    
                    HeSeatingPlanDetail::create([
                        'he_seating_plan_id' => $plan->id,
                        'he_admission_id' => $studentSubject->he_admission_id,
                        'room_id' => $room->id,
                        'seat_number' => $seatedCount + 1,
                    ]);

                    $studentIndex++;
                    $seatedCount++;
                }
            }

            DB::commit();
            return redirect()->route('he.seating_plans.index')->with('success', 'Seating plan generated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error generating seating plan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified seating plan.
     */
    public function show(HeSeatingPlan $seatingPlan)
    {
        $seatingPlan->load(['datesheet.program', 'datesheet.bsSubject', 'details.heAdmission.student', 'details.room']);

        // Group by Room
        $data = $seatingPlan->details->groupBy('room_id')->map(function ($details) use ($seatingPlan) {
            $room = $details->first()->room;
            
            // Calculate subject counts (Filter out and count valid admissions)
            $subjectCounts = [[
                'name' => $seatingPlan->datesheet->bsSubject->name ?? 'N/A',
                'count' => $details->filter(fn($d) => $d->heAdmission)->count()
            ]];

            return [
                'room_name' => $room->name,
                'students' => $details->sortBy('seat_number'),
                'subject_counts' => $subjectCounts,
            ];
        });

        return view('he.seating_plans.show', compact('seatingPlan', 'data'));
    }

    /**
     * Generate attendance sheet for the seating plan.
     */
    public function attendanceSheet(HeSeatingPlan $seatingPlan)
    {
        $seatingPlan->load(['datesheet.program', 'datesheet.bsSubject', 'details.heAdmission.student', 'details.room']);
        
        $instituteSettings = \App\Models\InstituteSetting::first();

        // Group by Room
        $data = $seatingPlan->details->groupBy('room_id')->map(function ($details) {
            $room = $details->first()->room;
            return [
                'room_name' => $room->name,
                'students' => $details->sortBy('seat_number')->map(function($detail) {
                    $detail->student_name_safe = $detail->heAdmission ? ($detail->heAdmission->student->full_name ?? 'Deleted Student') : 'Empty / Vacant';
                    return $detail;
                }),
            ];
        });

        return view('he.seating_plans.attendance_sheet', compact('seatingPlan', 'data', 'instituteSettings'));
    }

    /**
     * Remove the specified seating plan.
     */
    public function destroy(HeSeatingPlan $seatingPlan)
    {
        $seatingPlan->deleted_by = Auth::id();
        $seatingPlan->save();
        $seatingPlan->delete();

        return redirect()->route('he.seating_plans.index')->with('success', 'Seating plan deleted successfully.');
    }
}
