<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;

// // Get FQCNs
// $allModels = ModelResolver::all();
// print_r($allModels);

// // Instantiate all models
// $instances = ModelResolver::instances();


class PasswordResetToken extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'password_reset_tokens';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'email';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email' => 'string',
        'token' => 'string',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that are not mass assignable.
     */
    protected $guarded = ['email'];

    // Scopes
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    public function scopeNotExpired(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->created_at->addMinutes(60)->isPast();
    }

    // Custom methods
    public function isValid(): bool
    {
        return !$this->is_expired;
    }

    public function isExpired(): bool
    {
        return $this->is_expired;
    }

    public function getRemainingTime(): string
    {
        if ($this->is_expired) {
            return 'Token has expired';
        }
        
        $remaining = now()->diffInMinutes($this->created_at->addMinutes(60));
        return $remaining . ' minutes remaining';
    }

    public function isRecent(): bool
    {
        return $this->created_at->addMinutes(30)->isFuture();
    }
}
