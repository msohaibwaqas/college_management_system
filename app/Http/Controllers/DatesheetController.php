<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Datesheet;
use App\Models\Subject;
use App\Models\Admissions;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DatesheetController extends Controller
{
    public function index()
    {
        $datesheets = Datesheet::with('subject')
            ->orderBy('test_date')
            ->orderBy('admission_year')
            ->get();

        return view('datesheets.index', compact('datesheets'));
    }

    public function create()
    {
        $admissionYears = Admissions::distinctAdmissionYears();
            
        $subjects = Subject::orderBy('name')->get();

        return view('datesheets.create', compact('admissionYears', 'subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'test_date' => 'required|date|after_or_equal:today',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $admissionYear = $request->admission_year;
        $testDate = $request->test_date;
        $subjectIds = $request->subject_ids;

        // Check for Sunday
        if (date('N', strtotime($testDate)) == 7) {
            return back()->with('error', 'Tests cannot be scheduled on Sunday.')->withInput();
        }

        $conflicts = [];
        $savedCount = 0;

        // We use a transaction to ensure atomicity if needed, though here we might partial save
        DB::transaction(function () use ($admissionYear, $testDate, $subjectIds, &$conflicts, &$savedCount) {
            // 1. Get existing tests on this date for this admission year
            $existingTests = Datesheet::where('test_date', $testDate)
                ->where('admission_year', $admissionYear)
                ->get();

            foreach ($subjectIds as $newSubjectId) {
                $newSubject = Subject::find($newSubjectId);
                $hasConflict = false;

                // Get students for new subject
                $newStudents = $this->getStudentIdsForSubject($newSubjectId, $admissionYear);

                // Check against existing tests (and newly added ones in this loop)
                foreach ($existingTests as $existingTest) {
                    // Skip if same subject (duplicate check)
                    if ($existingTest->subject_id == $newSubjectId) {
                        $conflicts[] = "Subject '{$newSubject->name}' is already scheduled on this date.";
                        $hasConflict = true;
                        break;
                    }

                    $existingStudents = $this->getStudentIdsForSubject($existingTest->subject_id, $admissionYear);
                    
                    // Check intersection
                    $common = $newStudents->intersect($existingStudents);
                    
                    if ($common->isNotEmpty()) {
                        $conflicts[] = "Conflict: Subject '{$newSubject->name}' shares {$common->count()} student(s) with '{$existingTest->subject->name}' scheduled on this date.";
                        $hasConflict = true;
                        break; 
                    }
                }
                
                if (!$hasConflict) {
                    $datesheet = Datesheet::create([
                        'admission_year' => $admissionYear,
                        'test_date' => $testDate,
                        'subject_id' => $newSubjectId,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    
                    $savedCount++;
                    // Add to existing tests for subsequent checks in this loop
                    $existingTests->push($datesheet);
                }
            }
        });

        if (count($conflicts) > 0) {
            return redirect()->route('datesheets.index')->with('warning', "Saved $savedCount tests. Conflicts: " . implode(' | ', $conflicts));
        }

        return redirect()->route('datesheets.index')->with('success', "Successfully scheduled $savedCount tests.");
    }

    public function destroy(Datesheet $datesheet)
    {
        $datesheet->delete();
        return back()->with('success', 'Test deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:datesheets,id',
        ]);

        Datesheet::whereIn('id', $request->ids)->delete();

        return back()->with('success', 'Selected tests deleted successfully.');
    }

    public function summary(Request $request)
    {
        $query = Datesheet::with('subject')->orderBy('test_date');

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('test_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('test_date', '<=', $request->end_date);
        }

        $datesheets = $query->get();

        $grouped = $datesheets->groupBy(function($item) {
            return $item->test_date->format('Y-m-d');
        });

        $batchClasses = DB::table('admissions')
            ->select('admission_year', 'current_class')
            ->distinct()
            ->whereIn('status', ['Active', 'Admitted'])
            ->pluck('current_class', 'admission_year');

        $schedule = [];

        foreach ($grouped as $date => $tests) {
            $row = [
                'date' => \Carbon\Carbon::parse($date),
                'class_11' => [],
                'class_12' => [],
                'total_students' => 0
            ];

            foreach ($tests as $test) {
                $class = $batchClasses[$test->admission_year] ?? 'Unknown';
                $studentCount = $this->getStudentIdsForSubject($test->subject_id, $test->admission_year)->count();
                
                $subjectName = $test->subject->name;
                
                if ($class == '11') {
                    $row['class_11'][] = $subjectName;
                } elseif ($class == '12') {
                    $row['class_12'][] = $subjectName;
                }
                
                $row['total_students'] += $studentCount;
            }
            
            $schedule[] = $row;
        }

        return view('datesheets.summary', compact('schedule'));
    }

    public function generate()
    {
        $admissionYears = Admissions::distinctAdmissionYears();

        return view('datesheets.generate', compact('admissionYears'));
    }

    public function storeGenerated(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'admission_years' => 'required|array',
            'admission_years.*' => 'integer',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $admissionYears = $request->admission_years;

        // Generate pool of available dates (excluding Sundays)
        $availableDates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                $availableDates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        if (empty($availableDates)) {
            return back()->with('error', 'No available dates in the selected range (excluding Sundays).')->withInput();
        }

        $totalScheduled = 0;
        $errors = [];

        DB::transaction(function () use ($admissionYears, $availableDates, &$totalScheduled, &$errors) {
            // Global schedule tracker for load balancing across all batches
            $dailyLoad = array_fill_keys($availableDates, 0);

            foreach ($admissionYears as $year) {
                // 1. Get Batch Info (Majority Class)
                $batchInfo = Admissions::where('admission_year', $year)
                    ->whereIn('status', ['Admitted', 'Active'])
                    ->select('current_class', DB::raw('count(*) as total'))
                    ->groupBy('current_class')
                    ->orderByDesc('total')
                    ->first();
                
                $currentClass = $batchInfo ? $batchInfo->current_class : null;

                // 2. Get Students
                $students = Student::active()
                    ->inBatch($year)
                    ->get(['id']);

                if ($students->isEmpty()) continue;
                $studentIds = $students->pluck('id');

                // 3. Get Subjects & Categorize
                $compulsoryIds = DB::table('student_compulsory_subjects')
                    ->whereIn('student_id', $studentIds)
                    ->select('subject_id')
                    ->distinct()
                    ->pluck('subject_id');

                $electiveIds = DB::table('student_elective_subjects')
                    ->whereIn('student_id', $studentIds)
                    ->select('subject_id')
                    ->distinct()
                    ->pluck('subject_id');

                $allSubjectIds = $compulsoryIds->merge($electiveIds)->unique();

                // 4. Filter Subjects (Class Level & Name-based Rules)
                $filteredSubjects = $allSubjectIds->filter(function($sid) use ($currentClass) {
                    $subject = Subject::find($sid);
                    if (!$subject) return false;

                    // Name-based exclusions (Hard Rules requested by user)
                    $name = strtolower($subject->name);
                    if ($currentClass == '12' && (str_contains($name, 'islamic') || str_contains($name, 'islamiat'))) {
                        return false; // Class 12 doesn't take Islamic Studies
                    }
                    if ($currentClass == '11' && (str_contains($name, 'pak') && str_contains($name, 'stud'))) {
                        return false; // Class 11 doesn't take Pak Studies
                    }

                    // Standard Class Level Check
                    if ($subject->class_level === 'both') return true;
                    if ($currentClass && $subject->class_level != $currentClass) return false;

                    return true;
                });

                // 5. Build Metadata
                $subjectMeta = [];
                foreach ($filteredSubjects as $sid) {
                    $subject = Subject::find($sid);
                    $studentIdsForSub = $this->getStudentIdsForSubject($sid, $year);
                    $subjectMeta[$sid] = [
                        'type' => $subject->type, // Compulsory or Elective
                        'students' => $studentIdsForSub,
                        'count' => $studentIdsForSub->count(),
                        'object' => $subject
                    ];
                }

                // 6. Split into Phases
                $compulsoryGroup = $filteredSubjects->filter(function($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['type'] === 'Compulsory';
                })->sortByDesc(function($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['count'];
                });

                $electiveGroup = $filteredSubjects->filter(function($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['type'] !== 'Compulsory';
                })->sortByDesc(function($sid) use ($subjectMeta) {
                    return $subjectMeta[$sid]['count'];
                });

                // 7. Schedule
                $scheduled = []; 
                // Pre-fill existing
                $existingDatesheets = Datesheet::where('admission_year', $year)->get();
                foreach ($existingDatesheets as $ds) {
                    $d = $ds->test_date->format('Y-m-d');
                    if (!isset($scheduled[$d])) $scheduled[$d] = [];
                    $scheduled[$d][] = $ds->subject_id;
                    if (isset($dailyLoad[$d])) {
                        $dailyLoad[$d] += $this->getStudentIdsForSubject($ds->subject_id, $year)->count();
                    }
                }

                $lastCompulsoryDateIndex = -1;

                // Helper to schedule a list of subjects
                $scheduleList = function($subjectList, $startIndex = 0) use (&$scheduled, &$dailyLoad, $availableDates, $subjectMeta, $year, &$totalScheduled, &$errors, &$lastCompulsoryDateIndex) {
                    
                    foreach ($subjectList as $sid) {
                        // Check if already scheduled IN THE CURRENT REQUESTED RANGE
                        $isDone = false;
                        // We need to check if this subject is scheduled within the available dates we are generating for
                        // The $availableDates array contains the strings 'Y-m-d' of the current range
                        foreach ($availableDates as $availDate) {
                            if (isset($scheduled[$availDate]) && in_array($sid, $scheduled[$availDate])) {
                                $isDone = true;
                                break;
                            }
                        }
                        if ($isDone) continue;

                        $meta = $subjectMeta[$sid];
                        $validDates = [];

                        // Search from startIndex
                        for ($i = $startIndex; $i < count($availableDates); $i++) {
                            $date = $availableDates[$i];
                            
                            // Hard Conflict Check
                            $hardConflict = false;
                            if (isset($scheduled[$date])) {
                                foreach ($scheduled[$date] as $existingSid) {
                                    // If we don't have meta for existing (e.g. from DB), fetch it
                                    $existingStudents = isset($subjectMeta[$existingSid]) 
                                        ? $subjectMeta[$existingSid]['students'] 
                                        : $this->getStudentIdsForSubject($existingSid, $year);

                                    if ($meta['students']->intersect($existingStudents)->isNotEmpty()) {
                                        $hardConflict = true; break;
                                    }
                                }
                            }
                            if (!$hardConflict) {
                                $validDates[] = ['date' => $date, 'index' => $i];
                            }
                        }

                        if (empty($validDates)) {
                            // Try searching BEFORE startIndex if we failed (fallback for electives)
                            if ($startIndex > 0) {
                                for ($i = 0; $i < $startIndex; $i++) {
                                    $date = $availableDates[$i];
                                    $hardConflict = false;
                                    if (isset($scheduled[$date])) {
                                        foreach ($scheduled[$date] as $existingSid) {
                                            $existingStudents = isset($subjectMeta[$existingSid]) 
                                                ? $subjectMeta[$existingSid]['students'] 
                                                : $this->getStudentIdsForSubject($existingSid, $year);
                                            if ($meta['students']->intersect($existingStudents)->isNotEmpty()) {
                                                $hardConflict = true; break;
                                            }
                                        }
                                    }
                                    if (!$hardConflict) {
                                        $validDates[] = ['date' => $date, 'index' => $i];
                                    }
                                }
                            }
                        }

                        if (empty($validDates)) {
                            $errors[] = "Could not schedule '{$meta['object']->name}' for Batch $year.";
                            continue;
                        }

                        // Score Dates: Gap Preference + Load Balancing
                        $bestDate = null;
                        $bestScore = -1; // Lower is better
                        $bestIndex = -1;

                        foreach ($validDates as $item) {
                            $date = $item['date'];
                            $idx = $item['index'];
                            
                            // Gap Check
                            $prevDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
                            $nextDate = \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d');
                            
                            $hasGapConflict = false;
                            $checkGap = function($checkDate) use ($scheduled, $meta, $year, $subjectMeta) {
                                if (isset($scheduled[$checkDate])) {
                                    foreach ($scheduled[$checkDate] as $existingSid) {
                                        $existingStudents = isset($subjectMeta[$existingSid]) 
                                            ? $subjectMeta[$existingSid]['students'] 
                                            : $this->getStudentIdsForSubject($existingSid, $year);
                                        if ($meta['students']->intersect($existingStudents)->isNotEmpty()) {
                                            return true;
                                        }
                                    }
                                }
                                return false;
                            };

                            if ($checkGap($prevDate) || $checkGap($nextDate)) {
                                $gapPenalty = 10000; // High penalty for no gap
                            } else {
                                $gapPenalty = 0;
                            }

                            $loadScore = $dailyLoad[$date];
                            $totalScore = $gapPenalty + $loadScore;

                            if ($bestScore === -1 || $totalScore < $bestScore) {
                                $bestScore = $totalScore;
                                $bestDate = $date;
                                $bestIndex = $idx;
                            }
                        }

                        if ($bestDate) {
                            Datesheet::create([
                                'admission_year' => $year,
                                'test_date' => $bestDate,
                                'subject_id' => $sid,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]);
                            
                            if (!isset($scheduled[$bestDate])) $scheduled[$bestDate] = [];
                            $scheduled[$bestDate][] = $sid;
                            $dailyLoad[$bestDate] += $meta['count'];
                            $totalScheduled++;

                            if ($bestIndex > $lastCompulsoryDateIndex) {
                                $lastCompulsoryDateIndex = $bestIndex;
                            }
                        }
                    }
                };

                // Schedule Compulsory (Start from beginning)
                $scheduleList($compulsoryGroup, 0);

                // Schedule Elective (Start from where Compulsory ended + 1, if possible)
                $electiveStartIndex = $lastCompulsoryDateIndex + 1;
                if ($electiveStartIndex >= count($availableDates)) {
                    $electiveStartIndex = 0; // Fallback if no space left
                }
                $scheduleList($electiveGroup, $electiveStartIndex);
            }
        });

        if (count($errors) > 0) {
            return redirect()->route('datesheets.index')->with('warning', "Scheduled $totalScheduled tests. Issues: " . implode(' | ', $errors));
        }

        return redirect()->route('datesheets.index')->with('success', "Successfully generated datesheet with $totalScheduled tests.");
    }

    private function getStudentIdsForSubject($subjectId, $admissionYear)
    {
        $subject = Subject::find($subjectId);
        if (!$subject) return collect();

        $query = Student::active()->inBatch($admissionYear);

        if ($subject->type === 'Compulsory') {
            $query->where(function($q) use ($subjectId) {
                $q->whereHas('compulsorySubjects', function($sq) use ($subjectId) {
                    $sq->where('subject_id', $subjectId);
                })->orWhereNotExists(function($eq) {
                    $eq->select(DB::raw(1))
                        ->from('student_compulsory_subjects')
                        ->whereColumn('student_compulsory_subjects.student_id', 'students.id');
                });
            });
        } else {
            $query->whereHas('electives', function($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        }

        return $query->pluck('id');
    }
}
