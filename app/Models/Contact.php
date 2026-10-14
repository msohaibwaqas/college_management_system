<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class Contact extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'contacts';

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
        'student_contact',
        'whatsapp_no',
        'email',
        'address',
        'picture',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'address' => 'string',
        'picture' => 'string',
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
    public function scopeHasEmail(Builder $query): Builder
    {
        return $query->whereNotNull('email')->where('email', '!=', '');
    }

    public function scopeHasContact(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNotNull('student_contact')
              ->orWhereNotNull('whatsapp_no');
        });
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeSearchByContact(Builder $query, string $search): Builder
    {
        return $query->where(function($q) use ($search) {
            $q->where('student_contact', 'like', "%{$search}%")
              ->orWhere('whatsapp_no', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        return $this->address ?? 'Address not provided';
    }

    public function getIsEmailValidatedAttribute(): bool
    {
        return !empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }

    public function getPrimaryContactAttribute(): string
    {
        return $this->student_contact ?? $this->whatsapp_no ?? $this->email ?? 'No contact available';
    }

    // Mutators
    public function setStudentContactAttribute($value): void
    {
        $this->attributes['student_contact'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setWhatsappNoAttribute($value): void
    {
        $this->attributes['whatsapp_no'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = $value ? strtolower(trim($value)) : null;
    }

    public function setAddressAttribute($value): void
    {
        $this->attributes['address'] = $value ? trim($value) : null;
    }

    // Custom methods
    public function hasValidContact(): bool
    {
        return !empty($this->student_contact) || 
               !empty($this->whatsapp_no) || 
               $this->isEmailValidated;
    }

    public function getContactMethods(): array
    {
        $methods = [];
        if ($this->student_contact) {
            $methods[] = 'Phone';
        }
        if ($this->whatsapp_no) {
            $methods[] = 'WhatsApp';
        }
        if ($this->email && $this->isEmailValidated) {
            $methods[] = 'Email';
        }
        return $methods;
    }

    public function getFormattedContact(): string
    {
        $contacts = [];
        if ($this->student_contact) {
            $contacts[] = "Phone: {$this->student_contact}";
        }
        if ($this->whatsapp_no) {
            $contacts[] = "WhatsApp: {$this->whatsapp_no}";
        }
        if ($this->email && $this->isEmailValidated) {
            $contacts[] = "Email: {$this->email}";
        }
        return implode(' | ', $contacts) ?: 'No contact methods available';
    }

    public function getProfilePictureUrl(): string
    {
        if ($this->picture && file_exists(public_path('storage/' . $this->picture))) {
            return asset('storage/' . $this->picture);
        }
        return asset('uploads/default.jpg');
    }
}
