<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalExpense extends Model
{
    use SoftDeletes;

    /** Simple everyday categories with an emoji, kept lightweight on purpose. */
    public const CATEGORIES = [
        'Food' => '🍽️',
        'Groceries' => '🛒',
        'Fuel' => '⛽',
        'Travel' => '✈️',
        'Shopping' => '🛍️',
        'Bills' => '🧾',
        'Health' => '🏥',
        'Entertainment' => '🎬',
        'Education' => '📚',
        'Other' => '📌',
    ];

    protected $fillable = ['admin_id', 'title', 'amount', 'spent_on', 'category', 'notes'];

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** Only the given admin's own entries — personal means personal. */
    public function scopeOwnedBy(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeForMonth(Builder $query, $year, $month): Builder
    {
        return $query->whereYear('spent_on', $year)->whereMonth('spent_on', $month);
    }

    public function getCategoryIconAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? '📌';
    }
}
