<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


use Illuminate\Notifications\Notifiable;

class Staff extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    /**
     * The table associated with the model.
     */
    protected $table = 'staff';

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
        'designation_id',
        'staff_id',
        'first_name',
        'last_name',
        'father_name',
        'cnic',
        'contact_number',
        'address',
        'date_of_birth',
        'blood_group_id',
        'picture',
        'whatsapp_number',
        'email',
        'category',
        'highest_qualification',
        'designation',
        'bps',
        'entry_date_govt_service',
        'hed_joining_date',
        'current_college_joining_date',
        'status',
        'gender',
        'first_promotion_date',
        'second_promotion_date',
        'third_promotion_date',
        'fourth_promotion_date',
        'join_current_bps_as',
        'signature_image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'entry_date_govt_service' => 'date',
        'hed_joining_date' => 'date',
        'current_college_joining_date' => 'date',
        'first_promotion_date' => 'date',
        'second_promotion_date' => 'date',
        'third_promotion_date' => 'date',
        'fourth_promotion_date' => 'date',
        'category' => 'string',
        'status' => 'string',
        'gender' => 'string',
        'join_current_bps_as' => 'string',
        'bps' => 'integer',
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
    public function bloodGroup(): BelongsTo
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'id');
    }

    public function designationModel(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'staff_id', 'id');
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

    public function academicQualifications(): HasMany
    {
        return $this->hasMany(StaffAcademicQualification::class, 'staff_id', 'id');
    }

    public function serviceHistory(): HasMany
    {
        return $this->hasMany(StaffServiceHistory::class, 'staff_id', 'id');
    }

    public function promotionHistory(): HasMany
    {
        return $this->hasMany(StaffPromotionHistory::class, 'staff_id', 'id');
    }

    public function subjectSpecializations(): HasMany
    {
        return $this->hasMany(StaffSubjectSpecialization::class, 'staff_id', 'id');
    }

    public function studentTeacherSubjects(): HasMany
    {
        return $this->hasMany(StudentTeacherSubject::class, 'staff_id', 'id');
    }

    public function teacherProfile(): HasMany
    {
        return $this->hasMany(TeacherProfile::class, 'staff_id', 'id');
    }

    public function heTeacherAssignments(): HasMany
    {
        return $this->hasMany(HeTeacherAssignment::class, 'staff_id', 'id');
    }

    /**
     * BS/ADP Subject Specializations (Higher Education)
     */
    public function bsSubjects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(BsSubject::class, 'staff_bs_subject_specializations', 'staff_id', 'bs_subject_id')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeForStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeForBps(Builder $query, int $bps): Builder
    {
        return $query->where('bps', $bps);
    }

    public function scopeForDesignation(Builder $query, string $designation): Builder
    {
        return $query->where('designation', $designation);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeTeaching(Builder $query): Builder
    {
        return $query->where('category', 'teaching');
    }

    public function scopeNonTeaching(Builder $query): Builder
    {
        return $query->where('category', 'non_teaching');
    }

    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->where(function($q) use ($name) {
            $q->where('first_name', 'like', "%{$name}%")
              ->orWhere('last_name', 'like', "%{$name}%")
              ->orWhere('father_name', 'like', "%{$name}%");
        });
    }

    public function scopeSearchByCnic(Builder $query, string $cnic): Builder
    {
        return $query->where('cnic', $cnic);
    }

    public function scopeSearchByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getFullDesignationAttribute(): string
    {
        return $this->designation . ' (BPS-' . $this->bps . ')';
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    public function getFormattedCnicAttribute(): string
    {
        $cnic = $this->cnic;
        if (strlen($cnic) === 13) {
            return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
        }
        return $cnic;
    }

    public function getIsTeachingStaffAttribute(): bool
    {
        return $this->category === 'teaching';
    }

    public function getIsNonTeachingStaffAttribute(): bool
    {
        return $this->category === 'non_teaching';
    }

    public function getIsRetiredAttribute(): bool
    {
        return $this->status === 'retired';
    }

    public function getIsOnDutyAttribute(): bool
    {
        return in_array($this->status, ['active', 'suspended']);
    }

    public function getIsFemaleAttribute(): bool
    {
        return $this->gender === 'female';
    }

    public function getIsMaleAttribute(): bool
    {
        return $this->gender === 'male';
    }

    public function getExperienceYearsAttribute(): int
    {
        if (!$this->current_college_joining_date) {
            return 0;
        }
        return $this->current_college_joining_date->diffInYears(now());
    }

    public function getPromotionCountAttribute(): int
    {
        $count = 0;
        if ($this->first_promotion_date) $count++;
        if ($this->second_promotion_date) $count++;
        if ($this->third_promotion_date) $count++;
        if ($this->fourth_promotion_date) $count++;
        return $count;
    }

    // Mutators
    public function setStaffIdAttribute($value): void
    {
        $this->attributes['staff_id'] = strtoupper(trim($value));
    }

    public function setCnicAttribute($value): void
    {
        $this->attributes['cnic'] = trim($value);
    }

    public function setContactNumberAttribute($value): void
    {
        $this->attributes['contact_number'] = $value ? trim($value) : null;
    }

    public function setWhatsappNumberAttribute($value): void
    {
        $this->attributes['whatsapp_number'] = $value ? trim($value) : null;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = $value ? strtolower(trim($value)) : null;
    }

    public function getDisplayEmailAttribute(): ?string
    {
        if (!empty($this->email) && !str_ends_with($this->email, '@college.edu')) {
            return $this->email;
        }
        if ($this->user && !empty($this->user->email) && !str_ends_with($this->user->email, '@college.edu')) {
            return $this->user->email;
        }
        return $this->email ?? ($this->user?->email);
    }

    public function setFirstNameAttribute($value): void
    {
        $this->attributes['first_name'] = trim(ucwords(strtolower($value)));
    }

    public function setLastNameAttribute($value): void
    {
        $this->attributes['last_name'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    public function setFatherNameAttribute($value): void
    {
        $this->attributes['father_name'] = trim(ucwords(strtolower($value)));
    }

    public function setDesignationAttribute($value): void
    {
        $this->attributes['designation'] = trim(ucwords(strtolower($value)));
    }

    // Custom methods
    public function hasValidContact(): bool
    {
        return !empty($this->contact_number) || !empty($this->email);
    }

    public function getContactInfo(): string
    {
        $contacts = [];
        if ($this->contact_number) {
            $contacts[] = $this->contact_number;
        }
        if ($this->email) {
            $contacts[] = $this->email;
        }
        return $contacts ? implode(' | ', $contacts) : 'No contact available';
    }

    public function getProfilePictureUrl(): string
    {
        if ($this->picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->picture)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->picture);
        }
        // Fallback for old path style (if any exist)
        if ($this->picture && file_exists(public_path($this->picture))) {
            return asset($this->picture);
        }
        // Trust the DB if it looks like a valid upload path (even if file_exists fails due to permissions/symlinks)
        if ($this->picture && strpos($this->picture, 'uploads/') === 0) {
             return asset($this->picture);
        }
        return asset('uploads/default.jpg');
    }

    public function getSignatureImageUrl(): string
    {
        if ($this->signature_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->signature_image)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->signature_image);
        }
        return '';
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'full_name' => $this->full_name,
            'father_name' => $this->father_name,
            'cnic' => $this->formatted_cnic,
            'contact_info' => $this->getContactInfo(),
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->age,
            'blood_group' => $this->bloodGroup ? $this->bloodGroup->code : null,
            'picture_url' => $this->getProfilePictureUrl(),
            'signature_image_url' => $this->getSignatureImageUrl(),
            'email' => $this->email,
            'category' => $this->category,
            'is_teaching_staff' => $this->is_teaching_staff,
            'highest_qualification' => $this->highest_qualification,
            'designation' => $this->designation,
            'full_designation' => $this->full_designation,
            'bps' => $this->bps,
            'entry_date_govt_service' => $this->entry_date_govt_service,
            'hed_joining_date' => $this->hed_joining_date,
            'current_college_joining_date' => $this->current_college_joining_date,
            'status' => $this->status,
            'is_on_duty' => $this->is_on_duty,
            'gender' => $this->gender,
            'experience_years' => $this->experience_years,
            'promotion_count' => $this->promotion_count,
            'join_current_bps_as' => $this->join_current_bps_as,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getQualificationSummary(): array
    {
        $qualifications = $this->academicQualifications()
                              ->orderBy('degree_level', 'desc')
                              ->get();
        
        return [
            'count' => $qualifications->count(),
            'highest' => $qualifications->first() ? $qualifications->first()->degree_level : null,
            'list' => $qualifications->pluck('degree_level')->toArray(),
        ];
    }

    public function getPromotionSummary(): array
    {
        $promotions = [
            'first' => $this->first_promotion_date,
            'second' => $this->second_promotion_date,
            'third' => $this->third_promotion_date,
            'fourth' => $this->fourth_promotion_date,
        ];
        
        return [
            'count' => $this->promotion_count,
            'dates' => array_filter($promotions),
        ];
    }

    public function isEligibleForPromotion(): bool
    {
        // Example logic: Teaching staff with 3+ years experience and BPS < 22
        return $this->is_teaching_staff && 
               $this->experience_years >= 3 && 
               $this->bps < 22 && 
               $this->status === 'active';
    }

    public function getYearsToRetirement(): int
    {
        if (!$this->date_of_birth) {
            return 0;
        }
        
        $retirementAge = 60; // Assuming standard retirement age
        $currentAge = $this->age;
        
        return $currentAge >= $retirementAge ? 0 : ($retirementAge - $currentAge);
    }

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function salarySlips()
    {
        return $this->hasMany(SalarySlip::class);
    }

    public function attendance()
    {
        return $this->hasMany(StaffDailyAttendance::class);
    }
}
