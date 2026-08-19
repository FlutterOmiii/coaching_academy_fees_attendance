<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    public const PLAYING_ROLES = [
        'batter' => 'Batter',
        'bowler' => 'Bowler',
        'batting_allrounder' => 'Batting Allrounder',
        'bowling_allrounder' => 'Bowling Allrounder',
        'wicket_keeper' => 'Wicket Keeper',
    ];

    protected $fillable = [
        'student_code', 'first_name', 'last_name', 'date_of_birth', 'gender', 'blood_group', 'photo',
        'email', 'phone', 'address', 'city', 'state', 'pincode', 'school_name',
        'guardian_name', 'guardian_phone', 'guardian_email', 'guardian_relation',
        'playing_role', 'batting_style', 'bowling_style',
        'admission_date', 'admission_status', 'status', 'medical_notes', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
        ];
    }

    // ---------------------------------------------------------------- Relations

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_student')
            ->withPivot(['joined_on', 'left_on', 'status'])
            ->withTimestamps();
    }

    /** Batches the student is currently enrolled in. */
    public function activeBatches(): BelongsToMany
    {
        return $this->batches()->wherePivot('status', 'active');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(BatchTransfer::class);
    }

    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function performances(): HasMany
    {
        return $this->hasMany(MatchPerformance::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(StudentAssessment::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_student')
            ->withPivot(['jersey_number', 'is_captain', 'is_vice_captain', 'role'])
            ->withTimestamps();
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

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('admission_status', 'approved');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('student_code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('guardian_name', 'like', "%{$term}%")
                ->orWhere('guardian_phone', 'like', "%{$term}%");
        });
    }

    // --------------------------------------------------------------- Accessors

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getPlayingRoleLabelAttribute(): string
    {
        return self::PLAYING_ROLES[$this->playing_role] ?? ucfirst((string) $this->playing_role);
    }

    /** Outstanding balance across every invoice raised for this student. */
    public function getPendingFeesAttribute(): float
    {
        return (float) $this->feeInvoices()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('balance_amount');
    }

    /**
     * Generate the next sequential student code, e.g. STU0001.
     */
    public static function nextCode(): string
    {
        $last = static::withTrashed()->max('id') ?? 0;

        return 'STU'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
