<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


use Illuminate\Notifications\Notifiable;

class Student extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    /**
     * The table associated with the model.
     */
    protected $table = 'students';

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
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'cnic',
        'picture',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
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

    public function attribute(): HasOne
    {
        return $this->hasOne(StudentAttribute::class, 'student_id', 'id');
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class, 'student_id', 'id');
    }

    public function father(): HasOne
    {
        return $this->hasOne(Father::class, 'student_id', 'id');
    }

    public function sscRecord(): HasOne
    {
        return $this->hasOne(SscRecord::class, 'student_id', 'id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(FeeVoucher::class, 'student_id', 'id');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admissions::class, 'student_id', 'id');
    }

    public function heAdmissions(): HasMany
    {
        return $this->hasMany(HeAdmission::class, 'student_id', 'id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'student_id', 'id');
    }

    public function electives(): HasMany
    {
        return $this->hasMany(StudentElectiveSubject::class, 'student_id', 'id');
    }

    public function compulsorySubjects(): HasMany
    {
        return $this->hasMany(StudentCompulsorySubject::class, 'student_id', 'id');
    }

    public function studentTeacherSubjects(): HasMany
    {
        return $this->hasMany(StudentTeacherSubject::class, 'student_id', 'id');
    }

    public function subjectSections(): HasMany
    {
        return $this->hasMany(StudentSubjectSection::class, 'student_id', 'id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'student_id', 'id');
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id', 'id');
    }

    public function graduationRecord(): HasOne
    {
        return $this->hasOne(GraduationRecord::class, 'student_id', 'id');
    }

    public function transport(): HasOne
    {
        return $this->hasOne(StudentTransport::class, 'student_id', 'id');
    }

    public function struckOffLogs(): HasMany
    {
        return $this->hasMany(StudentStruckOffLog::class, 'student_id', 'id');
    }

    public function fines(): HasMany
    {
        return $this->hasMany(StudentFine::class, 'student_id', 'id');
    }

    public function unpaidFines(): HasMany
    {
        return $this->hasMany(StudentFine::class, 'student_id', 'id')->whereIn('status', ['unpaid', 'partially_paid']);
    }

    public function totalOutstandingFine(): float
    {
        return (float) $this->unpaidFines()->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(total_amount, 0) - COALESCE(paid_amount, 0) - COALESCE(waived_amount, 0)'));
    }

    /**
     * Returns the active (not-yet-readmitted) struck-off log, if any.
     */
    public function activeStruckOffLog(): ?StudentStruckOffLog
    {
        return $this->struckOffLogs()->where('status', 'struck_off')->latest('struck_off_at')->first();
    }

    // Scopes
    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->where(function($q) use ($name) {
            $q->where('first_name', 'like', "%{$name}%")
              ->orWhere('last_name', 'like', "%{$name}%");
        });
    }

    public function scopeForAgeRange(Builder $query, int $minAge, int $maxAge): Builder
    {
        $startDate = now()->subYears($maxAge)->format('Y-m-d');
        $endDate = now()->subYears($minAge)->format('Y-m-d');
        
        return $query->whereBetween('date_of_birth', [$startDate, $endDate]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('admissions', function ($q) {
            $q->active();
        });
    }

    public function scopeStruckOff(Builder $query): Builder
    {
        return $query->whereHas('admissions', function ($q) {
            $q->struckOff();
        });
    }

    public function scopeInBatch(Builder $query, int $admissionYear): Builder
    {
        return $query->whereHas('admissions', function ($q) use ($admissionYear) {
            $q->where('admission_year', $admissionYear);
        });
    }

    public function scopeInClass(Builder $query, string $class): Builder
    {
        return $query->whereHas('admissions', function ($q) use ($class) {
            $q->where('current_class', $class);
        });
    }

    public function scopeWhereNotGraduated(Builder $query): Builder
    {
        return $query->whereHas('admissions', function ($q) {
            $q->notGraduated();
        });
    }

    public function scopeAssignedToTeacher(Builder $query, ?int $staffId, ?int $subjectId = null): Builder
    {
        if (!$staffId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('studentTeacherSubjects', function ($q) use ($staffId, $subjectId) {
            $q->where('staff_id', $staffId);
            if ($subjectId) {
                $q->where('subject_id', $subjectId);
            }
        });
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
              ->orWhereHas('father', function ($fq) use ($search) {
                  $fq->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('admissions', function ($subQ) use ($search) {
                  $subQ->where('college_roll_no', 'like', "%{$search}%")
                       ->orWhere('bise_roll_11', 'like', "%{$search}%")
                       ->orWhere('bise_roll_12', 'like', "%{$search}%");
              });
        });
    }

    public function scopeForClass(Builder $query, string $class): Builder
    {
        return $query->inClass($class);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereHas('admissions', function ($q) use ($year) {
            $q->where('academic_year', $year);
        });
    }

    public function scopeWithCnic(Builder $query): Builder
    {
        return $query->whereHas('attribute', function ($q) {
            $q->whereNotNull('student_cnic');
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    public function getFormattedDateOfBirthAttribute(): string
    {
        return $this->date_of_birth ? $this->date_of_birth->format('d-m-Y') : '';
    }

    public function getIsMinorAttribute(): bool
    {
        return $this->age < 18;
    }

    public function getIsAdultAttribute(): bool
    {
        return $this->age >= 18;
    }

    public function getIsCurrentStudentAttribute(): bool
    {
        return $this->admissions()->whereIn('status', ['Active', 'Admitted'])->exists()
            || $this->heAdmissions()->whereIn('status', ['Active', 'Admitted'])->exists();
    }

    public function getIsStruckOffAttribute(): bool
    {
        return $this->admissions()->whereIn('status', ['Struck Off', 'struck_off'])->exists()
            || $this->heAdmissions()->whereIn('status', ['Struck Off', 'struck_off'])->exists()
            || $this->struckOffLogs()->where('status', 'struck_off')->exists();
    }

    public function getIsGraduatedAttribute(): bool
    {
        return $this->admissions()->where('status', 'Graduated')->exists()
            || $this->heAdmissions()->where('status', 'Graduated')->exists();
    }

    public function getIsDroppedAttribute(): bool
    {
        return $this->admissions()->where('status', 'Dropped')->exists()
            || $this->heAdmissions()->where('status', 'Dropped')->exists();
    }

    public function getAdmissionNoAttribute(): string
    {
        $admission = $this->admissions->whereIn('status', ['Active', 'Admitted'])->first();
        if ($admission) {
            return $admission->college_roll_no ?? 'N/A';
        }
        $heAdmission = $this->heAdmissions->whereIn('status', ['Active', 'Admitted'])->first();
        if ($heAdmission) {
            return $heAdmission->roll_no ?? 'N/A';
        }
        return 'N/A';
    }

    public function getCurrentClassAttribute(): string
    {
        $admission = $this->admissions->whereIn('status', ['Active', 'Admitted'])->first();
        if ($admission) {
            return $admission->current_class ?? 'N/A';
        }
        $heAdmission = $this->heAdmissions->whereIn('status', ['Active', 'Admitted'])->first();
        if ($heAdmission) {
            return ($heAdmission->program->name ?? 'HE') . ' (Sem ' . $heAdmission->current_semester . ')';
        }
        return 'Not Enrolled';
    }

    public function getStudentCnicAttribute(): string
    {
        if (!empty($this->attributes['cnic'])) {
            return $this->attributes['cnic'];
        }
        if ($this->attribute && !empty($this->attribute->student_cnic)) {
            return $this->attribute->student_cnic;
        }
        if ($this->contact && !empty($this->contact->student_cnic)) {
            return $this->contact->student_cnic;
        }
        return '';
    }

    public function getBloodGroupAttribute(): string
    {
        return $this->attribute && $this->attribute->bloodGroup ? 
               $this->attribute->bloodGroup->code : '';
    }

    public function getQuotaAttribute(): string
    {
        return $this->attribute && $this->attribute->quota ? 
               $this->attribute->quota->name : '';
    }

    // Mutators
    public function setFirstNameAttribute($value): void
    {
        $this->attributes['first_name'] = trim(ucwords(strtolower($value)));
    }

    public function setLastNameAttribute($value): void
    {
        $this->attributes['last_name'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    public function setDateOfBirthAttribute($value): void
    {
        $this->attributes['date_of_birth'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->formatted_date_of_birth,
            'age' => $this->age,
            'is_minor' => $this->is_minor,
            'is_adult' => $this->is_adult,
            'is_current_student' => $this->is_current_student,
            'is_graduated' => $this->is_graduated,
            'is_dropped' => $this->is_dropped,
            'current_class' => $this->current_class,
            'cnic' => $this->student_cnic,
            'blood_group' => $this->blood_group,
            'quota' => $this->quota,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getAcademicProfile(): array
    {
        $admissions = $this->admissions;
        $results = $this->results;
        
        return [
            'total_admissions' => $admissions->count(),
            'active_admission' => $admissions->where('status', 'Active')->first(),
            'graduation_status' => $this->is_graduated,
            'total_results' => $results->count(),
            'average_percentage' => $results->avg('percentage'),
        ];
    }

    public function getContactInfo(): array
    {
        $contact = $this->contact;
        
        return [
            'email' => $contact ? $contact->email : null,
            'phone' => $contact ? $contact->student_contact : null,
            'whatsapp' => $contact ? $contact->whatsapp_no : null,
            'address' => $contact ? $contact->address : null,
        ];
    }

    public function getFatherInfo(): array
    {
        $father = $this->father;
        
        return [
            'name' => $father ? $father->name : null,
            'cnic' => $father ? $father->cnic : null,
            'contact' => $father ? $father->contact : null,
            'occupation' => $father ? $father->occupation : null,
            'annual_income' => $father ? $father->annual_income : null,
        ];
    }

    public function getSscInfo(): array
    {
        $ssc = $this->sscRecord;
        
        return [
            'roll_no' => $ssc ? $ssc->roll_no : null,
            'reg_no' => $ssc ? $ssc->reg_no : null,
            'school_name' => $ssc ? $ssc->school_name : null,
            'total_marks' => $ssc ? $ssc->total_mark : null,
            'obtained_marks' => $ssc ? $ssc->obtained_mark : null,
            'percentage' => $ssc ? $ssc->percentage : null,
            'grade' => $ssc ? $ssc->grade : null,
        ];
    }

    public function isEligibleForAdmission(): bool
    {
        return $this->age >= 15 && $this->age <= 25; // Assuming age criteria
    }

    public function canTakeFinalExams(): bool
    {
        return $this->is_current_student && $this->current_class === '12';
    }

    public function getStudentStatus(): string
    {
        if ($this->is_struck_off) return 'Struck Off';
        if ($this->is_graduated) return 'Graduated';
        if ($this->is_current_student) return 'Active';
        if ($this->is_dropped) return 'Dropped';
        return 'Not Enrolled';
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
        return asset('uploads/default.jpg');
    }

    /**
     * Classify an array of elective subject names into a recognized academic group.
     */
    public static function classifySubjectGroup(array $subjects): string
    {
        $subs = array_map('strtolower', $subjects);
        $has = fn($sub) => in_array(true, array_map(fn($s) => strpos($s, strtolower($sub)) !== false, $subs));

        if ($has('biology') && $has('physics') && $has('chemistry')) return 'Pre-Medical';
        if ($has('math') && $has('physics') && $has('chemistry')) return 'Pre-Engineering';
        if ($has('math') && $has('computer')) return 'ICS';
        if ($has('computer')) return 'FA-IT';
        return 'FA';
    }

    /**
     * Get the student's academic group based on their enrolled elective subjects.
     */
    public function getSubjectGroupAttribute(): string
    {
        $subjectNames = $this->electives->pluck('subject.name')->filter()->toArray();
        return !empty($subjectNames) ? static::classifySubjectGroup($subjectNames) : 'N/A';
    }

    /**
     * Check if the student has a pending subject change fee difference voucher.
     */
    public function hasPendingSubjectChangeVoucher(): bool
    {
        return $this->vouchers()
            ->where('status', 'pending')
            ->where('remarks', 'Subject Change Fee Difference')
            ->exists();
    }

    /**
     * Check if elective subjects can be modified for this student.
     */
    public function canChangeElectives(): bool
    {
        $latestAdmission = $this->admissions()->latest('admission_year')->first();
        if ($latestAdmission && $latestAdmission->current_class === '12') {
            return false;
        }

        return !$this->hasPendingSubjectChangeVoucher();
    }

    /**
     * Update elective subjects and synchronize orphaned teacher assignments.
     */
    public function updateElectives(array $subjectIds, ?int $userId = null): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($subjectIds, $userId) {
            // Delete existing electives
            $this->electives()->forceDelete();

            // Insert new ones with sequence
            foreach ($subjectIds as $sequence => $subjectId) {
                StudentElectiveSubject::create([
                    'student_id' => $this->id,
                    'subject_id' => $subjectId,
                    'sequence'   => $sequence + 1,
                    'created_by' => $userId,
                ]);
            }

            // Sync with Teacher Assignment
            $validSubjectIds = $this->compulsorySubjects()->pluck('subject_id')->merge($subjectIds)->unique();
            $this->studentTeacherSubjects()
                ->whereNotIn('subject_id', $validSubjectIds)
                ->delete();
        });
    }
}
