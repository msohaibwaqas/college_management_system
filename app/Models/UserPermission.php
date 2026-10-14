<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;

class UserPermission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_permissions';

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
        'permission_id',
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

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
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

    public function scopeForPermission(Builder $query, int $permissionId): Builder
    {
        return $query->where('permission_id', $permissionId);
    }

    public function scopeForUserEmail(Builder $query, string $email): Builder
    {
        return $query->join('users', 'user_permissions.user_id', '=', 'users.id')
                    ->where('users.email', $email)
                    ->select('user_permissions.*');
    }

    public function scopeForPermissionName(Builder $query, string $permissionName): Builder
    {
        return $query->join('permissions', 'user_permissions.permission_id', '=', 'permissions.id')
                    ->where('permissions.name', $permissionName)
                    ->select('user_permissions.*');
    }

    public function scopeWithUserDetails(Builder $query): Builder
    {
        return $query->with('user');
    }

    public function scopeWithPermissionDetails(Builder $query): Builder
    {
        return $query->with('permission');
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

    public function getPermissionNameAttribute(): string
    {
        return $this->permission ? $this->permission->name : 'Unknown Permission';
    }

    public function getPermissionDescriptionAttribute(): string
    {
        return $this->permission ? $this->permission->description : 'No description';
    }

    public function getPermissionCategoryAttribute(): string
    {
        return $this->permission ? $this->permission->category : 'General';
    }

    public function getIsFromRoleAttribute(): bool
    {
        // Check if this permission is also granted through a role
        return $this->user && $this->user->roles->pluck('permissions')->flatten()->contains('id', $this->permission_id);
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'user_email' => $this->user_email,
            'permission_id' => $this->permission_id,
            'permission_name' => $this->permission_name,
            'permission_description' => $this->permission_description,
            'permission_category' => $this->permission_category,
            'is_from_role' => $this->is_from_role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getUserPermissionInfo(): array
    {
        return [
            'user' => $this->user ? $this->user->getFullDetails() : null,
            'permission' => $this->permission ? $this->permission->getFullDetails() : null,
        ];
    }

    public function isValid(): bool
    {
        return $this->user && $this->permission;
    }

    public function isRedundant(): bool
    {
        // Check if this direct permission is redundant because it's already granted via role
        return $this->is_from_role;
    }

    public function getFormattedName(): string
    {
        return $this->user_name . ' - ' . $this->permission_name;
    }

    public function canBeRemoved(): bool
    {
        // Can be removed if it's not granted by any role (direct permission)
        return !$this->is_from_role;
    }
}
