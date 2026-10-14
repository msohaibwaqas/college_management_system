<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'title_urdu', 'ref_no', 'language', 'author_id',
        'book_type', 'genre_id', 'isbn', 'pages', 'price', 'acc_no',
        'location_id', 'volumes', 'publication_year', 'remarks', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'pages' => 'integer',
        'volumes' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(BookLocation::class, 'location_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(BookIssue::class);
    }

    public function activeIssues(): HasMany
    {
        return $this->hasMany(BookIssue::class)->where('status', 'issued');
    }

    public function getAvailableCopiesAttribute(): int
    {
        return max(0, $this->volumes - $this->activeIssues()->count());
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->available_copies > 0;
    }
}
