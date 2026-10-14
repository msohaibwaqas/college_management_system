<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class Session extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'sessions';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'string',
        'user_id' => 'integer',
        'ip_address' => 'string',
        'user_agent' => 'string',
        'payload' => 'string',
        'last_activity' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForIpAddress(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeActive(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('last_activity', '>', now()->subMinutes($minutes)->timestamp);
    }

    public function scopeInactive(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('last_activity', '<=', now()->subMinutes($minutes)->timestamp);
    }

    public function scopeForUserAgent(Builder $query, string $userAgent): Builder
    {
        return $query->where('user_agent', 'like', "%{$userAgent}%");
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->last_activity < now()->subHours(2)->timestamp;
    }

    public function getIsActiveAttribute(): bool
    {
        return !$this->is_expired;
    }

    public function getLastActivityDateAttribute(): string
    {
        return date('Y-m-d H:i:s', $this->last_activity);
    }

    public function getFormattedUserAgentAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Unknown';
        }
        
        // Simple parsing of user agent
        $agent = $this->user_agent;
        if (strpos($agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($agent, 'Safari') !== false) return 'Safari';
        if (strpos($agent, 'Edge') !== false) return 'Edge';
        if (strpos($agent, 'Opera') !== false) return 'Opera';
        return 'Unknown Browser';
    }

    // Custom methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isExpired(): bool
    {
        return $this->is_expired;
    }

    public function getBrowserInfo(): array
    {
        $userAgent = $this->user_agent;
        
        $browser = 'Unknown';
        $version = 'Unknown';
        
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Chrome';
            $version = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Firefox';
            $version = $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Safari';
            $version = $matches[1];
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Edge';
            $version = $matches[1];
        }
        
        return [
            'browser' => $browser,
            'version' => $version
        ];
    }

    public function getDeviceInfo(): string
    {
        $userAgent = $this->user_agent;
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return 'Mobile';
        } elseif (preg_match('/Tablet/', $userAgent)) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    public function getFullSessionInfo(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'browser' => $this->formatted_user_agent,
            'device' => $this->getDeviceInfo(),
            'last_activity' => $this->last_activity,
            'last_activity_date' => $this->last_activity_date,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
        ];
    }

    public function getMinutesSinceLastActivity(): int
    {
        return (int) ((time() - $this->last_activity) / 60);
    }
}
