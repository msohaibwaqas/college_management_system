<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;

// // Get FQCNs
// $allModels = ModelResolver::all();
// print_r($allModels);

// // Instantiate all models
// $instances = ModelResolver::instances();

class Quota extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'quotas';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'name' => 'string',
    ];

    // Relationships
    public function studentAttributes(): HasMany
    {
        return $this->hasMany(StudentAttribute::class, 'quota_id', 'id');
    }

    // Scopes
    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    // Accessors
    public function getFormattedNameAttribute(): string
    {
        return str_replace(['_', '-'], ' ', ucwords(strtolower($this->name)));
    }

    public function getIsSpecialQuotaAttribute(): bool
    {
        return in_array(strtolower($this->name), [
            'hafiz e quran',
            'sports',
            'disable',
            'minority'
        ]);
    }

    // Mutators
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = strtolower(trim($value));
    }

    // Custom methods
    public function getStudentCount(): int
    {
        return $this->studentAttributes()->count();
    }

    public function getStudentCountByYear(int $year): int
    {
        return $this->studentAttributes()
                    ->join('students', 'student_attributes.student_id', '=', 'students.id')
                    ->join('admissions', 'admissions.student_id', '=', 'students.id')
                    ->where('admissions.admission_year', $year)
                    ->count();
    }

    public function getBeneficiaries(int $limit = null): array
    {
        $query = $this->studentAttributes()->with('student');
        if ($limit) {
            $query = $query->limit($limit);
        }
        
        return $query->get()->toArray();
    }

    public function getQuotaBenefits(): string
    {
        $benefits = [
            'Hafiz e Quran' => 'Religious scholarship benefits',
            'Sports' => 'Sports facilities and coaching',
            'Minority' => 'Reservation and support programs',
            'Disable' => 'Accessibility and support services',
            'Naat Khawan' => 'Religious recitation opportunities',
        ];

        return $benefits[$this->formatted_name] ?? 'Special benefits available';
    }

    public function getQuotaCode(): string
    {
        return substr(strtoupper(str_replace(' ', '', $this->name)), 0, 3);
    }
}
