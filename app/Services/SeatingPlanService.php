<?php

namespace App\Services;

use App\Models\Datesheet;
use App\Models\HeDatesheet;
use App\Models\HeStudentSubject;
use App\Models\Room;
use App\Models\Student;
use Illuminate\Support\Collection;

class SeatingPlanService
{
    /**
     * Gather and sort all HSSC candidates scheduled for examinations on a given date.
     * 
     * Sort Order: Admission Year ASC -> Subject ID ASC -> Teacher ID ASC -> Student ID ASC
     */
    public function getHsscCandidatesForDate(string $date): Collection
    {
        $tests = Datesheet::whereDate('test_date', $date)->with('subject')->get();
        if ($tests->isEmpty()) {
            return collect();
        }

        $studentList = collect();

        foreach ($tests as $test) {
            $subjectId = $test->subject_id;
            $year = $test->admission_year;
            $subject = $test->subject;

            if (!$subject) {
                continue;
            }

            $query = Student::active()
                ->inBatch($year)
                ->join('admissions', 'students.id', '=', 'admissions.student_id')
                ->where('admissions.admission_year', $year)
                ->leftJoin('student_teacher_subjects', function ($join) use ($subjectId) {
                    $join->on('students.id', '=', 'student_teacher_subjects.student_id')
                         ->where('student_teacher_subjects.subject_id', '=', $subjectId)
                         ->whereNull('student_teacher_subjects.deleted_at')
                         ->on('student_teacher_subjects.class_level', '=', 'admissions.current_class');
                });

            if ($subject->type === 'Compulsory') {
                $query->whereHas('compulsorySubjects', function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                });
            } else {
                $query->whereHas('electives', function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                });
            }

            $studentsData = $query->select('students.id', 'student_teacher_subjects.staff_id')->get();

            foreach ($studentsData as $data) {
                if (!$studentList->contains('student_id', $data->id)) {
                    $studentList->push([
                        'student_id'     => $data->id,
                        'subject_id'     => $subjectId,
                        'admission_year' => $year,
                        'staff_id'       => $data->staff_id,
                    ]);
                }
            }
        }

        return $studentList->sort(function ($a, $b) {
            if ($a['admission_year'] !== $b['admission_year']) {
                return $a['admission_year'] <=> $b['admission_year'];
            }
            if ($a['subject_id'] !== $b['subject_id']) {
                return $a['subject_id'] <=> $b['subject_id'];
            }
            if ($a['staff_id'] !== $b['staff_id']) {
                if ($a['staff_id'] === null) return 1;
                if ($b['staff_id'] === null) return -1;
                return $a['staff_id'] <=> $b['staff_id'];
            }
            return $a['student_id'] <=> $b['student_id'];
        })->values();
    }

    /**
     * Gather and sort all Higher Education candidates enrolled for a specific datesheet.
     * 
     * Sort Order: College Roll No ASC
     */
    public function getHeCandidatesForDatesheet(HeDatesheet $datesheet): Collection
    {
        return HeStudentSubject::with('heAdmission.student')
            ->where('bs_subject_id', $datesheet->bs_subject_id)
            ->where('semester_no', $datesheet->semester_no)
            ->where('status', 'Enrolled')
            ->whereHas('heAdmission', function ($q) use ($datesheet) {
                $q->where('program_id', $datesheet->program_id)
                  ->where('admission_year', $datesheet->academic_year)
                  ->whereIn('status', ['Admitted', 'Active']);
            })
            ->get()
            ->sortBy(function ($item) {
                return $item->heAdmission->college_roll_no ?? 'ZZZ' . $item->id;
            })
            ->values();
    }

    /**
     * Distribute students across rooms up to room capacities.
     * 
     * @param iterable $candidates
     * @param Collection<Room> $rooms
     * @param callable $assignCallback function($candidate, Room $room, int $seatNo)
     * @return array{total_candidates: int, seated_count: int, unseated_count: int, rooms_used: int}
     */
    public function allocateSeats(iterable $candidates, Collection $rooms, callable $assignCallback): array
    {
        $candidateList = is_array($candidates) ? $candidates : collect($candidates)->values()->all();
        $totalCandidates = count($candidateList);
        $studentIndex = 0;
        $roomsUsed = 0;

        foreach ($rooms as $room) {
            if ($studentIndex >= $totalCandidates) {
                break;
            }

            $capacity = (int) $room->capacity;
            $seatedCount = 0;
            $roomsUsed++;

            while ($seatedCount < $capacity && $studentIndex < $totalCandidates) {
                $candidate = $candidateList[$studentIndex];
                $seatNo = $seatedCount + 1;

                $assignCallback($candidate, $room, $seatNo);

                $studentIndex++;
                $seatedCount++;
            }
        }

        return [
            'total_candidates' => $totalCandidates,
            'seated_count'     => $studentIndex,
            'unseated_count'   => max(0, $totalCandidates - $studentIndex),
            'rooms_used'       => $roomsUsed,
        ];
    }
}
