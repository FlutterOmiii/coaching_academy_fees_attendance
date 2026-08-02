<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'cricket_match_id', 'student_id', 'batting_position',
        'runs_scored', 'balls_faced', 'fours', 'sixes', 'is_out', 'dismissal_type',
        'overs_bowled', 'maidens', 'runs_conceded', 'wickets', 'wides', 'no_balls',
        'catches', 'run_outs', 'stumpings', 'rating', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_out' => 'boolean',
            'overs_bowled' => 'decimal:1',
            'rating' => 'decimal:1',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(CricketMatch::class, 'cricket_match_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Runs per 100 balls faced. */
    public function getStrikeRateAttribute(): float
    {
        if ($this->balls_faced < 1) {
            return 0.0;
        }

        return round(($this->runs_scored / $this->balls_faced) * 100, 2);
    }

    /** Runs conceded per over bowled. */
    public function getEconomyAttribute(): float
    {
        if ((float) $this->overs_bowled <= 0) {
            return 0.0;
        }

        return round($this->runs_conceded / (float) $this->overs_bowled, 2);
    }
}
