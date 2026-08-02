<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function index(Request $request)
    {
        $tournaments = Tournament::query()
            ->withCount(['matches', 'teams'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderByDesc('start_date')
            ->paginate(12)
            ->withQueryString();

        return view('admin.tournaments.index', [
            'tournaments' => $tournaments,
            'counts' => [
                'upcoming' => Tournament::where('status', 'upcoming')->count(),
                'ongoing' => Tournament::where('status', 'ongoing')->count(),
                'completed' => Tournament::where('status', 'completed')->count(),
                'won' => Tournament::where('final_position', 'Winner')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.tournaments.form', [
            'tournament' => new Tournament(['status' => 'upcoming', 'start_date' => now()->toDateString()]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('banner')) {
            $data['banner'] = StorageHelper::upload($request->file('banner'), 'tournaments');
        }

        $tournament = Tournament::create($data);

        return redirect()->route('admin.tournaments.show', $tournament)->with('success', 'Tournament created.');
    }

    public function show(Tournament $tournament)
    {
        $tournament->load(['teams.coach', 'matches' => fn ($q) => $q->orderByDesc('match_date')]);

        $stats = $tournament->matches()
            ->where('status', 'completed')
            ->selectRaw("COUNT(*) played, SUM(result='won') won, SUM(result='lost') lost")
            ->first();

        return view('admin.tournaments.show', compact('tournament', 'stats'));
    }

    public function edit(Tournament $tournament)
    {
        return view('admin.tournaments.form', compact('tournament'));
    }

    public function update(Request $request, Tournament $tournament)
    {
        $data = $this->validated($request, $tournament);

        if ($request->hasFile('banner')) {
            StorageHelper::delete($tournament->banner);
            $data['banner'] = StorageHelper::upload($request->file('banner'), 'tournaments');
        }

        $tournament->update($data);

        return redirect()->route('admin.tournaments.show', $tournament)->with('success', 'Tournament updated.');
    }

    public function destroy(Tournament $tournament)
    {
        $tournament->delete();

        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament deleted.');
    }

    private function validated(Request $request, ?Tournament $tournament = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'venue' => 'nullable|string|max:255',
            'format' => 'required|in:t10,t20,odi,multi_day,custom',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'entry_fee' => 'nullable|numeric|min:0',
            'banner' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
            'final_position' => 'nullable|string|max:50',
        ]);
    }
}
