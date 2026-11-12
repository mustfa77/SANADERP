<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Concerns\HasTranslations;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name_ar', 'name_en', 'email', 'phone', 'password', 'status', 'user_type', 'agency_id',
    'preferred_language', 'avatar', 'must_change_password', 'two_factor_enabled', 'created_by',
])]
#[Hidden(['password', 'remember_token', 'two_factor_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasTranslations, Notifiable, SoftDeletes;

    /**
     * Session key holding the branch chosen in the top-bar switcher.
     */
    public const SELECTED_BRANCH_SESSION_KEY = 'current_branch_id';

    /**
     * @var list<int>|null
     */
    private ?array $allowedBranchIdsCache = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_expires_at' => 'datetime',
            'must_change_password' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'user_type' => UserType::class,
        ];
    }

    /**
     * @return BelongsToMany<Branch, $this>
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')->withTimestamps();
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<LoginAttempt, $this>
     */
    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    /**
     * Actions performed by this user.
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function displayName(): string
    {
        return (string) $this->trans('name');
    }

    public function avatarUrl(): string
    {
        return $this->avatar
            ? Storage::disk('public')->url($this->avatar)
            : asset('assets/images/users/avatar-1.jpg');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isInternal(): bool
    {
        return $this->user_type === UserType::Internal;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('permissions.super_admin_role'));
    }

    /**
     * True when the user may see every branch ("branches.all").
     */
    public function hasAllBranches(): bool
    {
        return $this->isSuperAdmin() || $this->checkPermissionTo('branches.all');
    }

    /**
     * Branch ids the user may access.
     *
     * @return list<int>
     */
    public function allowedBranchIds(): array
    {
        return $this->allowedBranchIdsCache ??= $this->hasAllBranches()
            ? Branch::query()->pluck('id')->all()
            : $this->branches()->pluck('branches.id')->all();
    }

    /**
     * Branch chosen in the top-bar switcher, ignored if no longer allowed.
     */
    public function selectedBranchId(): ?int
    {
        if (! app()->bound('session') || ! session()->isStarted()) {
            return null;
        }

        $selected = session(self::SELECTED_BRANCH_SESSION_KEY);

        return $selected !== null && in_array((int) $selected, $this->allowedBranchIds(), true)
            ? (int) $selected
            : null;
    }

    /**
     * Email code required at login: enabled by the user or by any of their roles.
     */
    public function requiresTwoFactor(): bool
    {
        return $this->two_factor_enabled || $this->roles->contains('requires_2fa', true);
    }

    /**
     * Clear per-request memoised data (after branch or role changes).
     */
    public function flushAccessCache(): void
    {
        $this->allowedBranchIdsCache = null;
        $this->unsetRelation('roles');
        $this->unsetRelation('permissions');
    }
}
