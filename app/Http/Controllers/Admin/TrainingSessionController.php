<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Coach;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrainingSessionController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from') ?? Carbon::today()->startOfWeek();
        $to = $request->date('to') ?? $from->copy()->addDays(13);

        $sessions = TrainingSession::query()
            // A deleted batch's sessions are not part of the schedule any more.
            // Without this the batch relation resolves to null and the view breaks.
            ->whereHas('batch')
            ->with(['batch:id,name,code', 'coach:id,first_name,last_name'])
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->batch_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->whereBetween('session_date', [$from, $to])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($s) => $s->session_date->toDateString());

        return view('admin.training.index', [
            'sessions' => $sessions,
            'batches' => Batch::active()->orderBy('name')->get(['id', 'name']),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.training.form', [
            'session' => new TrainingSession([
                'session_date' => $request->input('date', now()->toDateString()),
                'status' => 'scheduled',
            ]),
            'batches' => Batch::active()->orderBy('name')->get(),
            'coaches' => Coach::active()->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        TrainingSession::create($this->validated($request));

        return redirect()->route('admin.training.index')->with('success', 'Training session scheduled.');
    }

    public function show(TrainingSession $session)
    {
        $session->load(['batch.activeStudents', 'coach', 'attendances.student']);

        return view('admin.training.show', compact('session'));
    }

    public function edit(TrainingSession $session)
    {
        return view('admin.training.form', [
            'session' => $session,
            'batches' => Batch::active()->orderBy('name')->get(),
            'coaches' => Coach::active()->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, TrainingSession $session)
    {
        $session->update($this->validated($request, $session));

        return redirect()->route('admin.training.index')->with('success', 'Session updated.');
    }

    public function destroy(TrainingSession $session)
    {
        $session->delete();

        return back()->with('success', 'Session removed.');
    }

    private function validated(Request $request, ?TrainingSession $session = null): array
    {
        $data = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'coach_id' => 'nullable|exists:coaches,id',
            'title' => 'nullable|string|max:255',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'ground' => 'nullable|string|max:255',
            'focus_area' => 'required|in:batting,bowling,fielding,wicket_keeping,fitness,match_practice,general',
            'notes' => 'nullable|string',
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);

        // The schema enforces one session per batch/date/start_time — surface
        // that as a friendly validation error rather than a 500.
        $clash = TrainingSession::where('batch_id', $data['batch_id'])
            ->whereDate('session_date', $data['session_date'])
            ->where('start_time', $data['start_time'].':00')
            ->when($session, fn ($q) => $q->whereKeyNot($session->id))
            ->exists();

        if ($clash) {
            throw ValidationException::withMessages([
                'start_time' => 'This batch already has a session at that time on that date.',
            ]);
        }

        return $data;
    }
}
