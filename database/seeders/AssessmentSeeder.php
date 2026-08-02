<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\Student;
use App\Models\StudentAssessment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $coaches = Coach::active()->pluck('id')->all();

        if (empty($coaches)) {
            return;
        }

        $students = Student::active()->approved()->get();

        foreach ($students as $student) {
            // A quarterly review for each of the last 3 quarters.
            foreach ([0, 3, 6] as $monthsAgo) {
                if ($student->admission_date->gt(Carbon::now()->subMonths($monthsAgo))) {
                    continue;
                }

                // Ratings drift upward over time to show progression.
                $base = 5 + (int) round((6 - $monthsAgo) / 3);

                StudentAssessment::create([
                    'student_id' => $student->id,
                    'coach_id' => $coaches[array_rand($coaches)],
                    'assessment_date' => Carbon::now()->subMonths($monthsAgo)->subDays(random_int(0, 20))->toDateString(),
                    'batting_rating' => $this->rating($base, $student->playing_role === 'batsman'),
                    'bowling_rating' => $this->rating($base, $student->playing_role === 'bowler'),
                    'fielding_rating' => $this->rating($base, false),
                    'fitness_rating' => $this->rating($base, false),
                    'discipline_rating' => $this->rating($base, false),
                    'strengths' => $this->pick([
                        'Excellent hand-eye coordination and timing.',
                        'Reads the length early and plays late.',
                        'Very coachable, applies feedback immediately.',
                        'Strong wrists, clears the infield with ease.',
                        'Great temperament under pressure.',
                    ]),
                    'improvements' => $this->pick([
                        'Needs work on footwork against spin.',
                        'Should improve stamina for longer spells.',
                        'Shot selection outside off stump.',
                        'Follow-through consistency while bowling.',
                        'Communication while fielding in the ring.',
                    ]),
                    'remarks' => 'Quarterly review completed by coaching staff.',
                ]);
            }
        }
    }

    /** 1–10, nudged up for the student's specialist discipline. */
    private function rating(int $base, bool $isSpecialty): int
    {
        return max(1, min(10, $base + random_int(-1, 2) + ($isSpecialty ? 2 : 0)));
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }
}
