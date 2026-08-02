<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    public const TYPES = [
        'match' => 'Match',
        'tournament' => 'Tournament',
        'camp' => 'Training Camp',
        'trial' => 'Trials',
        'meeting' => 'Meeting',
        'holiday' => 'Holiday',
        'other' => 'Other',
    ];

    /** Calendar colour per event type. */
    public const TYPE_COLORS = [
        'match' => '#00ab55',
        'tournament' => '#e2a03f',
        'camp' => '#4361ee',
        'trial' => '#2196f3',
        'meeting' => '#805dca',
        'holiday' => '#e7515a',
        'other' => '#3b3f5c',
    ];

    protected $fillable = [
        'title', 'type', 'start_at', 'end_at', 'is_all_day', 'venue',
        'description', 'color', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_at', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('start_at');
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('start_at', [$from, $to]);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
