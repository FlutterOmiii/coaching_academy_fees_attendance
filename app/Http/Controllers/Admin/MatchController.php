<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CricketMatch;
use App\Models\MatchPerformance;
use App\Models\Student;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $matches = CricketMatch::query()
            ->with(['tournament:id,name', 'team:id,name', 'manOfMatch:id,first_name,last_name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('result'), fn ($q) => $q->where('result', $request->result))
            ->when($request->filled('tournament_id'), fn ($q) => $q->where('tournament_id', $request->tournament_id))
            ->when($request->filled('search'), fn ($q) => $q->where('opponent_name', 'like', '%'.$request->search.'%'))
            ->orderByDesc('match_date')
            ->paginate(15)
            ->withQueryString();

        $stats = CricketMatch::completed()
            ->selectRaw("COUNT(*) played,
                         SUM(result = 'won') won,
                         SUM(result = 'lost') lost,
                         SUM(result IN ('tie','draw','no_result')) other")
            ->first();

        return view('admin.matches.index', [
            'matches' => $matches,
            'stats' => $stats,
            'tournaments' => Tournament::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('admin.matches.form', [
            'match' => new CricketMatch(['status' => 'scheduled', 'match_date' => now()->toDateString(), 'overs' => 20]),
            'tournaments' => Tournament::orderBy('name')->get(),
            'teams' => Team::active()->orderBy('name')->get(),
            'students' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $match = CricketMatch::create($this->validated($request));

        return redirect()->route('admin.matches.show', $match)->with('success', 'Match scheduled.');
    }

    public function show(CricketMatch $match)
    {
        $match->load([
            'tournament', 'team', 'manOfMatch',
            'performances.student:id,first_name,last_name,student_code,playing_role',
        ]);

        // Split the scorecard into the two disciplines for display.
        $batting = $match->performances->sortBy('batting_position');
        $bowling = $match->performances->filter(fn ($p) => (float) $p->overs_bowled > 0);

        return view('admin.matches.show', compact('match', 'batting', 'bowling'));
    }

    public function edit(CricketMatch $match)
    {
        return view('admin.matches.form', [
            'match' => $match,
            'tournaments' => Tournament::orderBy('name')->get(),
            'teams' => Team::active()->orderBy('name')->get(),
            'students' => $match->team
                ? $match->team->students()->orderBy('first_name')->get()
                : Student::active()->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, CricketMatch $match)
    {
        $match->update($this->validated($request, $match));

        return redirect()->route('admin.matches.show', $match)->with('success', 'Match updated.');
    }

    public function destroy(CricketMatch $match)
    {
        $match->delete();

        return redirect()->route('admin.matches.index')->with('success', 'Match deleted.');
    }

    /** Per-player scorecard entry for the squad. */
    public function scorecard(CricketMatch $match)
    {
        $match->load('team', 'performances');

        $squad = $match->team
            ? $match->team->students()->orderBy('first_name')->get()
            : Student::active()->approved()->orderBy('first_name')->limit(20)->get();

        return view('admin.matches.scorecard', [
            'match' => $match,
            'squad' => $squad,
            'existing' => $match->performances->keyBy('student_id'),
        ]);
    }

    public function saveScorecard(Request $request, CricketMatch $match)
    {
        $data = $request->validate([
            'players' => 'required|array',
            'players.*.include' => 'nullable|boolean',
            'players.*.batting_position' => 'nullable|integer|min:1|max:11',
            'players.*.runs_scored' => 'nullable|integer|min:0|max:999',
            'players.*.balls_faced' => 'nullable|integer|min:0|max:999',
            'players.*.fours' => 'nullable|integer|min:0|max:99',
            'players.*.sixes' => 'nullable|integer|min:0|max:99',
            'players.*.is_out' => 'nullable|boolean',
            'players.*.dismissal_type' => 'nullable|in:bowled,caught,lbw,run_out,stumped,hit_wicket,retired,not_out',
            'players.*.overs_bowled' => 'nullable|numeric|min:0|max:50',
            'players.*.maidens' => 'nullable|integer|min:0|max:50',
            'players.*.runs_conceded' => 'nullable|integer|min:0|max:500',
            'players.*.wickets' => 'nullable|integer|min:0|max:10',
            'players.*.catches' => 'nullable|integer|min:0|max:10',
            'players.*.run_outs' => 'nullable|integer|min:0|max:10',
            'players.*.stumpings' => 'nullable|integer|min:0|max:10',
            'players.*.rating' => 'nullable|numeric|min:0|max:10',
        ]);

        DB::transaction(function () use ($data, $match) {
            foreach ($data['players'] as $studentId => $row) {
                // Unticked players are dropped from the scorecard entirely.
                if (empty($row['include'])) {
                    MatchPerformance::where('cricket_match_id', $match->id)
                        ->where('student_id', $studentId)
                        ->delete();

                    continue;
                }

                MatchPerformance::updateOrCreate(
                    ['cricket_match_id' => $match->id, 'student_id' => $studentId],
                    [
                        'batting_position' => $row['batting_position'] ?? null,
                        'runs_scored' => $row['runs_scored'] ?? 0,
                        'balls_faced' => $row['balls_faced'] ?? 0,
                        'fours' => $row['fours'] ?? 0,
                        'sixes' => $row['sixes'] ?? 0,
                        'is_out' => (bool) ($row['is_out'] ?? false),
                        'dismissal_type' => $row['dismissal_type'] ?? null,
                        'overs_bowled' => $row['overs_bowled'] ?? 0,
                        'maidens' => $row['maidens'] ?? 0,
                        'runs_conceded' => $row['runs_conceded'] ?? 0,
                        'wickets' => $row['wickets'] ?? 0,
                        'catches' => $row['catches'] ?? 0,
                        'run_outs' => $row['run_outs'] ?? 0,
                        'stumpings' => $row['stumpings'] ?? 0,
                        'rating' => $row['rating'] ?? null,
                    ]
                );
            }
        });

        return redirect()->route('admin.matches.show', $match)->with('success', 'Scorecard saved.');
    }

    private function validated(Request $request, ?CricketMatch $match = null): array
    {
        return $request->validate([
            'tournament_id' => 'nullable|exists:tournaments,id',
            'team_id' => 'nullable|exists:teams,id',
            'opponent_name' => 'required|string|max:255',
            'match_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'venue' => 'nullable|string|max:255',
            'match_type' => 'required|in:practice,friendly,tournament,league,knockout,final',
            'overs' => 'nullable|integer|min:1|max:200',
            'toss_won_by' => 'nullable|in:academy,opponent',
            'toss_decision' => 'nullable|in:bat,bowl',
            'status' => 'required|in:scheduled,live,completed,cancelled,abandoned',
            'result' => 'nullable|in:won,lost,tie,draw,no_result',
            'win_margin' => 'nullable|string|max:100',
            'academy_runs' => 'nullable|integer|min:0|max:2000',
            'academy_wickets' => 'nullable|integer|min:0|max:10',
            'academy_overs' => 'nullable|numeric|min:0|max:200',
            'opponent_runs' => 'nullable|integer|min:0|max:2000',
            'opponent_wickets' => 'nullable|integer|min:0|max:10',
            'opponent_overs' => 'nullable|numeric|min:0|max:200',
            'man_of_match_id' => 'nullable|exists:students,id',
            'summary' => 'nullable|string',
        ]);
    }
}
