<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\Student;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $teams = Team::query()
            ->with(['tournament:id,name', 'coach:id,first_name,last_name'])
            ->withCount('students')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.teams.index', compact('teams'));
    }

    public function create()
    {
        return view('admin.teams.form', [
            'team' => new Team(['status' => 'active']),
            'tournaments' => Tournament::orderBy('name')->get(),
            'coaches' => Coach::active()->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $team = Team::create($this->validated($request));

        return redirect()->route('admin.teams.show', $team)->with('success', 'Team created.');
    }

    public function show(Team $team)
    {
        $team->load(['tournament', 'coach', 'students', 'matches' => fn ($q) => $q->orderByDesc('match_date')->limit(10)]);

        // Squad-eligible students not already picked.
        $available = Student::active()->approved()
            ->whereDoesntHave('teams', fn ($q) => $q->where('teams.id', $team->id))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'student_code', 'playing_role']);

        $usedJerseys = $team->students->pluck('pivot.jersey_number')->filter()->all();

        return view('admin.teams.show', compact('team', 'available', 'usedJerseys'));
    }

    public function edit(Team $team)
    {
        return view('admin.teams.form', [
            'team' => $team,
            'tournaments' => Tournament::orderBy('name')->get(),
            'coaches' => Coach::active()->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $team->update($this->validated($request, $team));

        return redirect()->route('admin.teams.show', $team)->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        $team->delete();

        return redirect()->route('admin.teams.index')->with('success', 'Team deleted.');
    }

    public function addPlayer(Request $request, Team $team)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'jersey_number' => 'nullable|integer|min:0|max:999',
            'role' => 'nullable|in:'.implode(',', array_keys(\App\Models\Student::PLAYING_ROLES)),
            'is_captain' => 'nullable|boolean',
            'is_vice_captain' => 'nullable|boolean',
        ]);

        if ($team->students()->where('students.id', $data['student_id'])->exists()) {
            return back()->with('error', 'That player is already in this squad.');
        }

        // jersey_number is unique per team at the database level.
        if (! empty($data['jersey_number'])) {
            $taken = $team->students()->wherePivot('jersey_number', $data['jersey_number'])->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'jersey_number' => 'Jersey number '.$data['jersey_number'].' is already taken in this squad.',
                ]);
            }
        }

        $isCaptain = (bool) ($data['is_captain'] ?? false);
        $isVice = (bool) ($data['is_vice_captain'] ?? false);

        // Only one captain / vice-captain per squad.
        if ($isCaptain) {
            $team->students()->newPivotQuery()->where('is_captain', true)->update(['is_captain' => false]);
        }
        if ($isVice) {
            $team->students()->newPivotQuery()->where('is_vice_captain', true)->update(['is_vice_captain' => false]);
        }

        $team->students()->attach($data['student_id'], [
            'jersey_number' => $data['jersey_number'] ?? null,
            'role' => $data['role'] ?? null,
            'is_captain' => $isCaptain,
            'is_vice_captain' => $isVice,
        ]);

        return back()->with('success', 'Player added to squad.');
    }

    public function removePlayer(Team $team, Student $student)
    {
        $team->students()->detach($student->id);

        return back()->with('success', 'Player removed from squad.');
    }

    private function validated(Request $request, ?Team $team = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'tournament_id' => 'nullable|exists:tournaments,id',
            'coach_id' => 'nullable|exists:coaches,id',
            'age_group' => 'required|in:under_10,under_12,under_14,under_16,under_19,senior,open',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
    }
}
