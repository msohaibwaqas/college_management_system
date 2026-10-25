<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentTeacherSubject;
use App\Models\StudentSubjectSection;
use App\Models\Admissions;
use App\Models\InstituteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAssignmentService
{
    /**
     * Get distinct admission years for dropdowns.
     */
    public function getAdmissionYears(): Collection
    {
        return Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');
    }

    /**
     * Get data required for the staff panel.
     */
    public function getStaffPanelData($year, $subjectId, $staffId): array
    {
        $admissionYears = $this->getAdmissionYears();

        $subjects = collect();
        if ($year) {
            $subjects = Subject::whereHas('studentTeacherSubjects.student.admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            })->orderBy('name', 'asc')->get();
        }

        $staffMembers = collect();
        if ($year && $subjectId) {
            $staffMembers = Staff::where('category', 'teaching')
                ->where('status', 'active')
                ->whereHas('studentTeacherSubjects', function ($q) use ($year, $subjectId) {
                    $q->whereHas('student.admissions', function ($subQ) use ($year) {
                        $subQ->where('admission_year', $year);
                    })->where('subject_id', $subjectId);
                })
                ->orderBy('first_name', 'asc')
                ->get();
        }

        $students = collect();
        $staffName = '';

        if ($year && $subjectId && $staffId) {
            $staffMember = Staff::find($staffId);
            $staffName = $staffMember ? $staffMember->first_name . ' ' . $staffMember->last_name : '';

            $students = Student::with(['admissions', 'father'])
                ->whereHas('admissions', function ($q) use ($year) {
                    $q->where('admission_year', $year);
                })
                ->whereHas('studentTeacherSubjects', function ($q) use ($subjectId, $staffId) {
                    $q->where('subject_id', $subjectId)
                      ->where('staff_id', $staffId);
                })
                ->get();
        }

        return [
            'admissionYears' => $admissionYears,
            'subjects' => $subjects,
            'staffMembers' => $staffMembers,
            'students' => $students,
            'selectedYear' => $year,
            'selectedSubject' => $subjectId,
            'selectedStaff' => $staffId,
            'staffName' => $staffName,
        ];
    }

    /**
     * Get subjects that have enrolled students in a specific admission year.
     */
    public function getSubjectsByYear($year): Collection
    {
        if (!$year) {
            return collect();
        }

        return Subject::where(function ($query) use ($year) {
            $query->whereHas('studentElectives.student.admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->orWhereHas('studentCompulsorySubjects.student.admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            });
        })
        ->orderBy('name', 'asc')
        ->get(['id', 'name']);
    }

    /**
     * Get active teaching staff specialized in a specific subject.
     */
    public function getTeachersBySubject($subjectId): Collection
    {
        if (!$subjectId) {
            return collect();
        }

        return Staff::where('category', 'teaching')
            ->where('status', 'active')
            ->whereHas('subjectSpecializations', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->orderBy('first_name', 'asc')
            ->get(['id', 'first_name', 'last_name']);
    }

    /**
     * Get subject sections for a specific admission year and subject.
     */
    public function getSectionsByYear($year, $subjectId): Collection
    {
        if (!$year || !$subjectId) {
            return collect();
        }

        return StudentSubjectSection::where('admission_year', $year)
            ->where('subject_id', $subjectId)
            ->select('section')
            ->selectRaw('COUNT(*) as student_count')
            ->selectRaw('GROUP_CONCAT(student_id ORDER BY student_id) as student_ids_csv')
            ->groupBy('section')
            ->orderBy('section')
            ->get()
            ->map(function ($row) {
                return [
                    'section'       => $row->section,
                    'student_count' => (int) $row->student_count,
                    'student_ids'   => array_map('intval', explode(',', $row->student_ids_csv)),
                ];
            });
    }

    /**
     * Get unassigned students for a subject in an admission year.
     */
    public function getUnassignedStudents($year, $subjectId): Collection
    {
        if (!$year || !$subjectId) {
            return collect();
        }

        return Student::with([
                'admissions',
                'father',
                'subjectSections' => function ($q) use ($subjectId, $year) {
                    $q->where('subject_id', $subjectId)->where('admission_year', $year);
                }
            ])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereHas('admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->where(function ($query) use ($subjectId) {
                $query->whereHas('electives', function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                })
                ->orWhereHas('compulsorySubjects', function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                });
            })
            ->whereDoesntHave('studentTeacherSubjects', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
            ->select('students.*')
            ->get();
    }

    /**
     * Get assigned students for a specific teacher and subject in an admission year.
     */
    public function getAssignedStudents($year, $subjectId, $staffId): Collection
    {
        if (!$year || !$subjectId || !$staffId) {
            return collect();
        }

        return Student::with([
                'admissions',
                'father',
                'subjectSections' => function ($q) use ($subjectId, $year) {
                    $q->where('subject_id', $subjectId)->where('admission_year', $year);
                }
            ])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereHas('admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->whereHas('studentTeacherSubjects', function ($q) use ($subjectId, $staffId) {
                $q->where('subject_id', $subjectId)
                  ->where('staff_id', $staffId);
            })
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
            ->select('students.*')
            ->get();
    }

    /**
     * Assign students to a teacher for a subject.
     */
    public function assignStudents(array $studentIds, $staffId, $subjectId, $academicYear, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();

        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            if (!$student) continue;

            $admission = $student->admissions->first();

            $existingAssignment = StudentTeacherSubject::withTrashed()
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->first();

            if ($existingAssignment) {
                $existingAssignment->staff_id = $staffId;
                $existingAssignment->class_level = $admission->current_class ?? '11';
                $existingAssignment->academic_year = $academicYear;
                $existingAssignment->assignment_type = 'elective';
                $existingAssignment->updated_by = $userId;
                $existingAssignment->restore();
                $existingAssignment->save();
            } else {
                StudentTeacherSubject::create([
                    'student_id' => $studentId,
                    'staff_id' => $staffId,
                    'subject_id' => $subjectId,
                    'class_level' => $admission->current_class ?? '11',
                    'academic_year' => $academicYear,
                    'assignment_type' => 'elective',
                    'created_by' => $userId,
                ]);
            }
        }
    }

    /**
     * Unassign students from a teacher for a subject.
     */
    public function unassignStudents(array $studentIds, $staffId, $subjectId): void
    {
        $assignments = StudentTeacherSubject::whereIn('student_id', $studentIds)
            ->where('staff_id', $staffId)
            ->where('subject_id', $subjectId)
            ->get();

        foreach ($assignments as $assignment) {
            $assignment->delete();
        }
    }

    /**
     * Export staff list as a streamed CSV response.
     */
    public function exportStaffCsv(array $filters = []): StreamedResponse
    {
        $query = Staff::with('bloodGroup');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $staff = $query->orderBy('first_name', 'asc')->get();

        $filename = "staff_export_" . date('Y-m-d_His') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($staff) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Staff ID',
                'Name',
                'Father Name',
                'CNIC',
                'Contact',
                'Category',
                'Designation',
                'BPS',
                'Status'
            ]);

            foreach ($staff as $member) {
                fputcsv($file, [
                    $member->staff_id,
                    $member->first_name . ' ' . $member->last_name,
                    $member->father_name,
                    $member->cnic,
                    $member->contact_number,
                    ucfirst($member->category),
                    $member->designation,
                    $member->bps,
                    ucfirst($member->status)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate empty award list PDF download response.
     */
    public function downloadAwardListPdf($year, $subjectId, $staffId)
    {
        $subject = Subject::findOrFail($subjectId);
        $staff = Staff::findOrFail($staffId);

        $students = Student::with(['admissions'])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereHas('admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->whereHas('studentTeacherSubjects', function ($q) use ($subjectId, $staffId) {
                $q->where('subject_id', $subjectId)
                  ->where('staff_id', $staffId);
            })
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
            ->select('students.*')
            ->get();

        $pdf = Pdf::loadView('staff.award_list_pdf', compact('students', 'subject', 'staff', 'year'));
        return $pdf->download('award_list_' . $subject->name . '_' . $year . '.pdf');
    }

    /**
     * Prepare award list data grouped by teacher.
     */
    public function getAwardListData($year, $subjectId): array
    {
        $subject = Subject::findOrFail($subjectId);

        $teachers = Staff::whereHas('studentTeacherSubjects', function ($q) use ($subjectId, $year) {
            $q->where('subject_id', $subjectId)
              ->whereHas('student.admissions', function ($aq) use ($year) {
                  $aq->where('admission_year', $year);
              });
        })
        ->where('category', 'teaching')
        ->where('status', 'active')
        ->orderBy('first_name')
        ->get();

        $teachersData = [];

        foreach ($teachers as $teacher) {
            $students = Student::whereHas('admissions', function ($q) use ($year) {
                $q->where('admission_year', $year);
            })
            ->whereHas('studentTeacherSubjects', function ($q) use ($subjectId, $teacher) {
                $q->where('subject_id', $subjectId)
                  ->where('staff_id', $teacher->id);
            })
            ->with(['admissions' => function ($q) use ($year) {
                $q->where('admission_year', $year);
            }])
            ->get()
            ->map(function ($student) {
                $admission = $student->admissions->first();
                return [
                    'roll_no' => $admission ? $admission->college_roll_no : 'N/A'
                ];
            })
            ->sortBy('roll_no')
            ->values();

            if ($students->count() > 0) {
                $teachersData[] = [
                    'teacher' => $teacher,
                    'students' => $students
                ];
            }
        }

        $settings = InstituteSetting::getSettings();

        return compact('teachersData', 'subject', 'year', 'settings');
    }
}
