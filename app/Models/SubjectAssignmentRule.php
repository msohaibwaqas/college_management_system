<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectAssignmentRule extends Model
{
    protected $table = 'subject_assignment_rules';
    protected $guarded = [];

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'rule_subjects', 'rule_id', 'subject_id')
                    ->withTimestamps();
    }

    /**
     * Get subjects based on characteristics.
     */
    public static function getSubjectsForStudent($classLevel, $nationality, $religion, $assignmentType = 'compulsory')
    {
        $nationalityType = strtolower($nationality) === 'pakistani' ? 'pakistani' : 'non_pakistani';
        // Treat Islam/Muslim variations mapping to 'muslim'
        $religionType = in_array(strtolower($religion), ['islam', 'muslim']) ? 'muslim' : 'non_muslim';

        // Find rule that completely matches the student's demographics
        $rule = self::where('assignment_type', $assignmentType)
            ->where(function ($query) use ($classLevel) {
                $query->where('class_level', $classLevel)
                      ->orWhereNull('class_level')
                      ->orWhere('class_level', 'any')
                      ->orWhere('class_level', '');
            })
            ->where(function ($query) use ($nationalityType) {
                $query->where('nationality_type', $nationalityType)
                    ->orWhere('nationality_type', 'any');
            })
            ->where(function ($query) use ($religionType) {
                $query->where('religion_type', $religionType)
                    ->orWhere('religion_type', 'any');
            })
            ->with('subjects')
            ->orderByRaw("
                (nationality_type != 'any') + 
                (religion_type != 'any') + 
                (class_level IS NOT NULL AND class_level != 'any' AND class_level != '') 
                DESC
            ") // Order by specificity to get the most specific rule first
            ->first();

        if ($rule) {
            return $rule->subjects;
        }

        return collect([]);
    }
}
