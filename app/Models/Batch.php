<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    public const AGE_GROUPS = [
        'under_10' => 'Under 10',
        'under_12' => 'Under 12',
        'under_14' => 'Under 14',
        'under_16' => 'Under 16',
        'under_19' => 'Under 19',
        'senior' => 'Senior',
        'open' => 'Open',
    ];

    public const SKILL_LEVELS = [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'professional' => 'Professional',
    ];

    public const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    protected $fillable = [
        'name', 'code', 'coach_id', 'age_group', 'skill_level', 'capacity',
        'start_time', 'end_time', 'training_days', 'ground',
        'monthly_fee', 'start_date', 'end_date', 'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'training_days' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_fee' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------- Relations

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /** Assistant / support coaches assigned to this batch. */
    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(Coach::class, 'batch_coach')
            ->withPivot(['role', 'assigned_on'])
            ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'batch_student')
            ->withPivot(['joined_on', 'left_on', 'status'])
            ->withTimestamps();
    }

    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('status', 'active');
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    // ------------------------------------------------------------------ Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('ground', 'like', "%{$term}%");
        });
    }

    // --------------------------------------------------------------- Accessors

    public function getAgeGroupLabelAttribute(): string
    {
        return self::AGE_GROUPS[$this->age_group] ?? ucfirst((string) $this->age_group);
    }

    /** e.g. "Mon, Wed, Fri" */
    public function getTrainingDaysLabelAttribute(): string
    {
        return collect($this->training_days ?? [])
            ->map(fn ($day) => self::DAY_NAMES[$day] ?? '')
            ->filter()
            ->implode(', ');
    }

    public function getEnrolledCountAttribute(): int
    {
        return $this->activeStudents()->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->capacity - $this->enrolled_count);
    }

    /** How full the batch is, as a percentage of capacity. */
    public function getOccupancyPercentageAttribute(): float
    {
        if ($this->capacity < 1) {
            return 0.0;
        }

        return round(min(100, ($this->enrolled_count / $this->capacity) * 100), 1);
    }
}
