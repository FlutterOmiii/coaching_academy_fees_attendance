<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_COACH = 'coach';
    public const ROLE_ACCOUNTANT = 'accountant';

    public const ROLES = [
        self::ROLE_OWNER => 'Academy Owner',
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_COACH => 'Coach',
        self::ROLE_ACCOUNTANT => 'Accountant',
    ];

    /**
     * Abilities each role is granted. The owner is handled separately and
     * implicitly holds every ability.
     *
     * Abilities are per-verb (view / create / edit / delete) rather than a
     * single "manage", so a role can be given read or edit access without
     * silently inheriting the ability to destroy records.
     *
     * Two rules drive this matrix:
     *   - Deleting anything is restricted to the owner and admin.
     *   - finance.view gates every rupee figure in the UI. Coaches do not
     *     have it, so fees, salaries and revenue never render for them.
     */
    private const ROLE_ABILITIES = [
        self::ROLE_ADMIN => [
            'dashboard.view', 'finance.view',
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'coaches.view', 'coaches.create', 'coaches.edit', 'coaches.delete',
            'batches.view', 'batches.create', 'batches.edit', 'batches.delete',
            'attendance.view', 'attendance.manage',
            'leaves.view', 'leaves.manage', 'leaves.delete',
            'training.view', 'training.manage', 'training.delete',
            'fees.view', 'fees.manage', 'fees.delete',
            'expenses.view', 'expenses.manage', 'expenses.delete',
            'matches.view', 'matches.manage', 'matches.delete',
            'tournaments.view', 'tournaments.manage', 'tournaments.delete',
            'teams.view', 'teams.manage', 'teams.delete',
            'performance.view', 'performance.manage', 'performance.delete',
            'calendar.view', 'calendar.manage', 'calendar.delete',
            'reports.view',
            'settings.manage',
            // Birthday lists are informational and admin-only, like finance.
            'birthdays.view',
        ],

        // Coaching staff: read the academy, run the cricket. No money, no deletes.
        self::ROLE_COACH => [
            'dashboard.view',
            'students.view',
            'coaches.view',
            // View and edit batches, but not create or delete them.
            'batches.view', 'batches.edit',
            'attendance.view', 'attendance.manage',
            'leaves.view', 'leaves.manage',
            'training.view', 'training.manage',
            'matches.view', 'matches.manage',
            'tournaments.view', 'tournaments.manage',
            'teams.view', 'teams.manage',
            'performance.view', 'performance.manage',
            'calendar.view', 'calendar.manage',
        ],

        // Money only: the ledger, the students it belongs to, and reports.
        // Manages expenses but, like fees, cannot delete them (owner/admin only).
        self::ROLE_ACCOUNTANT => [
            'dashboard.view', 'finance.view',
            'students.view',
            'batches.view',
            'fees.view', 'fees.manage',
            'expenses.view', 'expenses.manage',
            'reports.view',
        ],
    ];

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar', 'role', 'status', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function coach(): HasOne
    {
        return $this->hasOne(Coach::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isCoach(): bool
    {
        return $this->role === self::ROLE_COACH;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * The owner bypasses ability checks; every other role is limited to the
     * abilities mapped above.
     */
    public function hasAbility(string $ability): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return in_array($ability, self::ROLE_ABILITIES[$this->role] ?? [], true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }
}
