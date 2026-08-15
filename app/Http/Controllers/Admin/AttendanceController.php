<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Coach;
use App\Models\CoachAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TrainingSession;
use App\Services\AttendancePriority;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Marking sheet. Defaults to "All Students" — every active student across
     * all batches on one sheet — with the option to narrow to a single batch.
     */
    public function index(Request $request)
    {
        $batches = Batch::active()->orderBy('name')->get();
        // "all" is the default so the sheet opens with everyone.
        $batchId = $request->input('batch_id', 'all');
        $date = $request->date('date') ?? Carbon::today();

        $allMode = $batchId === 'all';
        $batch = null;
        $students = collect();
        $existing = collect();
        $isTrainingDay = true;

        if ($allMode) {
            // Every active, approved student who belongs to a batch — the batch
            // is what the mark is attributed to.
            $students = Student::active()->approved()
                ->with('activeBatches:id,name')
                ->get()
                ->filter(fn ($s) => $s->activeBatches->isNotEmpty())
                ->values();

            // Existing marks on this date, keyed by student (one batch each in
            // practice); used to pre-fill the sheet.
            $existing = StudentAttendance::whereDate('attendance_date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');
        } elseif ($batchId) {
            $batch = Batch::with('activeStudents')->find($batchId);

            if ($batch) {
                $students = $batch->activeStudents()->get();

                $existing = StudentAttendance::where('batch_id', $batch->id)
                    ->whereDate('attendance_date', $date)
                    ->get()
                    ->keyBy('student_id');

                // Warn when marking a day the batch does not normally train.
                $isTrainingDay = in_array($date->dayOfWeek, $batch->training_days ?? [], true);
            }
        }

        // Regular Student Priority — most-likely-to-attend first. Screen order
        // only; the students' registration order is never touched.
        $students = AttendancePriority::order($students, $batchId, $date instanceof Carbon ? $date : Carbon::parse($date));

        return view('admin.attendance.index', [
            'batches' => $batches,
            'batch' => $batch,
            'batchId' => $batchId,
            'allMode' => $allMode,
            'students' => $students,
            'existing' => $existing,
            'date' => $date,
            'isTrainingDay' => $isTrainingDay,
        ]);
    }

    public function store(Request $request)
    {
        $allMode = $request->input('batch_id') === 'all';

        $rules = [
            'attendance_date' => 'required|date|before_or_equal:today',
            'attendance' => 'required|array|min:1',
            // Two states. Anyone not explicitly Present is saved Absent, so an
            // empty/unmarked value is allowed and coerced to absent below.
            'attendance.*' => 'nullable|in:present,absent',
            'remarks' => 'array',
        ];

        if ($allMode) {
            // In all-students mode each row carries the batch to attribute to.
            $rules['student_batch'] = 'required|array';
            $rules['student_batch.*'] = 'required|exists:batches,id';
        } else {
            $rules['batch_id'] = 'required|exists:batches,id';
        }

        $data = $request->validate($rules);

        $date = Carbon::parse($data['attendance_date']);
        $adminId = auth('admin')->id();
        $marked = 0;

        DB::transaction(function () use ($data, $date, $request, $allMode, $adminId, &$marked) {
            // Cache one session lookup per batch so we don't query per student.
            $sessionByBatch = [];

            foreach ($data['attendance'] as $studentId => $status) {
                // Present only when explicitly tapped; everyone else is Absent.
                $status = $status === 'present' ? 'present' : 'absent';

                $batchId = $allMode
                    ? ($request->input("student_batch.{$studentId}"))
                    : $data['batch_id'];

                if (! $batchId) {
                    continue;
                }

                if (! array_key_exists($batchId, $sessionByBatch)) {
                    $sessionByBatch[$batchId] = TrainingSession::where('batch_id', $batchId)
                        ->whereDate('session_date', $date)
                        ->value('id');
                }

                // updateOrCreate on the (student, batch, date) unique key makes
                // this idempotent — re-saving edits the same row, never a duplicate.
                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'batch_id' => $batchId,
                        'attendance_date' => $date->toDateString(),
                    ],
                    [
                        'training_session_id' => $sessionByBatch[$batchId],
                        'status' => $status,
                        'check_in' => $status === 'present' ? now()->format('H:i:s') : null,
                        'remarks' => $request->input("remarks.{$studentId}"),
                        'marked_by' => $adminId,
                    ]
                );

                $marked++;
            }
        });

        // Straight into the daily chart: Present first (green), Absent after (red).
        return redirect()
            ->route('admin.attendance.daily', ['batch_id' => $request->input('batch_id'), 'date' => $date->toDateString()])
            ->with('success', "Attendance saved — {$marked} students marked for ".$date->format('d M Y').'.');
    }

    /**
     * Daily chart for one date: Present students first (green), Absent after
     * (red), never mixed. This is where saving lands.
     */
    public function daily(Request $request)
    {
        $batches = Batch::active()->orderBy('name')->get();
        $batchId = $request->input('batch_id', 'all');
        $date = $request->date('date') ?? Carbon::today();
        $allMode = $batchId === 'all';

        $records = StudentAttendance::query()
            ->with(['student:id,first_name,last_name,student_code,photo', 'batch:id,name'])
            ->whereDate('attendance_date', $date)
            ->when(! $allMode, fn ($q) => $q->where('batch_id', $batchId))
            ->get()
            ->filter(fn ($r) => $r->student !== null);

        $isPresent = fn ($r) => in_array($r->status, StudentAttendance::PRESENT_STATUSES, true);

        $present = $records->filter($isPresent)
            ->sortBy(fn ($r) => mb_strtolower($r->student->full_name))->values();
        $absent = $records->reject($isPresent)
            ->sortBy(fn ($r) => mb_strtolower($r->student->full_name))->values();

        return view('admin.attendance.daily', [
            'batches' => $batches,
            'batchId' => $batchId,
            'allMode' => $allMode,
            'date' => $date,
            'present' => $present,
            'absent' => $absent,
            'batch' => $allMode ? null : $batches->firstWhere('id', (int) $batchId),
        ]);
    }

    /** Monthly grid: one row per student, one column per day. */
    public function report(Request $request)
    {
        $batches = Batch::active()->orderBy('name')->get();
        $batchId = $request->input('batch_id', $batches->first()?->id);
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $batch = $batchId ? Batch::find($batchId) : null;
        $rows = collect();
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];

        if ($batch) {
            $students = $batch->activeStudents()->orderBy('first_name')->get();

            $records = StudentAttendance::where('batch_id', $batch->id)
                ->whereYear('attendance_date', $month->year)
                ->whereMonth('attendance_date', $month->month)
                ->get();

            foreach ($records as $record) {
                $summary[$record->status] = ($summary[$record->status] ?? 0) + 1;
            }

            // Index by student, then by day-of-month, for O(1) cell lookup.
            $byStudent = $records->groupBy('student_id');

            $rows = $students->map(function ($student) use ($byStudent) {
                $marks = ($byStudent[$student->id] ?? collect())
                    ->keyBy(fn ($r) => $r->attendance_date->day)
                    ->map->status;

                $total = $marks->count();
                $present = $marks->filter(fn ($s) => in_array($s, ['present', 'late'], true))->count();

                return [
                    'student' => $student,
                    'marks' => $marks,
                    'total' => $total,
                    'present' => $present,
                    'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                ];
            });
        }

        return view('admin.attendance.report', [
            'batches' => $batches,
            'batch' => $batch,
            'month' => $month,
            'rows' => $rows,
            'summary' => $summary,
            'daysInMonth' => $month->daysInMonth,
        ]);
    }

    public function coaches(Request $request)
    {
        $date = $request->date('date') ?? Carbon::today();

        $coaches = Coach::active()->orderBy('first_name')->get();
        $existing = CoachAttendance::whereDate('attendance_date', $date)->get()->keyBy('coach_id');

        return view('admin.attendance.coaches', compact('coaches', 'existing', 'date'));
    }

    public function storeCoaches(Request $request)
    {
        $data = $request->validate([
            'attendance_date' => 'required|date|before_or_equal:today',
            'attendance' => 'required|array|min:1',
            'attendance.*' => 'required|in:present,absent,half_day,leave',
        ]);

        $date = Carbon::parse($data['attendance_date']);

        DB::transaction(function () use ($data, $date, $request) {
            foreach ($data['attendance'] as $coachId => $status) {
                CoachAttendance::updateOrCreate(
                    ['coach_id' => $coachId, 'attendance_date' => $date->toDateString()],
                    [
                        'status' => $status,
                        'check_in' => $request->input("check_in.{$coachId}") ?: null,
                        'check_out' => $request->input("check_out.{$coachId}") ?: null,
                        'marked_by' => auth('admin')->id(),
                    ]
                );
            }
        });

        return back()->with('success', 'Coach attendance saved for '.$date->format('d M Y').'.');
    }
}
