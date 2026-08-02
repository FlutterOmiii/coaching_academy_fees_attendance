<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Coach;
use App\Models\CricketMatch;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\StudentAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Report definitions: slug => [title, description] */
    private const REPORTS = [
        'students' => ['Student Report', 'All students with batch, guardian and admission details'],
        'attendance' => ['Attendance Report', 'Monthly attendance percentages per student'],
        'fees' => ['Fee Collection Report', 'Payments collected and outstanding balances'],
        'coaches' => ['Coach Report', 'Coaching staff with batches and attendance'],
        'matches' => ['Match & Tournament Report', 'Fixtures, results and margins'],
        'performance' => ['Performance Report', 'Career batting and bowling statistics'],
    ];

    public function index()
    {
        return view('admin.reports.index', [
            'reports' => self::REPORTS,
            'batches' => Batch::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function generate(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::REPORTS), 404);

        $format = $request->input('format', 'pdf');
        abort_unless(in_array($format, ['pdf', 'csv'], true), 422);

        [$title, $columns, $rows, $meta] = $this->build($type, $request);

        return $format === 'csv'
            ? $this->csv($type, $columns, $rows)
            : $this->pdf($type, $title, $columns, $rows, $meta);
    }

    /**
     * @return array{0:string,1:array,2:\Illuminate\Support\Collection,3:string}
     */
    private function build(string $type, Request $request): array
    {
        return match ($type) {
            'students' => $this->students($request),
            'attendance' => $this->attendance($request),
            'fees' => $this->fees($request),
            'coaches' => $this->coaches($request),
            'matches' => $this->matches($request),
            'performance' => $this->performance($request),
        };
    }

    private function students(Request $request): array
    {
        $rows = Student::query()
            ->with('activeBatches:id,name')
            ->when($request->filled('batch_id'), fn ($q) => $q->whereHas(
                'batches',
                fn ($b) => $b->where('batches.id', $request->batch_id)->where('batch_student.status', 'active')
            ))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('student_code')
            ->get()
            ->map(fn ($s) => [
                $s->student_code,
                $s->full_name,
                $s->age,
                ucfirst($s->gender),
                $s->playing_role_label,
                $s->activeBatches->pluck('name')->implode(', ') ?: '—',
                $s->guardian_name,
                $s->guardian_phone,
                $s->admission_date?->format('d M Y'),
                ucfirst($s->status),
            ]);

        return [
            'Student Report',
            ['Code', 'Name', 'Age', 'Gender', 'Role', 'Batch', 'Guardian', 'Phone', 'Admission', 'Status'],
            $rows,
            $rows->count().' students',
        ];
    }

    private function attendance(Request $request): array
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $query = StudentAttendance::query()
            ->join('students', 'students.id', '=', 'student_attendances.student_id')
            ->when($request->filled('batch_id'), fn ($q) => $q->where('student_attendances.batch_id', $request->batch_id))
            ->whereYear('attendance_date', $month->year)
            ->whereMonth('attendance_date', $month->month)
            ->groupBy('students.id', 'students.student_code', 'students.first_name', 'students.last_name')
            // status exists on both tables, so it must be qualified.
            ->selectRaw("students.student_code, CONCAT(students.first_name,' ',students.last_name) name,
                         COUNT(*) total,
                         SUM(student_attendances.status='present') present,
                         SUM(student_attendances.status='late') late,
                         SUM(student_attendances.status='absent') absent,
                         SUM(student_attendances.status='excused') excused")
            ->orderBy('students.first_name');

        $rows = $query->get()->map(function ($r) {
            $attended = $r->present + $r->late;
            $pct = $r->total > 0 ? round(($attended / $r->total) * 100, 1) : 0;

            return [$r->student_code, $r->name, $r->total, $r->present, $r->late, $r->absent, $r->excused, $pct.'%'];
        });

        return [
            'Attendance Report — '.$month->format('F Y'),
            ['Code', 'Name', 'Marked', 'Present', 'Late', 'Absent', 'Excused', 'Attendance %'],
            $rows,
            $month->format('F Y').' · '.$rows->count().' students',
        ];
    }

    private function fees(Request $request): array
    {
        $rows = FeeInvoice::query()
            ->with(['student:id,first_name,last_name,student_code', 'batch:id,name'])
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->batch_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('month'), function ($q) use ($request) {
                $m = Carbon::createFromFormat('Y-m', $request->month);
                $q->whereYear('billing_period', $m->year)->whereMonth('billing_period', $m->month);
            })
            ->orderBy('due_date')
            ->get()
            ->map(fn ($i) => [
                $i->invoice_no,
                $i->student?->full_name,
                $i->batch?->name ?? '—',
                $i->period_label,
                number_format($i->total_amount, 2),
                number_format($i->paid_amount, 2),
                number_format($i->balance_amount, 2),
                $i->due_date->format('d M Y'),
                ucfirst($i->status),
            ]);

        $collected = FeePayment::completed()->sum('amount');
        $pending = FeeInvoice::outstanding()->sum('balance_amount');

        return [
            'Fee Collection Report',
            ['Invoice', 'Student', 'Batch', 'Period', 'Total', 'Paid', 'Balance', 'Due', 'Status'],
            $rows,
            'Collected all-time: '.number_format($collected, 2).' · Outstanding: '.number_format($pending, 2),
        ];
    }

    private function coaches(Request $request): array
    {
        $rows = Coach::query()
            ->withCount(['batches as batch_count' => fn ($q) => $q->where('status', 'active')])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('first_name')
            ->get()
            ->map(fn ($c) => [
                $c->coach_code,
                $c->full_name,
                $c->specialization_label,
                $c->certification_level ?: '—',
                $c->experience_years.' yrs',
                $c->batch_count,
                $c->phone,
                $c->joining_date?->format('d M Y'),
                ucfirst(str_replace('_', ' ', $c->status)),
            ]);

        return [
            'Coach Report',
            ['Code', 'Name', 'Specialisation', 'Certification', 'Experience', 'Batches', 'Phone', 'Joined', 'Status'],
            $rows,
            $rows->count().' coaches',
        ];
    }

    private function matches(Request $request): array
    {
        $rows = CricketMatch::query()
            ->with('tournament:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('match_date')
            ->get()
            ->map(fn ($m) => [
                $m->match_date->format('d M Y'),
                $m->opponent_name,
                $m->tournament?->name ?? 'Standalone',
                ucfirst($m->match_type),
                $m->status === 'completed' ? $m->academy_score : '—',
                $m->status === 'completed' ? $m->opponent_score : '—',
                $m->result ? CricketMatch::RESULTS[$m->result] : '—',
                $m->win_margin ?: '—',
            ]);

        $stats = CricketMatch::completed()
            ->selectRaw("COUNT(*) played, SUM(result='won') won, SUM(result='lost') lost")
            ->first();

        return [
            'Match & Tournament Report',
            ['Date', 'Opponent', 'Tournament', 'Type', 'Academy', 'Opponent Score', 'Result', 'Margin'],
            $rows,
            "Played {$stats->played} · Won {$stats->won} · Lost {$stats->lost}",
        ];
    }

    private function performance(Request $request): array
    {
        $rows = DB::table('match_performances as mp')
            ->join('students as s', 's.id', '=', 'mp.student_id')
            ->whereNull('s.deleted_at')
            ->groupBy('s.id', 's.student_code', 's.first_name', 's.last_name')
            ->selectRaw("s.student_code, CONCAT(s.first_name,' ',s.last_name) name,
                         COUNT(*) innings, SUM(mp.runs_scored) runs, SUM(mp.balls_faced) balls,
                         MAX(mp.runs_scored) best, SUM(mp.wickets) wickets,
                         SUM(mp.overs_bowled) overs, SUM(mp.runs_conceded) conceded, SUM(mp.catches) catches")
            ->orderByDesc('runs')
            ->get()
            ->map(fn ($r) => [
                $r->student_code,
                $r->name,
                $r->innings,
                $r->runs,
                $r->best,
                $r->balls > 0 ? round(($r->runs / $r->balls) * 100, 1) : 0,
                $r->wickets,
                $r->overs > 0 ? round($r->conceded / $r->overs, 2) : '—',
                $r->catches,
            ]);

        return [
            'Performance Report',
            ['Code', 'Name', 'Innings', 'Runs', 'Best', 'Strike Rate', 'Wickets', 'Economy', 'Catches'],
            $rows,
            $rows->count().' players with recorded performances',
        ];
    }

    private function csv(string $type, array $columns, $rows)
    {
        $filename = $type.'-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function pdf(string $type, string $title, array $columns, $rows, string $meta)
    {
        $pdf = Pdf::loadView('admin.reports.pdf', [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'meta' => $meta,
            'academy' => \App\Models\Setting::get('academy_name', 'Cricket Academy'),
            'address' => \App\Models\Setting::get('academy_address', ''),
        ])->setPaper('a4', count($columns) > 8 ? 'landscape' : 'portrait');

        return $pdf->download($type.'-report-'.now()->format('Y-m-d').'.pdf');
    }
}
