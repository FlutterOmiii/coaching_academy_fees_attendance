<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coach extends Model
{
    use HasFactory, SoftDeletes;

    public const SPECIALIZATIONS = [
        'batting' => 'Batting',
        'bowling' => 'Bowling',
        'fielding' => 'Fielding',
        'wicket_keeping' => 'Wicket Keeping',
        'fitness' => 'Fitness',
        'all_round' => 'All Round',
    ];

    protected $fillable = [
        'coach_code', 'admin_id', 'first_name', 'last_name', 'date_of_birth', 'gender', 'photo',
        'email', 'phone', 'alt_phone', 'address', 'city', 'state', 'pincode',
        'specialization', 'qualification', 'certification_level', 'experience_years',
        'joining_date', 'monthly_salary', 'bio', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    // ---------------------------------------------------------------- Relations

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** Batches where this coach is the head coach. */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /** Batches this coach supports in any capacity. */
    public function assignedBatches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_coach')
            ->withPivot(['role', 'assigned_on'])
            ->withTimestamps();
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(CoachAvailability::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CoachAttendance::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(StudentAssessment::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function leaveRequests(): MorphMany
    {
        return $this->morphMany(LeaveRequest::class, 'leavable');
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
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('coach_code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    // --------------------------------------------------------------- Accessors

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getSpecializationLabelAttribute(): string
    {
        return self::SPECIALIZATIONS[$this->specialization] ?? ucfirst((string) $this->specialization);
    }

    /** Distinct students taught across every batch this coach heads. */
    public function getStudentCountAttribute(): int
    {
        return Student::query()
            ->whereHas('batches', fn (Builder $q) => $q->where('batches.coach_id', $this->id))
            ->count();
    }

    public static function nextCode(): string
    {
        $last = static::withTrashed()->max('id') ?? 0;

        return 'CCH'.str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
    }
}
