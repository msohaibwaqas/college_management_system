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
        return $query->join('admissions', 'students.id', '=', 'admissions.student_id')
                    ->where('admissions.status', 'Active')
                    ->select('students.*');
    }

    public function scopeForClass(Builder $query, string $class): Builder
    {
        return $query->join('admissions', 'students.id', '=', 'admissions.student_id')
                    ->where('admissions.current_class', $class)
                    ->select('students.*');
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->join('admissions', 'students.id', '=', 'admissions.student_id')
                    ->where('admissions.academic_year', $year)
                    ->select('students.*');
    }

    public function scopeWithCnic(Builder $query): Builder
    {
        return $query->join('student_attributes', 'students.id', '=', 'student_attributes.student_id')
                    ->whereNotNull('student_attributes.student_cnic')
                    ->select('students.*');
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
        return $this->admissions()->whereIn('status', ['Active', 'Admitted'])->exists();
    }

    public function getIsGraduatedAttribute(): bool
    {
        return $this->admissions()->where('status', 'Graduated')->exists();
    }

    public function getIsDroppedAttribute(): bool
    {
        return $this->admissions()->where('status', 'Dropped')->exists();
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
        return $this->attribute && $this->attribute->student_cnic ? $this->attribute->student_cnic : '';
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
}
