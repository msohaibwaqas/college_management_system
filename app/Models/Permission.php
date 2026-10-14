<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;



class Permission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'permissions';

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
        'name',
        'description',
        'guard_name',
        'category',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'name' => 'string',
        'description' => 'string',
        'guard_name' => 'string',
        'category' => 'string',
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
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Roles::class, 'role_permissions', 'permission_id', 'role_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id');
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
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeForGuard(Builder $query, string $guard = 'web'): Builder
    {
        return $query->where('guard_name', $guard);
    }

    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    public function scopeSearchByDescription(Builder $query, string $search): Builder
    {
        return $query->where('description', 'like', "%{$search}%");
    }

    // Accessors
    public function getFormattedNameAttribute(): string
    {
        return str_replace(['.', '_'], ' ', ucfirst($this->name));
    }

    public function getIsWebGuardAttribute(): bool
    {
        return $this->guard_name === 'web';
    }

    public function getIsApiGuardAttribute(): bool
    {
        return $this->guard_name === 'api';
    }

    // Mutators
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = strtolower(trim($value));
    }

    public function setGuardNameAttribute($value): void
    {
        $this->attributes['guard_name'] = $value ?: 'web';
    }

    public function setCategoryAttribute($value): void
    {
        $this->attributes['category'] = ucfirst(strtolower(trim($value)));
    }

    // Custom methods
    public function getPermissionGroup(): string
    {
        $parts = explode('.', $this->name);
        return $parts[0] ?? 'general';
    }

    public function getPermissionAction(): string
    {
        $parts = explode('.', $this->name);
        return $parts[1] ?? 'access';
    }

    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }
        
        if (is_int($role)) {
            return $this->roles->contains('id', $role);
        }
        
        return $this->roles->contains($role);
    }

    public function hasUser($user): bool
    {
        if (is_string($user)) {
            return $this->users->contains('email', $user);
        }
        
        if (is_int($user)) {
            return $this->users->contains('id', $user);
        }
        
        return $this->users->contains($user);
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'formatted_name' => $this->formatted_name,
            'description' => $this->description,
            'category' => $this->category,
            'guard_name' => $this->guard_name,
            'is_web_guard' => $this->is_web_guard,
            'is_api_guard' => $this->is_api_guard,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getRelatedRoles(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    public function getRelatedUsers(): array
    {
        return $this->users->pluck('email')->toArray();
    }
}
