<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class StudentAttendance extends Model
{
    use HasFactory;

    public const STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'excused' => 'Excused',
    ];

    /** Statuses that count towards the attendance percentage. */
    public const PRESENT_STATUSES = ['present', 'late'];

    protected $fillable = [
        'student_id', 'batch_id', 'training_session_id', 'attendance_date',
        'status', 'check_in', 'remarks', 'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'marked_by');
    }

    public function scopePresent(Builder $query): Builder
    {
        return $query->whereIn('status', self::PRESENT_STATUSES);
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('attendance_date', [$from, $to]);
    }

    /**
     * Attendance percentage across a set of records, counting present + late
     * against the total marked. Accepts a query builder or a relation.
     */
    public static function percentageFor(Builder|Relation $query): float
    {
        $total = (clone $query)->count();

        if ($total === 0) {
            return 0.0;
        }

        $present = (clone $query)->whereIn('status', self::PRESENT_STATUSES)->count();

        return round(($present / $total) * 100, 1);
    }
}
