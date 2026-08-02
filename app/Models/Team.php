<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'tournament_id', 'coach_id', 'age_group', 'logo', 'description', 'status',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'team_student')
            ->withPivot(['jersey_number', 'is_captain', 'is_vice_captain', 'role'])
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(CricketMatch::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getAgeGroupLabelAttribute(): string
    {
        return Batch::AGE_GROUPS[$this->age_group] ?? ucfirst((string) $this->age_group);
    }

    public function getCaptainAttribute(): ?Student
    {
        return $this->students()->wherePivot('is_captain', true)->first();
    }
}
