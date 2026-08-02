<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessment extends Model
{
    use HasFactory;

    /**
     * overall_rating is omitted deliberately: the database derives it from the
     * five component ratings.
     */
    protected $fillable = [
        'student_id', 'coach_id', 'assessment_date',
        'batting_rating', 'bowling_rating', 'fielding_rating', 'fitness_rating', 'discipline_rating',
        'strengths', 'improvements', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'overall_rating' => 'decimal:1',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }
}
