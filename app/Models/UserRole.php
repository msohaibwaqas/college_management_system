<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;

class UserRole extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_roles';

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
        'user_id',
        'role_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
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
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeForUserEmail(Builder $query, string $email): Builder
    {
        return $query->join('users', 'user_roles.user_id', '=', 'users.id')
                    ->where('users.email', $email)
                    ->select('user_roles.*');
    }

    public function scopeForRoleName(Builder $query, string $roleName): Builder
    {
        return $query->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', $roleName)
                    ->select('user_roles.*');
    }

    public function scopeWithUserDetails(Builder $query): Builder
    {
        return $query->with('user');
    }

    public function scopeWithRoleDetails(Builder $query): Builder
    {
        return $query->with('role');
    }

    public function scopeActiveUsers(Builder $query): Builder
    {
        return $query->join('users', 'user_roles.user_id', '=', 'users.id')
                    ->where('users.status', 'active')
                    ->select('user_roles.*');
    }

    // Accessors
    public function getUserNameAttribute(): string
    {
        return $this->user ? $this->user->full_name : 'Unknown User';
    }

    public function getUserEmailAttribute(): string
    {
        return $this->user ? $this->user->email : 'unknown@example.com';
    }

    public function getRoleNameAttribute(): string
    {
        return $this->role ? $this->role->name : 'Unknown Role';
    }

    public function getRoleDescriptionAttribute(): string
    {
        return $this->role ? $this->role->description : 'No description';
    }

    public function getRoleGuardNameAttribute(): string
    {
        return $this->role ? $this->role->guard_name : 'web';
    }

    public function getIsSystemRoleAttribute(): bool
    {
        return $this->role ? $this->role->is_system_role : false;
    }

    public function getFormattedRoleNameAttribute(): string
    {
        return $this->role ? $this->role->formatted_name : 'Unknown Role';
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'user_email' => $this->user_email,
            'role_id' => $this->role_id,
            'role_name' => $this->role_name,
            'role_description' => $this->role_description,
            'role_guard_name' => $this->role_guard_name,
            'is_system_role' => $this->is_system_role,
            'formatted_role_name' => $this->formatted_role_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getUserRoleInfo(): array
    {
        return [
            'user' => $this->user ? $this->user->getFullDetails() : null,
            'role' => $this->role ? $this->role->getFullDetails() : null,
        ];
    }

    public function isValid(): bool
    {
        return $this->user && $this->role;
    }

    public function getRolePermissions(): array
    {
        if (!$this->role) {
            return [];
        }
        
        return $this->role->permissions->pluck('name')->toArray();
    }

    public function getUserEffectivePermissions(): array
    {
        if (!$this->user) {
            return [];
        }
        
        // Get role permissions + direct user permissions
        $rolePermissions = $this->getRolePermissions();
        $userPermissions = $this->user->permissions->pluck('name')->toArray();
        
        return array_unique(array_merge($rolePermissions, $userPermissions));
    }

    public function canBeRemoved(): bool
    {
        // Cannot remove system roles if they are system roles
        return !$this->is_system_role;
    }

    public function getFormattedName(): string
    {
        return $this->user_name . ' - ' . $this->role_name;
    }

    public function getUserAccessLevel(): string
    {
        if (!$this->role) {
            return 'No Role';
        }
        
        $roleName = strtolower($this->role_name);
        
        if ($roleName === 'super_admin' || $roleName === 'admin') {
            return 'Administrator';
        } elseif ($roleName === 'principal' || $roleName === 'vice_principal') {
            return 'Management';
        } elseif ($roleName === 'teacher') {
            return 'Faculty';
        } elseif ($roleName === 'student' || $roleName === 'parent') {
            return 'Student';
        } else {
            return 'User';
        }
    }
}
