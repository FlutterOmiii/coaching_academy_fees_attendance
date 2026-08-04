<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A category in the owner's private personal ledger. Owner-scoped by admin_id so
 * each person manages their own list — completely separate from business books.
 */
class PersonalExpenseCategory extends Model
{
    protected $fillable = ['admin_id', 'name', 'icon'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeOwnedBy(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Make sure this admin has the sensible starter set (only the first time),
     * then return their full, ordered list. Idempotent — safe to call on every
     * page load.
     */
    public static function forOwner(int $adminId)
    {
        if (! static::ownedBy($adminId)->exists()) {
            foreach (PersonalExpense::CATEGORIES as $name => $icon) {
                static::create(['admin_id' => $adminId, 'name' => $name, 'icon' => $icon]);
            }
        }

        return static::ownedBy($adminId)->orderBy('name')->get();
    }
}
