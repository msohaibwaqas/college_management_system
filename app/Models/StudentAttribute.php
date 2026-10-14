<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;



class StudentAttribute extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'student_attributes';

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
        'student_id',
        'blood_group_id',
        'quota_id',
        'religion',
        'nationality',
        'domicile',
        'student_cnic',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'student_cnic' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function bloodGroup(): BelongsTo
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'id');
    }

    public function quota(): BelongsTo
    {
        return $this->belongsTo(Quota::class, 'quota_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    // Scopes
    public function scopeForBloodGroup(Builder $query, int $bloodGroupId): Builder
    {
        return $query->where('blood_group_id', $bloodGroupId);
    }

    public function scopeForQuota(Builder $query, int $quotaId): Builder
    {
        return $query->where('quota_id', $quotaId);
    }

    public function scopeWithCnic(Builder $query): Builder
    {
        return $query->whereNotNull('student_cnic');
    }

    public function scopeWithoutCnic(Builder $query): Builder
    {
        return $query->whereNull('student_cnic');
    }

    public function scopeForBloodGroupCode(Builder $query, string $code): Builder
    {
        return $query->join('blood_groups', 'student_attributes.blood_group_id', '=', 'blood_groups.id')
                    ->where('blood_groups.code', $code)
                    ->select('student_attributes.*');
    }

    public function scopeForQuotaName(Builder $query, string $name): Builder
    {
        return $query->join('quotas', 'student_attributes.quota_id', '=', 'quotas.id')
                    ->where('quotas.name', $name)
                    ->select('student_attributes.*');
    }

    public function scopeSearchByCnic(Builder $query, string $cnic): Builder
    {
        return $query->where('student_cnic', 'like', "%{$cnic}%");
    }

    // Accessors
    public function getFormattedCnicAttribute(): string
    {
        $cnic = $this->student_cnic;
        if (strlen($cnic) === 13) {
            return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
        }
        return $cnic;
    }

    public function getBloodGroupCodeAttribute(): string
    {
        return $this->bloodGroup ? $this->bloodGroup->code : '';
    }

    public function getQuotaNameAttribute(): string
    {
        return $this->quota ? $this->quota->name : '';
    }

    public function getIsCnicValidAttribute(): bool
    {
        return $this->student_cnic && preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $this->student_cnic);
    }

    public function getIsSpecialQuotaAttribute(): bool
    {
        return $this->quota && in_array(strtolower($this->quota->name), [
            'hafiz e quran',
            'sports',
            'minority',
            'disable',
            'naat khawan'
        ]);
    }

    public function getStudentNameAttribute(): string
    {
        return $this->student ? $this->student->full_name : 'Unknown Student';
    }

    public function getStudentAgeAttribute(): int
    {
        return $this->student ? $this->student->age : 0;
    }

    public function getIsMinorAttribute(): bool
    {
        return $this->student_age < 18;
    }

    // Mutators
    public function setStudentCnicAttribute($value): void
    {
        $this->attributes['student_cnic'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    // Custom methods
    public function hasValidCnic(): bool
    {
        return $this->is_cnic_valid;
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->student_name,
            'student_age' => $this->student_age,
            'is_minor' => $this->is_minor,
            'cnic' => $this->formatted_cnic,
            'is_cnic_valid' => $this->is_cnic_valid,
            'blood_group' => $this->blood_group_code,
            'quota' => $this->quota_name,
            'is_special_quota' => $this->is_special_quota,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getMedicalInfo(): array
    {
        return [
            'blood_group' => $this->blood_group_code,
            'is_special_quota' => $this->is_special_quota,
            'quota' => $this->quota_name,
        ];
    }

    public function getIdentityInfo(): array
    {
        return [
            'cnic' => $this->formatted_cnic,
            'is_cnic_valid' => $this->is_cnic_valid,
            'has_cnic' => !empty($this->student_cnic),
        ];
    }

    public function getQuotaBenefits(): string
    {
        $quotaName = $this->quota_name;
        
        $benefits = [
            'Hafiz e Quran' => 'Religious scholarship and benefits',
            'Sports' => 'Sports facilities and coaching',
            'Minority' => 'Reservation and support programs',
            'Disable' => 'Accessibility and support services',
            'Naat Khawan' => 'Religious recitation opportunities',
        ];
        
        return $benefits[$quotaName] ?? 'Standard benefits apply';
    }

    public function canAccessSpecialBenefits(): bool
    {
        return $this->is_special_quota;
    }

    public function getProfileSummary(): array
    {
        return [
            'identity' => $this->getIdentityInfo(),
            'medical' => $this->getMedicalInfo(),
            'quota' => $this->getQuotaBenefits(),
            'can_access_special_benefits' => $this->can_access_special_benefits(),
        ];
    }

    public function getFormattedForReport(): array
    {
        return [
            'Student Name' => $this->student_name,
            'CNIC' => $this->formatted_cnic,
            'Blood Group' => $this->blood_group_code,
            'Quota' => $this->quota_name,
            'Special Quota' => $this->is_special_quota ? 'Yes' : 'No',
            'Minor' => $this->is_minor ? 'Yes' : 'No',
        ];
    }
}
