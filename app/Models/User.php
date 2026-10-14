<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Helpers\ModelResolver;


class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * Eager-load roles and staff on every User query to prevent N+1 queries
     * in blade layouts that call hasRole(), full_name, profile_picture_url, etc.
     */
    protected $with = ['roles', 'staff'];

    /**
     * The table associated with the model.
     */
    protected $table = 'users';

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
        'username',
        'email',
        'password',
        'must_change_password',
        'status',
        'is_super_admin',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login_at',
        'password_changed_at',
        'staff_id',
        'student_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'is_super_admin' => 'boolean',
        'must_change_password' => 'boolean',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Roles::class, 'user_roles', 'user_id', 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id');
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

    public function teacherProfile(): HasMany
    {
        return $this->hasMany(TeacherProfile::class, 'user_id', 'id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id', 'id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', 'suspended');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuperAdmin(Builder $query): Builder
    {
        return $query->where('is_super_admin', true);
    }

    public function scopeNotSuperAdmin(Builder $query): Builder
    {
        return $query->where('is_super_admin', false);
    }

    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->where(function($q) use ($name) {
            $q->where('first_name', 'like', "%{$name}%")
              ->orWhere('last_name', 'like', "%{$name}%");
        });
    }

    public function scopeSearchByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    public function scopeSearchByUsername(Builder $query, string $username): Builder
    {
        return $query->where('username', 'like', "%{$username}%");
    }

    public function scopeWithCnic(Builder $query): Builder
    {
        return $query->whereNotNull('cnic');
    }

    public function scopeWithContact(Builder $query): Builder
    {
        return $query->whereNotNull('contact_number');
    }

    // Accessors
    public function getFirstNameAttribute(): string
    {
        return $this->staff->first_name ?? '';
    }

    public function getLastNameAttribute(): string
    {
        return $this->staff->last_name ?? '';
    }

    public function getCnicAttribute(): ?string
    {
        return $this->staff->cnic ?? null;
    }

    public function getContactNumberAttribute(): ?string
    {
        return $this->staff->contact_number ?? null;
    }

    public function getProfilePictureAttribute(): ?string
    {
        return $this->staff->picture ?? null;
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }

    public function getFormattedNameAttribute(): string
    {
        return ucwords(strtolower($this->first_name . ' ' . ($this->last_name ?? '')));
    }

    public function getIsEmailVerifiedAttribute(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return (bool) ($this->attributes['is_super_admin'] ?? false);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsInactiveAttribute(): bool
    {
        return $this->status === 'inactive';
    }

    public function getIsSuspendedAttribute(): bool
    {
        return $this->status === 'suspended';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->is_email_verified && $this->status === 'active';
    }

    public function getHasTwoFactorEnabledAttribute(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function getFormattedCnicAttribute(): string
    {
        $cnic = $this->cnic;
        if ($cnic && strlen($cnic) === 13) {
            return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
        }
        return $cnic ?? '';
    }

    public function getProfilePictureUrlAttribute(): string
    {
        if ($this->staff && $this->staff->picture) {
            return $this->staff->getProfilePictureUrl();
        }
        if ($this->student && $this->student->picture) {
            return $this->student->getProfilePictureUrl();
        }
        return asset('uploads/default.jpg');
    }

    public function getRoleNamesAttribute(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    public function getPermissionNamesAttribute(): array
    {
        return $this->permissions->pluck('name')->toArray();
    }

    // Mutators
    public function setUsernameAttribute($value): void
    {
        $this->attributes['username'] = strtolower(trim($value));
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    public function setCnicAttribute($value): void
    {
        $this->attributes['cnic'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setContactNumberAttribute($value): void
    {
        $this->attributes['contact_number'] = $value ? preg_replace('/[^0-9]/', '', $value) : null;
    }

    public function setFirstNameAttribute($value): void
    {
        $this->attributes['first_name'] = trim(ucwords(strtolower($value)));
    }

    public function setLastNameAttribute($value): void
    {
        $this->attributes['last_name'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    public function setPasswordAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'formatted_name' => $this->formatted_name,
            'cnic' => $this->formatted_cnic,
            'contact_number' => $this->contact_number,
            'profile_picture_url' => $this->profile_picture_url,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'is_super_admin' => $this->is_super_admin,
            'is_email_verified' => $this->is_email_verified,
            'is_verified' => $this->is_verified,
            'has_two_factor' => $this->has_two_factor_enabled,
            'role_names' => $this->role_names,
            'permission_names' => $this->permission_names,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
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

    public function hasPermission($permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions->contains('name', $permission) ||
                   $this->roles->pluck('permissions')->flatten()->contains('name', $permission);
        }
        
        if (is_int($permission)) {
            return $this->permissions->contains('id', $permission) ||
                   $this->roles->pluck('permissions')->flatten()->contains('id', $permission);
        }
        
        return $this->permissions->contains($permission) ||
               $this->roles->pluck('permissions')->flatten()->contains($permission);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function getPermissionsForRole(string $roleName): array
    {
        $role = $this->roles->firstWhere('name', $roleName);
        return $role ? $role->permissions->pluck('name')->toArray() : [];
    }

    public function canAccessResource(string $resource, string $action = null): bool
    {
        if ($this->is_super_admin) {
            return true;
        }
        
        if ($action) {
            return $this->hasPermission("{$resource}.{$action}");
        }
        
        return $this->hasPermission($resource);
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function setPasswordChanged(): void
    {
        $this->update(['password_changed_at' => now()]);
    }

    public function getAccessLevel(): string
    {
        if ($this->is_super_admin) {
            return 'Super Admin';
        } elseif ($this->hasRole('admin')) {
            return 'Admin';
        } elseif ($this->hasRole('principal')) {
            return 'Principal';
        } elseif ($this->hasRole('teacher')) {
            return 'Teacher';
        } elseif ($this->hasRole('student')) {
            return 'Student';
        } else {
            return 'User';
        }
    }

    public function getActiveSessionsCount(): int
    {
        return $this->sessions()->active()->count();
    }

    public function getRecentLoginStatus(): string
    {
        if (!$this->last_login_at) {
            return 'Never Logged In';
        }
        
        $daysSinceLogin = $this->last_login_at->diffInDays(now());
        
        if ($daysSinceLogin <= 1) {
            return 'Active Today';
        } elseif ($daysSinceLogin <= 7) {
            return 'Active This Week';
        } elseif ($daysSinceLogin <= 30) {
            return 'Active This Month';
        } else {
            return 'Inactive';
        }
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentProfile::class, 'user_id');
    }
}
