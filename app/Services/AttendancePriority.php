<?php

namespace App\Services;

use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Regular Student Priority — orders the attendance-taking sheet so the students
 * most likely to be present sit at the top, cutting the coach's taps.
 *
 * The order is derived purely from recent attendance history and NEVER touches
 * the student's registration/order. It is recomputed on every load, so it keeps
 * up as habits change: a student who starts attending regularly rises, one who
 * starts skipping drifts down.
 *
 * Scoring is a recency-weighted present ratio: recent sessions count for more,
 * so consistent recent attendance beats a good-but-stale record. Students with
 * no history are never penalised — they sit just below those with history.
 *
 * One query fetches the whole window for every student in scope; the maths runs
 * in PHP. No per-student queries.
 */
class AttendancePriority
{
    /** How far back the regularity signal looks. */
    public const WINDOW_DAYS = 56; // 8 weeks

    /** Weekly decay: a session ~1 week old counts ~10% less than today's. */
    private const WEEKLY_DECAY = 0.9;

    /**
     * Return the students ordered by regularity (most regular first, then less
     * regular, then no-history), for a session on $asOf. Only history strictly
     * before $asOf is considered, so re-marking a day never feeds on itself.
     *
     * @param  Collection<int,\App\Models\Student>  $students
     * @param  int|string|null  $batchId  a batch id to scope history to, or 'all'/null for every batch
     */
    public static function order(Collection $students, int|string|null $batchId, Carbon $asOf): Collection
    {
        if ($students->isEmpty()) {
            return $students;
        }

        $scoped = $batchId !== null && $batchId !== 'all';

        $records = StudentAttendance::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('attendance_date', '<', $asOf->toDateString())
            ->whereDate('attendance_date', '>=', $asOf->copy()->subDays(self::WINDOW_DAYS)->toDateString())
            ->when($scoped, fn ($q) => $q->where('batch_id', $batchId))
            ->get(['student_id', 'attendance_date', 'status']);

        $byStudent = $records->groupBy('student_id');

        $rows = $students->map(function ($student) use ($byStudent, $asOf) {
            [$score, $count, $last] = self::score($byStudent->get($student->id) ?? collect(), $asOf);

            return [
                'student' => $student,
                'score' => $score,      // null = no history
                'count' => $count,
                'last' => $last,        // Y-m-d of most recent mark
                'name' => mb_strtolower($student->full_name),
            ];
        })->all();

        usort($rows, function ($a, $b) {
            // No-history students always sink below anyone with history.
            $an = $a['score'] === null;
            $bn = $b['score'] === null;
            if ($an !== $bn) {
                return $an ? 1 : -1;
            }

            if (! $an) {
                if ($a['score'] !== $b['score']) {
                    return $b['score'] <=> $a['score'];        // higher regularity first
                }
                if ($a['count'] !== $b['count']) {
                    return $b['count'] <=> $a['count'];        // more history = more confidence
                }
                if ($a['last'] !== $b['last']) {
                    return ($b['last'] ?? '') <=> ($a['last'] ?? ''); // attended more recently
                }
            }

            return $a['name'] <=> $b['name'];                  // stable, human-friendly tiebreak
        });

        return collect(array_map(fn ($r) => $r['student'], $rows));
    }

    /**
     * Recency-weighted present ratio for one student's recent records.
     *
     * @return array{0: float|null, 1: int, 2: string|null} [score 0-100 or null, record count, last date]
     */
    private static function score(Collection $records, Carbon $asOf): array
    {
        if ($records->isEmpty()) {
            return [null, 0, null];
        }

        $weightedPresent = 0.0;
        $weightedTotal = 0.0;
        $last = null;

        foreach ($records as $record) {
            $date = $record->attendance_date instanceof Carbon
                ? $record->attendance_date
                : Carbon::parse($record->attendance_date);

            $daysAgo = max(0, $date->diffInDays($asOf));
            $weight = self::WEEKLY_DECAY ** ($daysAgo / 7);

            $present = in_array($record->status, StudentAttendance::PRESENT_STATUSES, true) ? 1 : 0;

            $weightedPresent += $weight * $present;
            $weightedTotal += $weight;

            $ds = $date->toDateString();
            if ($last === null || $ds > $last) {
                $last = $ds;
            }
        }

        $score = $weightedTotal > 0 ? round(($weightedPresent / $weightedTotal) * 100, 2) : null;

        return [$score, $records->count(), $last];
    }
}
