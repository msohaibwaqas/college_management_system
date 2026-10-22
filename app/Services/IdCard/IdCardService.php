<?php

namespace App\Services\IdCard;

use App\DTOs\IdCardItemDto;
use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\InstituteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class IdCardService
{
    /**
     * Map a regular HSSC Admissions model to IdCardItemDto.
     */
    public function fromHsscAdmission(Admissions $admission): IdCardItemDto
    {
        $student = $admission->student;
        $studentId = (int) $admission->student_id;

        $electives = $student?->electives ? $student->electives->sortBy('sequence') : collect();
        $courseTitle = $electives->isNotEmpty()
            ? $electives->map(fn($e) => $e->subject?->name)->filter()->implode(', ')
            : (($admission->current_class == '11') ? 'HSSC Part-I' : (($admission->current_class == '12') ? 'HSSC Part-II' : ($admission->current_class ?? 'N/A')));

        $admissionYear = (int) ($admission->admission_year ?: date('Y'));
        $expiryYear = $admissionYear + 2;

        return new IdCardItemDto(
            studentId: $studentId,
            fullName: trim(($student?->first_name ?? 'Unknown') . ' ' . ($student?->last_name ?? '')),
            fatherName: $student?->father?->name,
            rollNo: $admission->college_roll_no ?: 'N/A',
            courseTitle: $courseTitle ?: 'HSSC',
            photoUrl: $student ? $student->getProfilePictureUrl() : asset('images/default-avatar.png'),
            admissionYear: $admissionYear,
            expiryText: "AUG {$expiryYear}",
            barcodeValue: str_pad((string) $studentId, 6, '0', STR_PAD_LEFT),
            type: 'HSSC'
        );
    }

    /**
     * Map a Higher Education (BS/ADP) HeAdmission model to IdCardItemDto.
     */
    public function fromHeAdmission(HeAdmission $admission): IdCardItemDto
    {
        $student = $admission->student;
        $studentId = (int) $admission->student_id;

        $programName = $admission->program?->name ?? 'BS Program';
        $semester = $admission->current_semester ?: 1;
        $courseTitle = "{$programName} (Sem {$semester})";

        $admissionYear = (int) ($admission->admission_year ?: date('Y'));
        $duration = (int) ($admission->program?->duration_years ?: 4);
        $expiryYear = $admissionYear + $duration;

        return new IdCardItemDto(
            studentId: $studentId,
            fullName: trim(($student?->first_name ?? 'Unknown') . ' ' . ($student?->last_name ?? '')),
            fatherName: $student?->father?->name,
            rollNo: $admission->roll_no ?: 'N/A',
            courseTitle: $courseTitle,
            photoUrl: $student ? $student->getProfilePictureUrl() : asset('images/default-avatar.png'),
            admissionYear: $admissionYear,
            expiryText: "AUG {$expiryYear}",
            barcodeValue: str_pad((string) $studentId, 6, '0', STR_PAD_LEFT),
            type: 'HE'
        );
    }

    /**
     * Generate collection of IdCardItemDto based on request filters or explicit student IDs.
     *
     * @return Collection<IdCardItemDto>
     */
    public function generateCards(Request $request): Collection
    {
        if ($request->filled('student_ids')) {
            $ids = array_values(array_filter(explode(',', $request->student_ids)));

            if (!empty($ids)) {
                // 1. Fetch regular HSSC admissions
                $regularStudents = Admissions::with(['student.father', 'student.contact', 'student.electives.subject'])
                    ->whereIn('student_id', $ids)
                    ->get();

                // 2. Fetch HE admissions for students not in regular admissions
                $foundStudentIds = $regularStudents->pluck('student_id')->toArray();
                $missingIds = array_diff($ids, $foundStudentIds);

                $heStudents = collect();
                if (!empty($missingIds)) {
                    $heStudents = HeAdmission::with(['student.father', 'student.contact', 'program'])
                        ->whereIn('student_id', $missingIds)
                        ->get();
                }

                $hsscCards = $regularStudents->map(fn($adm) => $this->fromHsscAdmission($adm));
                $heCards = $heStudents->map(fn($adm) => $this->fromHeAdmission($adm));
                $allCards = $hsscCards->concat($heCards);

                // Preserve exact URL ordering
                $orderMap = array_flip($ids);
                return $allCards->sortBy(fn($card) => $orderMap[$card->studentId] ?? 999999)->values();
            }
        }

        // Default query by class / active
        $query = Admissions::with(['student.father', 'student.contact', 'student.electives.subject'])
            ->whereIn('status', ['Active', 'Admitted', 'active', 'admitted']);

        if ($request->filled('class')) {
            $query->where('current_class', $request->class);
        }

        return $query->get()->map(fn($adm) => $this->fromHsscAdmission($adm));
    }
}
