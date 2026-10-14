<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class Father extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'fathers';

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
        'name',
        'cnic',
        'contact',
        'occupation',
        'annual_income',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'name' => 'string',
        'cnic' => 'string',
        'contact' => 'string',
        'occupation' => 'string',
        'annual_income' => 'integer',
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
    public function scopeHasContact(Builder $query): Builder
    {
        return $query->whereNotNull('contact')->where('contact', '!=', '');
    }

    public function scopeHasCnic(Builder $query): Builder
    {
        return $query->whereNotNull('cnic')->where('cnic', '!=', '');
    }

    public function scopeHasIncome(Builder $query): Builder
    {
        return $query->whereNotNull('annual_income')->where('annual_income', '>', 0);
    }

    public function scopeForIncomeRange(Builder $query, int $min, int $max): Builder
    {
        return $query->whereBetween('annual_income', [$min, $max]);
    }

    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    // Accessors
    public function getFormattedNameAttribute(): string
    {
        return ucwords(strtolower($this->name));
    }

    public function getIncomeRangeAttribute(): string
    {
        if ($this->annual_income === null) {
            return 'Income not specified';
        }
        
        if ($this->annual_income < 300000) {
            return 'Low Income';
        } elseif ($this->annual_income < 600000) {
            return 'Middle Income';
        } elseif ($this->annual_income < 1000000) {
            return 'Upper Middle Income';
        } else {
            return 'High Income';
        }
    }

    public function getIsCnicValidAttribute(): bool
    {
        return $this->cnic && preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $this->cnic);
    }

    // Mutators
    public function setCnicAttribute($value): void
    {
        $this->attributes['cnic'] = $value ? trim($value) : null;
    }

    public function setContactAttribute($value): void
    {
        $this->attributes['contact'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setAnnualIncomeAttribute($value): void
    {
        $this->attributes['annual_income'] = $value ? (int)$value : null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = trim($value);
    }

    // Custom methods
    public function hasValidContact(): bool
    {
        return !empty($this->contact) && strlen($this->contact) >= 10;
    }

    public function hasValidCnic(): bool
    {
        return $this->isCnicValid;
    }

    public function getContactInfo(): string
    {
        if ($this->hasValidContact()) {
            return $this->contact;
        }
        return 'Contact not available';
    }

    public function getIncomeStatus(): string
    {
        if ($this->annual_income === null) {
            return 'Income information not provided';
        }
        
        $income = number_format($this->annual_income);
        return "Annual Income: Rs. {$income}";
    }

    public function getFullDetails(): array
    {
        return [
            'name' => $this->formatted_name,
            'cnic' => $this->cnic,
            'contact' => $this->getContactInfo(),
            'occupation' => $this->occupation,
            'income' => $this->getIncomeStatus(),
            'income_range' => $this->income_range,
        ];
    }
}
