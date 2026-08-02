<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CricketMatch extends Model
{
    use HasFactory;

    /** "match" is a reserved word in PHP, hence the model and table name. */
    protected $table = 'cricket_matches';

    public const MATCH_TYPES = [
        'practice' => 'Practice',
        'friendly' => 'Friendly',
        'tournament' => 'Tournament',
        'league' => 'League',
        'knockout' => 'Knockout',
        'final' => 'Final',
    ];

    public const RESULTS = [
        'won' => 'Won',
        'lost' => 'Lost',
        'tie' => 'Tied',
        'draw' => 'Draw',
        'no_result' => 'No Result',
    ];

    protected $fillable = [
        'tournament_id', 'team_id', 'opponent_name', 'match_date', 'start_time', 'venue',
        'match_type', 'overs', 'toss_won_by', 'toss_decision', 'status', 'result', 'win_margin',
        'academy_runs', 'academy_wickets', 'academy_overs',
        'opponent_runs', 'opponent_wickets', 'opponent_overs',
        'man_of_match_id', 'summary',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'date',
            'academy_overs' => 'decimal:1',
            'opponent_overs' => 'decimal:1',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function manOfMatch(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'man_of_match_id');
    }

    public function performances(): HasMany
    {
        return $this->hasMany(MatchPerformance::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('match_date', '>=', today())
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('match_date');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->where('result', 'won');
    }

    public function getTitleAttribute(): string
    {
        return 'Academy vs '.$this->opponent_name;
    }

    public function getAcademyScoreAttribute(): string
    {
        if ($this->academy_runs === null) {
            return '-';
        }

        return "{$this->academy_runs}/{$this->academy_wickets} ({$this->academy_overs} ov)";
    }

    public function getOpponentScoreAttribute(): string
    {
        if ($this->opponent_runs === null) {
            return '-';
        }

        return "{$this->opponent_runs}/{$this->opponent_wickets} ({$this->opponent_overs} ov)";
    }
}
