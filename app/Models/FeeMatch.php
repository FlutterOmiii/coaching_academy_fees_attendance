<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A match the academy charges a fee for. The per-student money lives on the
 * MatchFee records; this row holds the event itself and the default amount.
 */
class FeeMatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'match_date', 'venue', 'description', 'fee_amount', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'date',
            'fee_amount' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------- Relations

    public function fees(): HasMany
    {
        return $this->hasMany(MatchFee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // ------------------------------------------------------------------ Scopes

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('venue', 'like', "%{$term}%");
        });
    }

    // --------------------------------------------------------------- Accessors

    /** Collection totals, cheap when fees are eager-loaded withSum/withCount. */
    public function getCollectedAttribute(): float
    {
        return (float) $this->fees->where('status', 'paid')->sum('amount');
    }

    public function getPendingAttribute(): float
    {
        return (float) $this->fees->where('status', 'pending')->sum('amount');
    }
}
