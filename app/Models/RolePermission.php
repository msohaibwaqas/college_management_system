<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class RolePermission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'role_permissions';

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
        'role_id',
        'permission_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'role_id' => 'integer',
        'permission_id' => 'integer',
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
    public function roles(): BelongsTo
    {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
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
    public function scopeForRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeForPermission(Builder $query, int $permissionId): Builder
    {
        return $query->where('permission_id', $permissionId);
    }

    public function scopeForRoleName(Builder $query, string $roleName): Builder
    {
        return $query->join('roles', 'role_permissions.role_id', '=', 'roles.id')
                    ->where('roles.name', $roleName)
                    ->select('role_permissions.*');
    }

    public function scopeForPermissionName(Builder $query, string $permissionName): Builder
    {
        return $query->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                    ->where('permissions.name', $permissionName)
                    ->select('role_permissions.*');
    }

    // Accessors
    public function getRoleNameAttribute(): string
    {
        return $this->role ? $this->role->name : 'Unknown Role';
    }

    public function getPermissionNameAttribute(): string
    {
        return $this->permission ? $this->permission->name : 'Unknown Permission';
    }

    public function getPermissionDescriptionAttribute(): string
    {
        return $this->permission ? $this->permission->description : 'No description';
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'role_name' => $this->role_name,
            'permission_id' => $this->permission_id,
            'permission_name' => $this->permission_name,
            'permission_description' => $this->permission_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getRolePermissionInfo(): array
    {
        return [
            'role' => $this->role ? $this->role->getFullDetails() : null,
            'permission' => $this->permission ? $this->permission->getFullDetails() : null,
        ];
    }

    public function isValid(): bool
    {
        return $this->role && $this->permission;
    }

    public function getFormattedName(): string
    {
        return $this->role_name . ' - ' . $this->permission_name;
    }
}
