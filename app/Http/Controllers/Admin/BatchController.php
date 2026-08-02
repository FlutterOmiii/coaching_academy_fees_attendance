<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Coach;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $batches = Batch::query()
            ->with('coach:id,first_name,last_name')
            ->withCount(['students as enrolled' => fn ($q) => $q->where('batch_student.status', 'active')])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('age_group'), fn ($q) => $q->where('age_group', $request->age_group))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.batches.index', compact('batches'));
    }

    public function create()
    {
        return view('admin.batches.form', [
            'batch' => new Batch(['status' => 'active', 'capacity' => 20, 'start_date' => now()->toDateString()]),
            'coaches' => Coach::active()->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $batch = Batch::create($this->validated($request));

        // Mirror the head coach into the pivot so batch_coach is the single
        // source of truth for "who coaches this batch".
        if ($batch->coach_id) {
            $batch->coaches()->syncWithoutDetaching([
                $batch->coach_id => ['role' => 'head', 'assigned_on' => $batch->start_date],
            ]);
        }

        return redirect()->route('admin.batches.show', $batch)->with('success', "Batch {$batch->code} created.");
    }

    public function show(Batch $batch)
    {
        $batch->load(['coach', 'coaches']);

        $students = $batch->activeStudents()->orderBy('first_name')->paginate(20);

        $attendance = $batch->attendances()->where('attendance_date', '>=', now()->subDays(30));
        $attendanceRate = \App\Models\StudentAttendance::percentageFor($attendance);

        return view('admin.batches.show', [
            'batch' => $batch,
            'students' => $students,
            'attendanceRate' => $attendanceRate,
            'upcomingSessions' => $batch->trainingSessions()->upcoming()->limit(5)->get(),
            // Students not already enrolled here, for the add-student picker.
            'available' => Student::active()->approved()
                ->whereDoesntHave('batches', fn ($q) => $q->where('batches.id', $batch->id)->where('batch_student.status', 'active'))
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'student_code']),
        ]);
    }

    public function edit(Batch $batch)
    {
        return view('admin.batches.form', [
            'batch' => $batch,
            'coaches' => Coach::active()->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, Batch $batch)
    {
        $batch->update($this->validated($request, $batch));

        if ($batch->coach_id) {
            $batch->coaches()->syncWithoutDetaching([
                $batch->coach_id => ['role' => 'head', 'assigned_on' => $batch->start_date],
            ]);
        }

        return redirect()->route('admin.batches.show', $batch)->with('success', 'Batch updated.');
    }

    /**
     * Soft-delete a batch, close out its roster and drop its future schedule.
     *
     * Enrolled students are marked as having left rather than being left
     * pointing at a batch that no longer exists. Sessions that have not
     * happened yet are removed because they never will; completed ones stay
     * as history, along with attendance, invoices and transfers.
     */
    public function destroy(Batch $batch)
    {
        $enrolled = $batch->activeStudents()->count();

        $upcoming = $batch->trainingSessions()
            ->whereDate('session_date', '>=', today())
            ->where('status', 'scheduled')
            ->count();

        DB::transaction(function () use ($batch) {
            // Close the roster in one statement rather than through the pivot
            // helper, which would need the ids resolved up front anyway.
            DB::table('batch_student')
                ->where('batch_id', $batch->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'left',
                    'left_on' => now()->toDateString(),
                    'updated_at' => now(),
                ]);

            $batch->trainingSessions()
                ->whereDate('session_date', '>=', today())
                ->where('status', 'scheduled')
                ->delete();

            $batch->delete();
        });

        $parts = ["Batch {$batch->code} deleted."];

        if ($enrolled > 0) {
            $parts[] = "{$enrolled} student(s) released — reassign them from the Students page.";
        }

        if ($upcoming > 0) {
            $parts[] = "{$upcoming} upcoming session(s) cancelled.";
        }

        return redirect()->route('admin.batches.index')->with('success', implode(' ', $parts));
    }

    public function toggleStatus(Batch $batch)
    {
        $batch->update(['status' => $batch->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', "Batch marked {$batch->status}.");
    }

    public function addStudent(Request $request, Batch $batch)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);

        if ($batch->available_seats < 1) {
            return back()->with('error', 'This batch is already at full capacity.');
        }

        $exists = $batch->students()->where('students.id', $request->student_id)
            ->wherePivot('status', 'active')->exists();

        if ($exists) {
            return back()->with('error', 'Student is already enrolled in this batch.');
        }

        $batch->students()->attach($request->student_id, [
            'joined_on' => now()->toDateString(),
            'status' => 'active',
        ]);

        return back()->with('success', 'Student added to batch.');
    }

    public function removeStudent(Batch $batch, Student $student)
    {
        $batch->students()->updateExistingPivot($student->id, [
            'status' => 'left',
            'left_on' => now()->toDateString(),
        ]);

        return back()->with('success', 'Student removed from batch.');
    }

    private function validated(Request $request, ?Batch $batch = null): array
    {
        $unique = $batch ? ','.$batch->id : '';

        // Coaches may edit a batch but never see or set its fee, so the field
        // is only validated for finance roles. Anyone else simply leaves the
        // stored value untouched.
        $canSeeFinance = (bool) auth('admin')->user()?->hasAbility('finance.view');

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:batches,code'.$unique,
            'coach_id' => 'nullable|exists:coaches,id',
            'age_group' => 'required|in:under_10,under_12,under_14,under_16,under_19,senior,open',
            'skill_level' => 'required|in:beginner,intermediate,advanced,professional',
            'capacity' => 'required|integer|min:1|max:200',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'training_days' => 'required|array|min:1',
            'training_days.*' => 'integer|between:0,6',
            'ground' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,completed',
        ];

        if ($canSeeFinance) {
            $rules['monthly_fee'] = 'required|numeric|min:0';
        }

        $data = $request->validate($rules);

        // Store as integers so the JSON cast round-trips cleanly.
        $data['training_days'] = array_map('intval', $data['training_days']);

        // A new batch still needs a fee value; default it rather than fail.
        if (! $canSeeFinance && ! $batch) {
            $data['monthly_fee'] = 0;
        }

        return $data;
    }
}
