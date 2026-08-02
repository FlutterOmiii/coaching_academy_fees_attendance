<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\Student;
use App\Models\StudentAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    /** Leaderboard of career stats across all matches played. */
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'runs');

        $column = match ($sort) {
            'wickets' => 'wickets',
            'catches' => 'catches',
            'matches' => 'matches',
            default => 'runs',
        };

        $leaders = Student::query()
            ->select('students.*')
            ->join('match_performances as mp', 'mp.student_id', '=', 'students.id')
            ->when($request->filled('search'), fn ($q) => $q->search($request->search))
            ->groupBy('students.id')
            ->selectRaw('COUNT(mp.id) matches, SUM(mp.runs_scored) runs, SUM(mp.balls_faced) balls,
                         SUM(mp.wickets) wickets, SUM(mp.catches) catches, MAX(mp.runs_scored) best,
                         SUM(mp.runs_conceded) conceded, SUM(mp.overs_bowled) overs')
            ->orderByDesc($column)
            ->paginate(20)
            ->withQueryString();

        $totals = DB::table('match_performances')
            ->selectRaw('SUM(runs_scored) runs, SUM(wickets) wickets, SUM(catches) catches, COUNT(*) innings')
            ->first();

        return view('admin.performance.index', compact('leaders', 'totals', 'sort'));
    }

    public function create(Request $request)
    {
        return view('admin.performance.form', [
            'students' => Student::active()->approved()->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'student_code']),
            'coaches' => Coach::active()->orderBy('first_name')->get(),
            'selectedStudent' => $request->input('student_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'coach_id' => 'nullable|exists:coaches,id',
            'assessment_date' => 'required|date|before_or_equal:today',
            'batting_rating' => 'required|integer|min:1|max:10',
            'bowling_rating' => 'required|integer|min:1|max:10',
            'fielding_rating' => 'required|integer|min:1|max:10',
            'fitness_rating' => 'required|integer|min:1|max:10',
            'discipline_rating' => 'required|integer|min:1|max:10',
            'strengths' => 'nullable|string',
            'improvements' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        StudentAssessment::create($data);

        return redirect()
            ->route('admin.performance.show', $data['student_id'])
            ->with('success', 'Assessment recorded.');
    }

    public function show(Student $student)
    {
        $student->load(['assessments.coach', 'activeBatches']);

        $performances = $student->performances()
            ->with('match:id,opponent_name,match_date,match_type,result')
            ->join('cricket_matches', 'cricket_matches.id', '=', 'match_performances.cricket_match_id')
            ->orderByDesc('cricket_matches.match_date')
            ->select('match_performances.*')
            ->limit(20)
            ->get();

        $career = $student->performances()
            ->selectRaw('COUNT(*) innings, SUM(runs_scored) runs, SUM(balls_faced) balls, MAX(runs_scored) best,
                         SUM(fours) fours, SUM(sixes) sixes, SUM(is_out) outs,
                         SUM(wickets) wickets, SUM(overs_bowled) overs, SUM(runs_conceded) conceded,
                         SUM(catches) catches, SUM(run_outs) run_outs, SUM(stumpings) stumpings')
            ->first();

        // Assessment trend for the chart, oldest first.
        $trend = $student->assessments->sortBy('assessment_date')->values();

        return view('admin.performance.show', compact('student', 'performances', 'career', 'trend'));
    }

    public function destroy(StudentAssessment $assessment)
    {
        $studentId = $assessment->student_id;
        $assessment->delete();

        return redirect()->route('admin.performance.show', $studentId)->with('success', 'Assessment deleted.');
    }
}
