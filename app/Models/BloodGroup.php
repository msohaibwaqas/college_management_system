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

class BloodGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'blood_groups';

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
        'code',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'code' => 'string',
    ];

    // Relationships
    public function studentAttribute(): HasMany
    {
        return $this->hasMany(StudentAttribute::class, 'blood_group_id', 'id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'blood_group_id', 'id');
    }

    // Scopes
    public function scopeSearchByCode(Builder $query, string $search): Builder
    {
        return $query->where('code', 'like', "%{$search}%");
    }

    // Accessors
    public function getIsCompatibleWithAttribute(): callable
    {
        return function (string $otherBloodGroup) {
            $compatibility = [
                'A+' => ['A+', 'AB+'],
                'A-' => ['A+', 'A-', 'AB+', 'AB-'],
                'B+' => ['B+', 'AB+'],
                'B-' => ['B+', 'B-', 'AB+', 'AB-'],
                'AB+' => ['AB+'],
                'AB-' => ['AB+', 'AB-'],
                'O+' => ['O+', 'A+', 'B+', 'AB+'],
                'O-' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            ];

            return in_array($otherBloodGroup, $compatibility[$this->code] ?? []);
        };
    }

    // Custom methods
    public function getRhFactorAttribute(): string
    {
        return strpos($this->code, '+') !== false ? 'Positive' : 'Negative';
    }

    public function getMainTypeAttribute(): string
    {
        return preg_replace('/[+-]/', '', $this->code);
    }
}
