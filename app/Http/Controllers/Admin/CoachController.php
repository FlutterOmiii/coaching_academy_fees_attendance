<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoachController extends Controller
{
    public function index(Request $request)
    {
        $coaches = Coach::query()
            ->withCount(['batches as batch_count' => fn ($q) => $q->where('status', 'active')])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('specialization'), fn ($q) => $q->where('specialization', $request->specialization))
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.coaches.index', compact('coaches'));
    }

    public function create()
    {
        return view('admin.coaches.form', [
            'coach' => new Coach(['joining_date' => now()->toDateString(), 'status' => 'active']),
            'nextCode' => Coach::nextCode(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['coach_code'] = Coach::nextCode();

        if ($request->hasFile('photo')) {
            $data['photo'] = StorageHelper::upload($request->file('photo'), 'coaches');
        }

        $coach = Coach::create($data);

        return redirect()
            ->route('admin.coaches.show', $coach)
            ->with('success', "Coach {$coach->coach_code} added.");
    }

    public function show(Coach $coach)
    {
        $coach->load(['batches.activeStudents:id', 'availabilities', 'admin']);

        $attendance = $coach->attendances();
        $stats = [
            'total' => (clone $attendance)->count(),
            'present' => (clone $attendance)->present()->count(),
        ];
        $stats['percentage'] = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0;

        // Availability keyed by day for the weekly grid.
        $availability = $coach->availabilities->groupBy('day_of_week');

        return view('admin.coaches.show', [
            'coach' => $coach,
            'stats' => $stats,
            'availability' => $availability,
            'recentAttendance' => $coach->attendances()->latest('attendance_date')->limit(10)->get(),
        ]);
    }

    public function edit(Coach $coach)
    {
        return view('admin.coaches.form', ['coach' => $coach, 'nextCode' => $coach->coach_code]);
    }

    public function update(Request $request, Coach $coach)
    {
        $data = $this->validated($request, $coach);

        if ($request->hasFile('photo')) {
            StorageHelper::delete($coach->photo);
            $data['photo'] = StorageHelper::upload($request->file('photo'), 'coaches');
        }

        $coach->update($data);

        return redirect()->route('admin.coaches.show', $coach)->with('success', 'Coach details updated.');
    }

    public function destroy(Coach $coach)
    {
        $coach->delete();

        return redirect()->route('admin.coaches.index')->with('success', 'Coach removed.');
    }

    public function toggleStatus(Coach $coach)
    {
        $coach->update(['status' => $coach->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', "Coach marked {$coach->status}.");
    }

    /** Replace the coach's weekly availability grid. */
    public function updateAvailability(Request $request, Coach $coach)
    {
        $request->validate([
            'slots' => 'array',
            'slots.*.day_of_week' => 'required|integer|between:0,6',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time' => 'required|date_format:H:i|after:slots.*.start_time',
        ]);

        DB::transaction(function () use ($request, $coach) {
            $coach->availabilities()->delete();

            foreach ($request->input('slots', []) as $slot) {
                $coach->availabilities()->create([
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'is_available' => true,
                ]);
            }
        });

        return back()->with('success', 'Availability updated.');
    }

    private function validated(Request $request, ?Coach $coach = null): array
    {
        $unique = $coach ? ',' . $coach->id : '';

        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'required|in:male,female,other',
            'photo' => 'nullable|image|max:2048',
            'email' => 'nullable|email|max:255|unique:coaches,email'.$unique,
            'phone' => 'required|string|max:20',
            'alt_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'specialization' => 'required|in:batting,bowling,fielding,wicket_keeping,fitness,all_round',
            'qualification' => 'nullable|string|max:255',
            'certification_level' => 'nullable|string|max:255',
            'experience_years' => 'required|integer|min:0|max:60',
            'joining_date' => 'required|date',
            'monthly_salary' => 'nullable|numeric|min:0',
            'bio' => 'nullable|string',
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        // One Full Name field on the form → first/last columns in the DB.
        $data = $this->splitFullName($data['full_name']) + $data;
        unset($data['full_name']);

        return $data;
    }
}
