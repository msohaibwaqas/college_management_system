<?php

namespace App\Services;

use App\Models\FeeHead;
use App\Models\FeeVoucher;
use App\Models\AdmissionApplication;
use App\Models\InstituteSetting;
use App\Models\Subject;

class FeeCalculationService
{
    /**
     * Calculate potential voucher items based on a list of subject IDs, program, and gender.
     */
    public function calculateItemsForSubjects(array $subjectIds, string $program = 'Inter', string $gender = 'All', string $group = 'All'): \Illuminate\Support\Collection
    {
        $allFees = FeeHead::general()->forProgram($program)->forGender($gender)->forGroup($group)->get();
        
        // Deduplicate by name, preferring specific groups over 'All'
        $baseFees = $allFees->groupBy('name')->map(function ($heads) use ($group) {
            if ($heads->count() > 1) {
                $specific = $heads->firstWhere('applicable_group', $group);
                if ($specific) return $specific;
                return $heads->whereIn('applicable_group', ['All', null])->first();
            }
            return $heads->first();
        })->values();
        $computerFund = FeeHead::computerFund()->forProgram($program)->forGender($gender)->first();
        $practicalFund = FeeHead::practicalBased()->forProgram($program)->forGender($gender)->first();

        $voucherItems = collect();

        foreach ($baseFees as $fee) {
            $voucherItems->push([
                'fee_head_id' => $fee->id,
                'name' => $fee->name,
                'amount' => (float)$fee->amount,
                'is_refundable' => $fee->is_refundable,
            ]);
        }

        $hasComputer = false;
        $practicalSubjectCount = 0;

        $subjectIds = collect($subjectIds)->filter()->unique();

        if ($subjectIds->isNotEmpty()) {
            if ($program === 'Inter') {
                $subjects = Subject::whereIn('id', $subjectIds)->get();
                foreach ($subjects as $subject) {
                    if (stripos($subject->name, 'Computer') !== false) {
                        $hasComputer = true;
                    } else if ($subject->practical_12 > 0) {
                        $practicalSubjectCount++;
                    }
                }
            } else {
                // HE Students
                $subjects = \App\Models\BsSubject::whereIn('id', $subjectIds)->get();
                foreach ($subjects as $subject) {
                    if (stripos($subject->name, 'Computer') !== false) {
                        $hasComputer = true;
                    } else if ($subject->practical_credit_hours > 0) {
                        $practicalSubjectCount++;
                    }
                }
            }
        }

        if ($hasComputer && $computerFund) {
            $voucherItems->push([
                'fee_head_id' => $computerFund->id,
                'name' => $computerFund->name,
                'amount' => (float)$computerFund->amount,
                'is_refundable' => $computerFund->is_refundable,
            ]);
        }

        if ($practicalSubjectCount > 0 && $practicalFund) {
            $totalPracticalAmount = (float)$practicalFund->amount * $practicalSubjectCount;
            $voucherItems->push([
                'fee_head_id' => $practicalFund->id,
                'name' => $practicalFund->name . " ({$practicalSubjectCount} Subjects)",
                'amount' => (float)$totalPracticalAmount,
                'is_refundable' => $practicalFund->is_refundable,
            ]);
        }

        return $voucherItems;
    }

    /**
     * Calculate yearly-only voucher items for Class 12 students, program, and gender.
     * Excludes one-time fees (already paid at admission).
     */
    public function calculateYearlyItemsForSubjects(array $subjectIds, string $program = 'Inter', string $gender = 'All', string $group = 'All'): \Illuminate\Support\Collection
    {
        $allFees = FeeHead::general()->yearly()->forProgram($program)->forGender($gender)->forGroup($group)->get();
        
        // Deduplicate by name, preferring specific groups over 'All'
        $baseFees = $allFees->groupBy('name')->map(function ($heads) use ($group) {
            if ($heads->count() > 1) {
                $specific = $heads->firstWhere('applicable_group', $group);
                if ($specific) return $specific;
                return $heads->whereIn('applicable_group', ['All', null])->first();
            }
            return $heads->first();
        })->values();
        $computerFund = FeeHead::computerFund()->yearly()->forProgram($program)->forGender($gender)->first();
        $practicalFund = FeeHead::practicalBased()->yearly()->forProgram($program)->forGender($gender)->first();

        $voucherItems = collect();

        foreach ($baseFees as $fee) {
            $voucherItems->push([
                'fee_head_id' => $fee->id,
                'name' => $fee->name,
                'amount' => (float)$fee->amount,
                'is_refundable' => $fee->is_refundable,
            ]);
        }

        $hasComputer = false;
        $practicalSubjectCount = 0;
        $subjectIds = collect($subjectIds)->filter()->unique();

        if ($subjectIds->isNotEmpty()) {
            if ($program === 'Inter') {
                $subjects = Subject::whereIn('id', $subjectIds)->get();
                foreach ($subjects as $subject) {
                    if (stripos($subject->name, 'Computer') !== false) {
                        $hasComputer = true;
                    }
                    if ($subject->practical_12 > 0) {
                        $practicalSubjectCount++;
                    }
                }
            } else {
                $subjects = \App\Models\BsSubject::whereIn('id', $subjectIds)->get();
                foreach ($subjects as $subject) {
                    if (stripos($subject->name, 'Computer') !== false) {
                        $hasComputer = true;
                    }
                    if ($subject->practical_credit_hours > 0) {
                        $practicalSubjectCount++;
                    }
                }
            }
        }

        if ($hasComputer && $computerFund) {
            $voucherItems->push([
                'fee_head_id' => $computerFund->id,
                'name' => $computerFund->name,
                'amount' => (float)$computerFund->amount,
                'is_refundable' => $computerFund->is_refundable,
            ]);
        }

        if ($practicalSubjectCount > 0 && $practicalFund) {
            $totalPracticalAmount = (float)$practicalFund->amount * $practicalSubjectCount;
            $voucherItems->push([
                'fee_head_id' => $practicalFund->id,
                'name' => $practicalFund->name . " ({$practicalSubjectCount} Subjects)",
                'amount' => (float)$totalPracticalAmount,
                'is_refundable' => $practicalFund->is_refundable,
            ]);
        }

        return $voucherItems;
    }

    /**
     * Calculate potential voucher items for an HE admission.
     */
    public function calculateVoucherItemsForHE(\App\Models\HeAdmission $admission): \Illuminate\Support\Collection
    {
        $program = $admission->program->type; // BS or ADP
        $gender = $admission->student->gender ?? 'All';
        $subjectIds = $admission->subjects->pluck('bs_subject_id')->unique()->toArray();
        $backgroundGroup = $admission->student->hsscRecord->group ?? 'All';

        return $this->calculateItemsForSubjects($subjectIds, $program, $gender, $backgroundGroup);
    }

    /**
     * Calculate potential voucher items for an admission application.
     * For ADP/BS applications no elective subjects are selected, so only general
     * base fees (filtered by program and gender) are included.
     */
    public function calculateVoucherItems(AdmissionApplication $application): \Illuminate\Support\Collection
    {
        $programType = $application->program_type ?? 'Inter';
        $gender = $application->gender ?? 'All';

        if (in_array($programType, ['ADP', 'BS'])) {
            // No elective subjects for HE programs — use background history for fees
            $backgroundGroup = $application->hssc_group ?? 'All';
            return $this->calculateItemsForSubjects([], $programType, $gender, $backgroundGroup);
        }

        // Inter: use elected subjects and subject group
        $subjectIds = [
            $application->elective_subject_1,
            $application->elective_subject_2,
            $application->elective_subject_3,
        ];
        
        $subjectGroup = $application->subject_group ?? 'All';

        return $this->calculateItemsForSubjects($subjectIds, 'Inter', $gender, $subjectGroup);
    }

    /**
     * Calculate fee difference for a subject change.
     */
    public function calculateDifference(\App\Models\Student $student, array $newSubjectIds): array
    {
        $currentSubjectIds = $student->electives()->pluck('subject_id')->toArray();
        
        $currentItems = $this->calculateItemsForSubjects($currentSubjectIds);
        $newItems = $this->calculateItemsForSubjects($newSubjectIds);

        $currentTotal = $currentItems->sum('amount');
        $newTotal = $newItems->sum('amount');

        $difference = $newTotal - $currentTotal;

        // Identify which heads are extra
        $extraItems = collect();
        if ($difference > 0) {
            foreach ($newItems as $newItem) {
                $currentItem = $currentItems->firstWhere('fee_head_id', $newItem['fee_head_id']);
                
                if (!$currentItem) {
                    $extraItems->push($newItem);
                } elseif ($newItem['amount'] > $currentItem['amount']) {
                    $itemDiff = $newItem['amount'] - $currentItem['amount'];
                    $extraItems->push([
                        'fee_head_id' => $newItem['fee_head_id'],
                        'name' => $newItem['name'] . " (Difference)",
                        'amount' => (float)$itemDiff,
                        'is_refundable' => $newItem['is_refundable'],
                    ]);
                }
            }
        }

        return [
            'difference' => (float)$difference,
            'extra_items' => $extraItems,
            'current_total' => (float)$currentTotal,
            'new_total' => (float)$newTotal
        ];
    }

    /**
     * Create the voucher from a collection of items (allows for adjustments).
     */
    public function createVoucher($target, \Illuminate\Support\Collection $items): FeeVoucher
    {
        $totalAmount = $items->sum('amount');
        $settings = InstituteSetting::getSettings();

        // Target can be AdmissionApplication or Student
        $applicationId = null;
        $studentId = null;
        $name = '';
        $fatherName = '';

        if ($target instanceof AdmissionApplication) {
            $applicationId = $target->id;
            $name = $target->full_name;
            $fatherName = $target->father_name;
        } elseif ($target instanceof \App\Models\Student) {
            $studentId = $target->id;
            $name = $target->full_name;
            $fatherName = $target->father ? $target->father->name : '';
            
            // Try to find the latest application if possible
            $latestApp = AdmissionApplication::where('ssc_roll_no', $target->sscRecord->roll_no ?? '')
                ->where('ssc_reg_no', $target->sscRecord->reg_no ?? '')
                ->orderBy('created_at', 'desc')
                ->first();
            if ($latestApp) {
                $applicationId = $latestApp->id;
            }
        }

        $voucher = FeeVoucher::create([
            'voucher_number' => FeeVoucher::generateVoucherNumber(),
            'application_id' => $applicationId,
            'student_id' => $studentId,
            'applicant_name' => $name,
            'father_name' => $fatherName,
            'amount' => $totalAmount,
            'paid_amount' => 0,
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'status' => 'pending',
            'bank_name' => $settings->bank_name ?? '',
            'account_number' => $settings->bank_account_number ?? '',
            'bank_branch' => $settings->bank_branch ?? '',
            'issued_by' => auth()->id(),
            'remarks' => $studentId ? 'Subject Change Fee Difference' : 'Initial Admission Fee',
        ]);

        foreach ($items as $item) {
            $voucher->items()->create([
                'fee_head_id' => $item['fee_head_id'] ?? null,
                'name' => $item['name'],
                'amount' => $item['amount'],
                'is_refundable' => $item['is_refundable'] ?? false,
            ]);
        }

        return $voucher;
    }

    /**
     * Legacy method for quick generation without adjustment.
     */
    public function generateVoucherForApplication(AdmissionApplication $application): FeeVoucher
    {
        $items = $this->calculateVoucherItems($application);
        return $this->createVoucher($application, $items);
    }
}
