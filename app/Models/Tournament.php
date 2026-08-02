<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    public const FORMATS = [
        't10' => 'T10',
        't20' => 'T20',
        'odi' => 'One Day',
        'multi_day' => 'Multi Day',
        'custom' => 'Custom',
    ];

    protected $fillable = [
        'name', 'organizer', 'venue', 'format', 'start_date', 'end_date',
        'entry_fee', 'banner', 'description', 'status', 'final_position',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'entry_fee' => 'decimal:2',
        ];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(CricketMatch::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'upcoming')->orderBy('start_date');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function getFormatLabelAttribute(): string
    {
        return self::FORMATS[$this->format] ?? strtoupper((string) $this->format);
    }
}
