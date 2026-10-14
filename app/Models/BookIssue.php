<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'issued_to_type', 'issued_to_id', 'issued_by',
        'issued_at', 'due_date', 'returned_at', 'return_accepted_by',
        'fine_amount', 'remarks', 'status',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_date' => 'date',
        'returned_at' => 'datetime',
        'fine_amount' => 'decimal:2',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returnAcceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_accepted_by');
    }

    /**
     * Polymorphic accessor: get the borrower (Student or Staff).
     */
    public function getIssuedToAttribute()
    {
        if ($this->issued_to_type === 'student') {
            return Student::find($this->issued_to_id);
        }
        return Staff::find($this->issued_to_id);
    }

    public function getBorrowerNameAttribute(): string
    {
        $entity = $this->issued_to;
        if (!$entity) return 'Unknown';
        return $entity->full_name ?? ($entity->first_name . ' ' . ($entity->last_name ?? ''));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'issued' && $this->due_date->isPast();
    }
}
