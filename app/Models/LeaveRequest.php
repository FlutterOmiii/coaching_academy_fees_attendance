<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LeaveRequest extends Model
{
    use HasFactory;

    public const TYPES = [
        'sick' => 'Sick Leave',
        'personal' => 'Personal',
        'travel' => 'Travel',
        'exam' => 'Exams',
        'other' => 'Other',
    ];

    protected $fillable = [
        'leavable_type', 'leavable_id', 'from_date', 'to_date', 'type', 'reason',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /** Resolves to a Student or a Coach. */
    public function leavable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** Inclusive day count of the leave period. */
    public function getDaysAttribute(): int
    {
        if (! $this->from_date || ! $this->to_date) {
            return 0;
        }

        return $this->from_date->diffInDays($this->to_date) + 1;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
