<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\Admissions;
use App\Services\FeeCalculationService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentSubjectService
{
    public function __construct(
        protected FeeCalculationService $feeService
    ) {}

    /**
     * Get students filtered by subject for an admission year.
     */
    public function getStudentsBySubjectData(?string $year, ?int $subjectId): array
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $subjects = collect();
        if ($year) {
            $subjects = Subject::where('type', 'Elective')
                ->whereHas('studentElectives.student.admissions', function ($q) use ($year) {
                    $q->where('admission_year', $year);
                })
                ->orderBy('name', 'ASC')
                ->get();
        }

        $students = collect();
        $subjectName = '';

        if ($year && $subjectId) {
            $students = Student::with(['admissions', 'father', 'attribute'])
                ->whereHas('admissions', function ($q) use ($year) {
                    $q->where('admission_year', $year);
                })
                ->whereHas('electives', function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                })
                ->get();

            $subject = Subject::find($subjectId);
            $subjectName = $subject ? $subject->name : '';
        }

        return [
            'admissionYears' => $admissionYears,
            'subjects' => $subjects,
            'selectedYear' => $year,
            'selectedSubject' => $subjectId,
            'students' => $students,
            'subjectName' => $subjectName,
        ];
    }

    /**
     * Group students by subject combinations for a given admission year.
     */
    public function getGroupedStudentsData(?string $year): array
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $groups = [];

        if ($year) {
            $students = Student::with(['electives.subject', 'admissions', 'father'])
                ->inBatch((int) $year)
                ->get();

            foreach ($students as $student) {
                $subjectNames = $student->electives->pluck('subject.name')->toArray();
                if (count($subjectNames) > 0) {
                    $group = $student->subject_group;
                    $admission = $student->admissions->first();

                    $groups[$group][] = [
                        'roll_no' => $admission ? $admission->college_roll_no : 'N/A',
                        'name' => $student->full_name,
                        'father_name' => $student->father ? $student->father->name : 'N/A',
                        'subjects' => implode(', ', $subjectNames)
                    ];
                }
            }
        }

        return [
            'admissionYears' => $admissionYears,
            'selectedYear' => $year,
            'groups' => $groups,
        ];
    }

    /**
     * Export grouped students as CSV.
     */
    public function exportGroupsCSV(int|string $year): StreamedResponse
    {
        $students = Student::with(['electives.subject', 'admissions', 'father'])
            ->inBatch((int) $year)
            ->get();

        $filename = "grouped_students_{$year}.csv";
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=$filename"];

        $callback = function () use ($students) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Group', 'Roll No', 'Name', 'Father Name', 'Subjects']);

            foreach ($students as $student) {
                $subjectNames = $student->electives->pluck('subject.name')->toArray();
                $group = count($subjectNames) > 0 ? $student->subject_group : 'Unknown';
                $admission = $student->admissions->first();

                fputcsv($output, [
                    $group,
                    $admission ? $admission->college_roll_no : 'N/A',
                    $student->full_name,
                    $student->father ? $student->father->name : 'N/A',
                    implode(', ', $subjectNames)
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Prepare data for changing student elective subjects.
     */
    public function getChangeSubjectsData(Student $student): array
    {
        $student->load(['electives.subject', 'admissions']);
        $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
        $subjectGroups = SubjectGroup::with('subjects')->get();

        $currentGroupIds = $student->electives->pluck('subject_group_id')->filter()->unique();
        $groupId = $currentGroupIds->count() === 1 ? $currentGroupIds->first() : null;

        if (!$groupId && $student->electives->count() === 3) {
            $studentSubjectIds = $student->electives->pluck('subject_id')->sort()->values()->toArray();
            foreach ($subjectGroups as $group) {
                $groupSubjectIds = $group->subjects->pluck('id')->sort()->values()->toArray();
                if (count(array_intersect($studentSubjectIds, $groupSubjectIds)) === 3) {
                    $groupId = $group->id;
                    break;
                }
            }
        }

        return compact('student', 'subjects', 'subjectGroups', 'groupId');
    }

    /**
     * Update elective subjects and generate fee difference voucher if applicable.
     */
    public function updateStudentSubjects(Student $student, array $newSubjectIds, ?int $userId = null): array
    {
        $result = $this->feeService->calculateDifference($student, $newSubjectIds);
        $difference = $result['difference'];

        if ($difference > 0) {
            $voucher = $this->feeService->createVoucher($student, $result['extra_items']);
            $student->updateElectives($newSubjectIds, $userId);

            return [
                'has_voucher' => true,
                'voucher' => $voucher,
                'difference' => $difference,
                'message' => "Subjects updated successfully. A new voucher ({$voucher->voucher_number}) for the fee difference of {$difference} has been generated."
            ];
        }

        $student->updateElectives($newSubjectIds, $userId);

        return [
            'has_voucher' => false,
            'message' => 'Subjects updated successfully. No additional fees required.'
        ];
    }
}
