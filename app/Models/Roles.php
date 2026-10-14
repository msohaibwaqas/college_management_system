<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;



class Roles extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'roles';

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
        'is_system_role',
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
        'is_system_role' => 'boolean',
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
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
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
    public function scopeForGuard(Builder $query, string $guard = 'web'): Builder
    {
        return $query->where('guard_name', $guard);
    }

    public function scopeSystemRoles(Builder $query): Builder
    {
        return $query->where('is_system_role', true);
    }

    public function scopeNonSystemRoles(Builder $query): Builder
    {
        return $query->where('is_system_role', false);
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
        return str_replace(['_', '-'], ' ', ucwords(strtolower($this->name)));
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

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = $value ? trim($value) : null;
    }

    // Custom methods
    public function hasPermission($permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions->contains('name', $permission);
        }
        
        if (is_int($permission)) {
            return $this->permissions->contains('id', $permission);
        }
        
        return $this->permissions->contains($permission);
    }

    public function hasPermissionTo($permission): bool
    {
        return $this->hasPermission($permission);
    }

    public function givePermissionTo($permission): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }
        
        if (!$permission) {
            return false;
        }
        
        $this->permissions()->attach($permission->id);
        return true;
    }

    public function removePermissionTo($permission): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }
        
        if (!$permission) {
            return false;
        }
        
        $this->permissions()->detach($permission->id);
        return true;
    }

    public function getPermissionNames(): array
    {
        return $this->permissions->pluck('name')->toArray();
    }

    public function getUserCount(): int
    {
        return $this->users()->count();
    }

    public function getPermissionCount(): int
    {
        return $this->permissions()->count();
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'formatted_name' => $this->formatted_name,
            'description' => $this->description,
            'guard_name' => $this->guard_name,
            'is_system_role' => $this->is_system_role,
            'is_web_guard' => $this->is_web_guard,
            'is_api_guard' => $this->is_api_guard,
            'user_count' => $this->getUserCount(),
            'permission_count' => $this->getPermissionCount(),
            'permissions' => $this->getPermissionNames(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isAssignable(): bool
    {
        return !$this->is_system_role || $this->name === 'admin'; // Admin can be assigned
    }
}
