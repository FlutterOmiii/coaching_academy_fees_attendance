<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory;

    public const FOCUS_AREAS = [
        'batting' => 'Batting',
        'bowling' => 'Bowling',
        'fielding' => 'Fielding',
        'wicket_keeping' => 'Wicket Keeping',
        'fitness' => 'Fitness',
        'match_practice' => 'Match Practice',
        'general' => 'General',
    ];

    protected $fillable = [
        'batch_id', 'coach_id', 'title', 'session_date', 'start_time', 'end_time',
        'ground', 'focus_area', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('session_date', '>=', today())
            ->where('status', 'scheduled')
            ->orderBy('session_date')
            ->orderBy('start_time');
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }

    public function getFocusAreaLabelAttribute(): string
    {
        return self::FOCUS_AREAS[$this->focus_area] ?? ucfirst((string) $this->focus_area);
    }
}
