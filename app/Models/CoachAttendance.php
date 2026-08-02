<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachAttendance extends Model
{
    use HasFactory;

    public const STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent',
        'half_day' => 'Half Day',
        'leave' => 'Leave',
    ];

    protected $fillable = [
        'coach_id', 'attendance_date', 'status', 'check_in', 'check_out', 'remarks', 'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'marked_by');
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopePresent(Builder $query): Builder
    {
        return $query->whereIn('status', ['present', 'half_day']);
    }
}
