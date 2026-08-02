<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Coach;
use App\Models\CricketMatch;
use App\Models\Event;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates every figure shown on the academy dashboard. Kept out of the view
 * so the queries stay testable and the Blade template stays presentational.
 */
class DashboardMetrics
{
    /**
     * Headline counters with month-on-month movement.
     *
     * Financial figures are only computed when the caller is allowed to see
     * them, so a coach's dashboard response contains no money at all.
     */
    public function kpis(bool $withFinance = true): array
    {
        $now = Carbon::now();
        $lastMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $students = Student::active()->approved()->count();
        $studentsLastMonth = Student::approved()
            ->whereDate('admission_date', '<=', $lastMonthEnd)
            ->count();

        $admissionsThis = Student::whereYear('admission_date', $now->year)
            ->whereMonth('admission_date', $now->month)
            ->count();
        $admissionsLast = Student::whereYear('admission_date', $lastMonthEnd->year)
            ->whereMonth('admission_date', $lastMonthEnd->month)
            ->count();

        $kpis = [
            'total_students' => $students,
            'students_change' => $this->percentChange($students, $studentsLastMonth),
            'total_coaches' => Coach::active()->count(),
            'active_batches' => Batch::active()->count(),
            'today_attendance' => $this->todayAttendance(),
            'monthly_attendance_pct' => $this->monthlyAttendancePercentage(),
            'upcoming_matches' => CricketMatch::upcoming()->count(),
            'upcoming_events' => Event::upcoming()->count(),
            'monthly_admissions' => $admissionsThis,
            'admissions_change' => $this->percentChange($admissionsThis, $admissionsLast),
        ];

        if ($withFinance) {
            $collectionThis = $this->collectionFor($now);
            $collectionLast = $this->collectionFor($now->copy()->subMonthNoOverflow());

            $kpis += [
                'monthly_collection' => $collectionThis,
                'collection_change' => $this->percentChange($collectionThis, $collectionLast),
                'pending_fees' => (float) FeeInvoice::outstanding()->sum('balance_amount'),
                'overdue_invoices' => FeeInvoice::overdue()->count(),
            ];
        }

        return $kpis;
    }

    /** Secondary business-intelligence widgets. */
    public function widgets(): array
    {
        $totalStudents = Student::approved()->count();
        $activeStudents = Student::active()->approved()->count();

        $capacity = (int) Batch::active()->sum('capacity');
        $enrolled = (int) DB::table('batch_student')->where('status', 'active')->count();

        $activeCoaches = Coach::active()->count();
        $assignedCoaches = Batch::active()->whereNotNull('coach_id')->distinct()->count('coach_id');

        return [
            'retention_rate' => $totalStudents > 0
                ? round(($activeStudents / $totalStudents) * 100, 1)
                : 0.0,
            'batch_occupancy' => $capacity > 0
                ? round(min(100, ($enrolled / $capacity) * 100), 1)
                : 0.0,
            'coach_utilization' => $activeCoaches > 0
                ? round(min(100, ($assignedCoaches / $activeCoaches) * 100), 1)
                : 0.0,
            'tournament_participation' => Tournament::count(),
            'tournaments_won' => Tournament::where('final_position', 'Winner')->count(),
            'total_capacity' => $capacity,
            'total_enrolled' => $enrolled,
        ];
    }

    // ------------------------------------------------------------------ Charts

    /** Cumulative approved students at the end of each of the last 12 months. */
    public function studentGrowth(): array
    {
        $labels = [];
        $data = [];

        foreach ($this->lastMonths(12) as $month) {
            $labels[] = $month->format('M y');
            $data[] = Student::approved()
                ->whereDate('admission_date', '<=', $month->copy()->endOfMonth())
                ->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** New admissions per month for the last 12 months. */
    public function monthlyAdmissions(): array
    {
        $rows = Student::query()
            ->selectRaw("DATE_FORMAT(admission_date, '%Y-%m') AS ym, COUNT(*) AS total")
            ->where('admission_date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonths($rows, 12);
    }

    /** Collected vs still-outstanding money, by billing month. */
    public function revenueVsPending(): array
    {
        $collected = FeePayment::query()
            ->completed()
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total")
            ->where('payment_date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $pending = FeeInvoice::query()
            ->outstanding()
            ->selectRaw("DATE_FORMAT(billing_period, '%Y-%m') AS ym, SUM(balance_amount) AS total")
            ->where('billing_period', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $collectedSeries = $this->mapMonths($collected, 12);
        $pendingSeries = $this->mapMonths($pending, 12);

        return [
            'labels' => $collectedSeries['labels'],
            'collected' => $collectedSeries['data'],
            'pending' => $pendingSeries['data'],
        ];
    }

    /** This month's attendance split, for the donut chart. */
    public function attendanceBreakdown(): array
    {
        $rows = StudentAttendance::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->whereYear('attendance_date', Carbon::now()->year)
            ->whereMonth('attendance_date', Carbon::now()->month)
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = ['present', 'late', 'absent', 'excused'];

        return [
            'labels' => array_map(fn ($s) => StudentAttendance::STATUSES[$s], $statuses),
            'data' => array_map(fn ($s) => (int) ($rows[$s] ?? 0), $statuses),
        ];
    }

    /** Daily attendance percentage across the last 30 days. */
    public function attendanceTrend(): array
    {
        $rows = StudentAttendance::query()
            ->selectRaw('attendance_date, COUNT(*) AS total, SUM(status IN ("present","late")) AS present')
            ->where('attendance_date', '>=', Carbon::today()->subDays(29))
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();

        return [
            'labels' => $rows->map(fn ($r) => Carbon::parse($r->attendance_date)->format('d M'))->all(),
            'data' => $rows->map(fn ($r) => $r->total > 0 ? round(($r->present / $r->total) * 100, 1) : 0)->all(),
        ];
    }

    public function batchDistribution(): array
    {
        $batches = Batch::active()
            ->withCount(['students as active_count' => fn ($q) => $q->where('batch_student.status', 'active')])
            ->orderByDesc('active_count')
            ->get();

        return [
            'labels' => $batches->pluck('name')->all(),
            'data' => $batches->pluck('active_count')->all(),
            'capacity' => $batches->pluck('capacity')->all(),
        ];
    }

    public function coachDistribution(): array
    {
        $rows = DB::table('coaches')
            ->leftJoin('batches', function ($join) {
                $join->on('batches.coach_id', '=', 'coaches.id')->where('batches.status', 'active');
            })
            ->leftJoin('batch_student', function ($join) {
                $join->on('batch_student.batch_id', '=', 'batches.id')->where('batch_student.status', 'active');
            })
            ->whereNull('coaches.deleted_at')
            ->where('coaches.status', 'active')
            ->groupBy('coaches.id', 'coaches.first_name', 'coaches.last_name')
            ->selectRaw("CONCAT(coaches.first_name,' ',coaches.last_name) AS name, COUNT(batch_student.student_id) AS total")
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    public function matchStatistics(): array
    {
        $rows = CricketMatch::completed()
            ->selectRaw('result, COUNT(*) AS total')
            ->groupBy('result')
            ->pluck('total', 'result');

        $results = ['won', 'lost', 'tie', 'draw', 'no_result'];
        $labels = [];
        $data = [];

        foreach ($results as $result) {
            if (($rows[$result] ?? 0) > 0) {
                $labels[] = CricketMatch::RESULTS[$result];
                $data[] = (int) $rows[$result];
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function activeVsInactive(): array
    {
        return [
            'labels' => ['Active', 'Inactive'],
            'data' => [
                Student::active()->approved()->count(),
                Student::approved()->where('status', 'inactive')->count(),
            ],
        ];
    }

    // -------------------------------------------------------------- Lists

    public function upcomingMatches(int $limit = 5): Collection
    {
        return CricketMatch::upcoming()->with('team')->limit($limit)->get();
    }

    public function upcomingEvents(int $limit = 5): Collection
    {
        return Event::upcoming()->limit($limit)->get();
    }

    public function recentPayments(int $limit = 5): Collection
    {
        return FeePayment::completed()
            ->with('student:id,first_name,last_name,student_code')
            ->latest('payment_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** Students owing the most, for the follow-up list. */
    public function topDefaulters(int $limit = 5): Collection
    {
        return FeeInvoice::query()
            ->outstanding()
            ->select('student_id', DB::raw('SUM(balance_amount) AS due'), DB::raw('COUNT(*) AS invoices'))
            ->with('student:id,first_name,last_name,student_code,guardian_phone')
            ->groupBy('student_id')
            ->orderByDesc('due')
            ->limit($limit)
            ->get();
    }

    // ------------------------------------------------------------- Internals

    private function collectionFor(Carbon $month): float
    {
        return (float) FeePayment::completed()
            ->whereYear('payment_date', $month->year)
            ->whereMonth('payment_date', $month->month)
            ->sum('amount');
    }

    /**
     * Present + late as a share of everything marked today. Returns null when
     * no session has been marked yet, so the view can say so rather than show 0%.
     */
    private function todayAttendance(): array
    {
        $query = StudentAttendance::forDate(Carbon::today());
        $total = (clone $query)->count();
        $present = (clone $query)->present()->count();

        return [
            'present' => $present,
            'total' => $total,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : null,
        ];
    }

    private function monthlyAttendancePercentage(): float
    {
        return StudentAttendance::percentageFor(
            StudentAttendance::query()
                ->whereYear('attendance_date', Carbon::now()->year)
                ->whereMonth('attendance_date', Carbon::now()->month)
        );
    }

    /** Percentage movement from $previous to $current. */
    private function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** @return Carbon[] */
    private function lastMonths(int $count): array
    {
        return collect(range($count - 1, 0))
            ->map(fn ($i) => Carbon::now()->subMonthsNoOverflow($i)->startOfMonth())
            ->all();
    }

    /**
     * Turn a keyed "YYYY-MM" => value map into aligned label/data arrays,
     * filling gaps with zero so the chart has no holes.
     */
    private function mapMonths(Collection|array $rows, int $count): array
    {
        $rows = collect($rows);
        $labels = [];
        $data = [];

        foreach ($this->lastMonths($count) as $month) {
            $labels[] = $month->format('M y');
            $data[] = round((float) ($rows[$month->format('Y-m')] ?? 0), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
