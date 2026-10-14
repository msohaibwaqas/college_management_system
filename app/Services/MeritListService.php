<?php

namespace App\Services;

use App\Models\AdmissionApplication;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\SscRecord;
use App\Models\Father;
use App\Models\Contact;
use App\Models\StudentAttribute;
use App\Models\StudentElectiveSubject;
use App\Models\StudentCompulsorySubject;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MeritListService
{
    /**
     * Default roll number starting ranges per group
     */
    const DEFAULT_ROLL_RANGES = [
        'Pre-Medical' => 1,
        'Pre-Engineering' => 101,
        'General Science' => 401,
        'Humanities' => 601,
    ];

    /**
     * Generate merit list and migrate confirmed applicants to students/admissions tables
     *
     * @param int $admissionYear
     * @param array $rollStartRanges e.g. ['Pre-Medical' => 1, 'Pre-Engineering' => 201, ...]
     * @param string $programType
     * @return array Summary of results
     */
    public function generate(int $admissionYear, array $rollStartRanges = [], string $programType = 'Inter'): array
    {
        $rollStartRanges = array_merge(self::DEFAULT_ROLL_RANGES, $rollStartRanges);

        return DB::transaction(function () use ($admissionYear, $rollStartRanges, $programType) {
            // 1. Get all fee_paid applications for this year and program
            $applications = AdmissionApplication::where('admission_year', $admissionYear)
                ->where('program_type', $programType)
                ->where('status', 'fee_paid')
                ->with(['electiveSubject1', 'electiveSubject2', 'electiveSubject3', 'bise'])
                ->get();

            if ($applications->isEmpty()) {
                throw new \Exception('No fee-paid applications found for year ' . $admissionYear);
            }

            // 2. Classify each application into subject group
            foreach ($applications as $app) {
                $app->updateSubjectGroup();
            }

            // Reload to get updated subject_group
            $applications = $applications->fresh();

            // 3. Group by subject_group
            $groups = $applications->groupBy('subject_group');

            $summary = [];
            $totalAdmitted = 0;

            // 4. Process each group
            foreach ($groups as $groupName => $groupApps) {
                // Sort by SSC percentage DESC
                $sorted = $groupApps->sortByDesc(fn($app) => $app->ssc_percentage)->values();

                $startRoll = $rollStartRanges[$groupName] ?? 901; // fallback
                $rollCounter = 0;

                foreach ($sorted as $app) {
                    $rollNumber = $startRoll + $rollCounter;

                    // Create student and related records
                    $this->migrateApplicationToStudent($app, $rollNumber, $admissionYear);

                    $rollCounter++;
                    $totalAdmitted++;
                }

                $summary[$groupName] = [
                    'count' => $rollCounter,
                    'roll_start' => $startRoll,
                    'roll_end' => $startRoll + $rollCounter - 1,
                ];
            }

            return [
                'total_admitted' => $totalAdmitted,
                'admission_year' => $admissionYear,
                'groups' => $summary,
            ];
        });
    }

    /**
     * Admit a single late applicant (after merit list was generated)
     * Gets the next available roll number for their group
     */
    public function admitLateApplicant(AdmissionApplication $application): array
    {
        return DB::transaction(function () use ($application) {
            // Classify the subject group
            $application->updateSubjectGroup();
            $application = $application->fresh();

            $group = $application->subject_group;

            // Find the max roll number currently assigned for this year and group
            $maxRoll = AdmissionApplication::where('admission_year', $application->admission_year)
                ->where('subject_group', $group)
                ->where('status', 'admitted')
                ->max('assigned_roll_no');

            // If no existing students in this group, use default start
            if (!$maxRoll) {
                $maxRoll = (self::DEFAULT_ROLL_RANGES[$group] ?? 901) - 1;
            }

            $rollNumber = $maxRoll + 1;

            $this->migrateApplicationToStudent($application, $rollNumber, $application->admission_year);

            return [
                'roll_number' => $rollNumber,
                'group' => $group,
            ];
        });
    }

    /**
     * Migrate a single application into the students/admissions tables
     */
    protected function migrateApplicationToStudent(AdmissionApplication $app, int $rollNumber, int $admissionYear): void
    {
        $userId = Auth::id();

        // Check for existing SSC record to avoid duplicate entry error
        $existingSscRecord = SscRecord::where('roll_no', $app->ssc_roll_no)
            ->where('reg_no', $app->ssc_reg_no)
            ->first();

        if ($existingSscRecord) {
            throw new \Exception("Student with SSC Roll {$app->ssc_roll_no} and Reg {$app->ssc_reg_no} is already admitted. Application #{$app->application_number} skipped.");
        }

        // 1. Create or find Student (by CNIC to avoid duplicates on retries)
        $studentKey = $app->cnic ? ['cnic' => $app->cnic] : ['id' => -1]; // fallback won't match
        $student = $app->cnic
            ? Student::updateOrCreate($studentKey, [
                'first_name' => $app->first_name,
                'last_name' => $app->last_name,
                'date_of_birth' => $app->date_of_birth,
                'gender' => $app->gender,
                'cnic' => $app->cnic,
                'picture' => $app->picture,
                'updated_by' => $userId,
            ])
            : Student::create([
                'first_name' => $app->first_name,
                'last_name' => $app->last_name,
                'date_of_birth' => $app->date_of_birth,
                'gender' => $app->gender,
                'cnic' => $app->cnic,
                'picture' => $app->picture,
                'created_by' => $userId,
            ]);

        if ($student->wasRecentlyCreated) {
            $student->created_by = $userId;
            $student->save();
        }

        // 2. Create or update Student Attribute
        StudentAttribute::updateOrCreate(
            ['student_id' => $student->id],
            [
                'blood_group_id' => $app->blood_group_id,
                'quota_id' => $app->quota_id,
                'religion' => $app->religion,
                'nationality' => $app->nationality,
                'domicile' => $app->domicile,
                'student_cnic' => $app->cnic,
                'updated_by' => $userId,
            ]
        );

        // 3. Create or update Father
        Father::updateOrCreate(
            ['student_id' => $student->id],
            [
                'name' => $app->father_name,
                'cnic' => $app->father_cnic,
                'contact' => $app->father_contact,
                'occupation' => $app->father_occupation,
                'annual_income' => $app->father_annual_income,
                'updated_by' => $userId,
            ]
        );

        // 4. Create or update Contact
        Contact::updateOrCreate(
            ['student_id' => $student->id],
            [
                'student_contact' => $app->student_contact,
                'whatsapp_no' => $app->whatsapp_no,
                'email' => $app->email,
                'address' => $app->address,
                'picture' => $app->picture,
                'updated_by' => $userId,
            ]
        );

        // 5. Create Admission (always new — one per application)
        $isInter = ($app->program_type === 'Inter');
        Admissions::create([
            'student_id' => $student->id,
            'admission_year' => $admissionYear,
            'academic_year' => $admissionYear,
            'college_roll_no' => (string) $rollNumber,
            'hssc_reg_no' => 'PENDING-' . $app->application_number,
            'current_class' => $isInter ? '11' : ($app->program_type === 'ADP' ? 'ADP-1' : ($app->program_type === 'DIT' ? 'DIT-1' : 'BS-1')),
            'program_type' => $app->program_type,
            'program_name' => $isInter ? 'FSc/FA' : $app->program_type,
            'status' => 'Active',
            'created_by' => $userId,
        ]);

        // 6. Create or update SSC Record
        SscRecord::updateOrCreate(
            ['student_id' => $student->id],
            [
                'bise_id' => $app->bise_id,
                'roll_no' => $app->ssc_roll_no,
                'reg_no' => $app->ssc_reg_no,
                'school_name' => $app->ssc_school_name,
                'total_mark' => $app->ssc_total_marks,
                'obtained_mark' => $app->ssc_obtained_marks,
                'passing_year' => $app->ssc_passing_year,
                'passing_session' => $app->ssc_passing_session,
                'ssc_group' => $app->ssc_group,
                'updated_by' => $userId,
            ]
        );

        // 7. Assign Elective Subjects (delete existing first to avoid duplication on retry)
        StudentElectiveSubject::where('student_id', $student->id)->delete();
        $electiveIds = $app->elective_subject_ids;
        foreach ($electiveIds as $sequence => $subjectId) {
            StudentElectiveSubject::create([
                'student_id' => $student->id,
                'subject_id' => $subjectId,
                'sequence' => $sequence + 1,
                'created_by' => $userId,
            ]);
        }

        // 8. Assign Compulsory Subjects (delete existing first to avoid duplication on retry)
        StudentCompulsorySubject::where('student_id', $student->id)->delete();
        $classLevel = $isInter ? '11' : ($app->program_type === 'ADP' ? 'ADP-1' : ($app->program_type === 'DIT' ? 'DIT-1' : 'BS-1'));
        $assignedSubjects = \App\Models\SubjectAssignmentRule::getSubjectsForStudent(
            $classLevel, 
            $app->nationality, 
            $app->religion, 
            'compulsory'
        );

        if ($assignedSubjects->isNotEmpty()) {
            foreach ($assignedSubjects as $subject) {
                StudentCompulsorySubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'class_level' => $classLevel,
                    'created_by' => $userId,
                ]);
            }
        } else {
            // Fallback just in case rules aren't seeded yet
            $compulsorySubjects = Subject::where('type', 'Compulsory')
                ->where('class_level', $classLevel)
                ->pluck('id');

            foreach ($compulsorySubjects as $subjectId) {
                StudentCompulsorySubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $subjectId,
                    'class_level' => $classLevel,
                    'created_by' => $userId,
                ]);
            }
        }

        // 9. Update application status
        $app->update([
            'status' => 'admitted',
            'assigned_roll_no' => (string) $rollNumber,
        ]);
    }

    /**
     * Preview merit list without actually creating records
     */
    public function preview(int $admissionYear, array $rollStartRanges = [], string $programType = 'Inter'): array
    {
        $rollStartRanges = array_merge(self::DEFAULT_ROLL_RANGES, $rollStartRanges);

        $applications = AdmissionApplication::where('admission_year', $admissionYear)
            ->where('program_type', $programType)
            ->where('status', 'fee_paid')
            ->with(['electiveSubject1', 'electiveSubject2', 'electiveSubject3', 'bise'])
            ->get();

        if ($applications->isEmpty()) {
            return ['total' => 0, 'groups' => []];
        }

        // Classify each
        foreach ($applications as $app) {
            $app->subject_group = $app->classifySubjectGroup();
        }

        $groups = $applications->groupBy('subject_group');
        $preview = [];

        foreach ($groups as $groupName => $groupApps) {
            $sorted = $groupApps->sortByDesc(fn($app) => $app->ssc_percentage)->values();
            $startRoll = $rollStartRanges[$groupName] ?? 901;

            $students = [];
            foreach ($sorted as $i => $app) {
                $students[] = [
                    'position' => $i + 1,
                    'roll_no' => $startRoll + $i,
                    'name' => $app->full_name,
                    'father_name' => $app->father_name,
                    'ssc_obtained' => $app->ssc_obtained_marks,
                    'ssc_total' => $app->ssc_total_marks,
                    'ssc_percentage' => $app->ssc_percentage,
                    'application_number' => $app->application_number,
                ];
            }

            $preview[$groupName] = [
                'count' => count($students),
                'roll_start' => $startRoll,
                'roll_end' => $startRoll + count($students) - 1,
                'students' => $students,
            ];
        }

        return [
            'total' => $applications->count(),
            'groups' => $preview,
        ];
    }
}
