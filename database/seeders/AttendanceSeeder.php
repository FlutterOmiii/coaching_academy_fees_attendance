<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Batch;
use App\Models\Coach;
use App\Models\LeaveRequest;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    /** How many days of history to generate. */
    private const DAYS = 90;

    public function run(): void
    {
        $markedBy = Admin::where('email', 'coach@academy.com')->value('id');

        $this->seedTrainingSessionsAndAttendance($markedBy);
        $this->seedCoachAttendance($markedBy);
        $this->seedLeaveRequests($markedBy);
    }

    /**
     * Walk each batch's real training days over the last 90 days, creating a
     * session and an attendance row per enrolled student. Rows are bulk
     * inserted for speed.
     */
    private function seedTrainingSessionsAndAttendance(?int $markedBy): void
    {
        $batches = Batch::with(['activeStudents:id'])->where('status', 'active')->get();
        $start = Carbon::today()->subDays(self::DAYS);

        $sessionRows = [];
        $now = Carbon::now();

        // Build every session first so they can be inserted in one statement.
        foreach ($batches as $batch) {
            $cursor = $start->copy();

            while ($cursor->lte(Carbon::today())) {
                if (in_array($cursor->dayOfWeek, $batch->training_days ?? [], true)) {
                    $sessionRows[] = [
                        'batch_id' => $batch->id,
                        'coach_id' => $batch->coach_id,
                        'title' => $batch->name.' session',
                        'session_date' => $cursor->toDateString(),
                        'start_time' => $batch->start_time,
                        'end_time' => $batch->end_time,
                        'ground' => $batch->ground,
                        'focus_area' => $this->pick([
                            'batting', 'bowling', 'fielding', 'fitness', 'match_practice', 'general',
                        ]),
                        'status' => $cursor->isToday() ? 'scheduled' : 'completed',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $cursor->addDay();
            }
        }

        foreach (array_chunk($sessionRows, 500) as $chunk) {
            DB::table('training_sessions')->insert($chunk);
        }

        // Attendance per session, per enrolled student.
        $sessions = DB::table('training_sessions')->get(['id', 'batch_id', 'session_date']);
        $studentsByBatch = $batches->mapWithKeys(
            fn (Batch $b) => [$b->id => $b->activeStudents->pluck('id')->all()]
        );

        $attendanceRows = [];

        foreach ($sessions as $session) {
            $studentIds = $studentsByBatch[$session->batch_id] ?? [];
            $sessionDate = Carbon::parse($session->session_date);

            foreach ($studentIds as $studentId) {
                // Roughly 82% turn up, with a few late and excused.
                $roll = random_int(1, 100);
                $status = match (true) {
                    $roll <= 78 => 'present',
                    $roll <= 86 => 'late',
                    $roll <= 92 => 'absent',
                    $roll <= 96 => 'excused',
                    default => 'absent',
                };

                $attendanceRows[] = [
                    'student_id' => $studentId,
                    'batch_id' => $session->batch_id,
                    'training_session_id' => $session->id,
                    'attendance_date' => $session->session_date,
                    'status' => $status,
                    'check_in' => in_array($status, ['present', 'late'], true)
                        ? $sessionDate->copy()->setTime(6, random_int(0, 30))->format('H:i:s')
                        : null,
                    'marked_by' => $markedBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($attendanceRows, 1000) as $chunk) {
            // A student can sit in two batches on the same day, but only one row
            // per (student, batch, date) is allowed — ignore any collisions.
            DB::table('student_attendances')->insertOrIgnore($chunk);
        }
    }

    private function seedCoachAttendance(?int $markedBy): void
    {
        $coaches = Coach::pluck('id');
        $now = Carbon::now();
        $rows = [];

        foreach ($coaches as $coachId) {
            $cursor = Carbon::today()->subDays(self::DAYS);

            while ($cursor->lte(Carbon::today())) {
                // Sunday off.
                if ($cursor->dayOfWeek !== 0) {
                    $roll = random_int(1, 100);
                    $status = match (true) {
                        $roll <= 88 => 'present',
                        $roll <= 93 => 'half_day',
                        $roll <= 97 => 'leave',
                        default => 'absent',
                    };

                    $rows[] = [
                        'coach_id' => $coachId,
                        'attendance_date' => $cursor->toDateString(),
                        'status' => $status,
                        'check_in' => $status === 'absent' ? null : '05:45:00',
                        'check_out' => $status === 'absent' ? null : ($status === 'half_day' ? '12:00:00' : '19:15:00'),
                        'marked_by' => $markedBy,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $cursor->addDay();
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('coach_attendances')->insertOrIgnore($chunk);
        }
    }

    private function seedLeaveRequests(?int $approvedBy): void
    {
        foreach (Student::active()->inRandomOrder()->limit(14)->get() as $student) {
            $this->createLeave($student, $approvedBy);
        }

        foreach (Coach::inRandomOrder()->limit(5)->get() as $coach) {
            $this->createLeave($coach, $approvedBy);
        }
    }

    private function createLeave($model, ?int $approvedBy): void
    {
        $from = Carbon::today()->addDays(random_int(-30, 20));
        $to = $from->copy()->addDays(random_int(0, 5));

        $status = $this->pick(['pending', 'approved', 'approved', 'rejected']);

        LeaveRequest::create([
            'leavable_type' => $model::class,
            'leavable_id' => $model->id,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'type' => $this->pick(['sick', 'personal', 'travel', 'exam', 'other']),
            'reason' => $this->pick([
                'Family function out of town',
                'Down with viral fever',
                'School examinations',
                'Travelling for a wedding',
                'Medical appointment',
            ]),
            'status' => $status,
            'approved_by' => $status === 'pending' ? null : $approvedBy,
            'approved_at' => $status === 'pending' ? null : Carbon::now()->subDays(random_int(1, 10)),
            'rejection_reason' => $status === 'rejected' ? 'Clashes with tournament selection week' : null,
        ]);
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }
}
