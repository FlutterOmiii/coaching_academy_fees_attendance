<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\CricketMatch;
use App\Models\MatchPerformance;
use App\Models\Student;
use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    private const OPPONENTS = [
        'Sunrise Cricket Club', 'Deccan Gymkhana', 'Royal Challengers Academy', 'MCA Colts',
        'Shivaji Sports Club', 'Nehru Stadium XI', 'Pune Warriors Academy', 'Sahyadri CC',
        'Vidarbha Juniors', 'Mumbai Cricket School', 'Konkan Kings', 'Western Sports Club',
    ];

    private const VENUES = [
        'MCA Stadium, Gahunje', 'Deccan Gymkhana Ground', 'PYC Hindu Gymkhana',
        'Nehru Stadium', 'Academy Centre Wicket', 'Sahyadri Ground',
    ];

    public function run(): void
    {
        $tournaments = $this->seedTournaments();
        $teams = $this->seedTeams($tournaments);
        $this->seedMatches($tournaments, $teams);
    }

    /** @return array<string, Tournament> */
    private function seedTournaments(): array
    {
        $definitions = [
            ['Pune District U14 Championship', 'Pune District Cricket Association', 't20', -11, -10, 'completed', 'Winner'],
            ['Maharashtra Junior League', 'MCA', 'odi', -8, -6, 'completed', 'Runner-up'],
            ['Monsoon Cup U16', 'Deccan Sports Trust', 't20', -5, -4, 'completed', 'Semi-finalist'],
            ['Inter-Academy T10 Bash', 'Pune Academy Alliance', 't10', -2, -1, 'completed', 'Winner'],
            ['State Youth Trophy', 'Maharashtra Cricket Association', 'odi', 0, 1, 'ongoing', null],
            ['Summer Invitational U19', 'City Sports Council', 't20', 2, 3, 'upcoming', null],
        ];

        $tournaments = [];

        foreach ($definitions as [$name, $organizer, $format, $startOffset, $endOffset, $status, $position]) {
            $tournaments[$name] = Tournament::create([
                'name' => $name,
                'organizer' => $organizer,
                'venue' => self::VENUES[array_rand(self::VENUES)],
                'format' => $format,
                'start_date' => Carbon::now()->addMonths($startOffset)->startOfMonth()->addDays(5)->toDateString(),
                'end_date' => Carbon::now()->addMonths($endOffset)->startOfMonth()->addDays(20)->toDateString(),
                'entry_fee' => [5000, 7500, 10000][array_rand([0, 1, 2])],
                'description' => $name.' organised by '.$organizer.'.',
                'status' => $status,
                'final_position' => $position,
            ]);
        }

        return $tournaments;
    }

    /** @return array<int, Team> */
    private function seedTeams(array $tournaments): array
    {
        $coaches = Coach::pluck('id')->all();
        $teams = [];

        $squads = [
            ['Academy Colts U14', 'under_14', 'Pune District U14 Championship'],
            ['Academy Juniors U16', 'under_16', 'Monsoon Cup U16'],
            ['Academy Elite U19', 'under_19', 'State Youth Trophy'],
            ['Academy Seniors', 'senior', 'Maharashtra Junior League'],
            ['Academy Strikers', 'open', 'Inter-Academy T10 Bash'],
        ];

        foreach ($squads as $index => [$name, $ageGroup, $tournamentName]) {
            $team = Team::create([
                'name' => $name,
                'tournament_id' => $tournaments[$tournamentName]->id ?? null,
                'coach_id' => $coaches[$index % count($coaches)] ?? null,
                'age_group' => $ageGroup,
                'description' => $name.' squad.',
                'status' => 'active',
            ]);

            $this->attachSquad($team, $ageGroup);
            $teams[] = $team;
        }

        return $teams;
    }

    /** Pick age-appropriate players and hand out unique jersey numbers. */
    private function attachSquad(Team $team, string $ageGroup): void
    {
        $query = Student::active()->approved();

        // 'open' and 'senior' squads draw from any age.
        if (! in_array($ageGroup, ['open', 'senior'], true)) {
            $maxAge = (int) filter_var($ageGroup, FILTER_SANITIZE_NUMBER_INT);
            $query->whereDate('date_of_birth', '>=', Carbon::today()->subYears($maxAge));
        }

        $players = $query->inRandomOrder()->limit(14)->get();

        if ($players->isEmpty()) {
            $players = Student::active()->inRandomOrder()->limit(14)->get();
        }

        $jerseys = range(1, 99);
        shuffle($jerseys);

        foreach ($players->values() as $index => $student) {
            $team->students()->attach($student->id, [
                'jersey_number' => $jerseys[$index],
                'is_captain' => $index === 0,
                'is_vice_captain' => $index === 1,
                'role' => $student->playing_role,
            ]);
        }
    }

    private function seedMatches(array $tournaments, array $teams): void
    {
        if (empty($teams)) {
            return;
        }

        // 26 played matches over the last year, plus 5 scheduled ahead.
        for ($i = 0; $i < 26; $i++) {
            $team = $teams[array_rand($teams)];
            $date = Carbon::now()->subDays(random_int(5, 350));

            $this->createCompletedMatch($team, $date);
        }

        for ($i = 0; $i < 5; $i++) {
            $team = $teams[array_rand($teams)];

            CricketMatch::create([
                'tournament_id' => $team->tournament_id,
                'team_id' => $team->id,
                'opponent_name' => self::OPPONENTS[array_rand(self::OPPONENTS)],
                'match_date' => Carbon::now()->addDays(random_int(2, 45))->toDateString(),
                'start_time' => '09:00',
                'venue' => self::VENUES[array_rand(self::VENUES)],
                'match_type' => ['friendly', 'tournament', 'league'][array_rand([0, 1, 2])],
                'overs' => 20,
                'status' => 'scheduled',
            ]);
        }
    }

    private function createCompletedMatch(Team $team, Carbon $date): void
    {
        $overs = [10, 20, 50][array_rand([0, 1, 2])];

        $academyRuns = random_int(70, 220);
        $opponentRuns = random_int(70, 220);
        $academyWkts = random_int(2, 10);
        $opponentWkts = random_int(2, 10);

        $result = match (true) {
            $academyRuns > $opponentRuns => 'won',
            $academyRuns < $opponentRuns => 'lost',
            default => 'tie',
        };

        $squad = $team->students()->inRandomOrder()->limit(11)->get();

        $match = CricketMatch::create([
            'tournament_id' => $team->tournament_id,
            'team_id' => $team->id,
            'opponent_name' => self::OPPONENTS[array_rand(self::OPPONENTS)],
            'match_date' => $date->toDateString(),
            'start_time' => '09:00',
            'venue' => self::VENUES[array_rand(self::VENUES)],
            'match_type' => ['practice', 'friendly', 'tournament', 'league', 'knockout'][array_rand([0, 1, 2, 3, 4])],
            'overs' => $overs,
            'toss_won_by' => ['academy', 'opponent'][array_rand([0, 1])],
            'toss_decision' => ['bat', 'bowl'][array_rand([0, 1])],
            'status' => 'completed',
            'result' => $result,
            'win_margin' => $result === 'won'
                ? abs($academyRuns - $opponentRuns).' runs'
                : ($result === 'lost' ? (10 - $opponentWkts).' wickets' : null),
            'academy_runs' => $academyRuns,
            'academy_wickets' => $academyWkts,
            'academy_overs' => $overs,
            'opponent_runs' => $opponentRuns,
            'opponent_wickets' => $opponentWkts,
            'opponent_overs' => $overs,
            'man_of_match_id' => $squad->first()?->id,
            'summary' => 'Academy '.$academyRuns.'/'.$academyWkts.' vs '.$opponentRuns.'/'.$opponentWkts.'.',
        ]);

        $this->seedPerformances($match, $squad, $academyRuns);
    }

    /** Distribute the team total across the batting order. */
    private function seedPerformances(CricketMatch $match, $squad, int $teamTotal): void
    {
        $remaining = $teamTotal;

        foreach ($squad->values() as $index => $student) {
            $runs = $index < 7 && $remaining > 0
                ? min($remaining, random_int(0, (int) max(1, $remaining / 2)))
                : 0;
            $remaining -= $runs;

            $balls = $runs > 0 ? max(1, (int) round($runs / (random_int(80, 160) / 100))) : random_int(0, 4);
            $bowls = $index >= 5;

            MatchPerformance::create([
                'cricket_match_id' => $match->id,
                'student_id' => $student->id,
                'batting_position' => $index + 1,
                'runs_scored' => $runs,
                'balls_faced' => $balls,
                'fours' => (int) floor($runs / 12),
                'sixes' => (int) floor($runs / 25),
                'is_out' => $runs > 0 ? (bool) random_int(0, 1) : false,
                'dismissal_type' => $runs > 0
                    ? ['bowled', 'caught', 'lbw', 'run_out', 'stumped', 'not_out'][array_rand([0, 1, 2, 3, 4, 5])]
                    : 'not_out',
                'overs_bowled' => $bowls ? random_int(1, 4) : 0,
                'maidens' => $bowls ? random_int(0, 1) : 0,
                'runs_conceded' => $bowls ? random_int(8, 45) : 0,
                'wickets' => $bowls ? random_int(0, 3) : 0,
                'wides' => $bowls ? random_int(0, 4) : 0,
                'no_balls' => $bowls ? random_int(0, 2) : 0,
                'catches' => random_int(0, 2),
                'run_outs' => random_int(0, 1),
                'stumpings' => $student->playing_role === 'wicket_keeper' ? random_int(0, 2) : 0,
                'rating' => round(random_int(40, 95) / 10, 1),
            ]);
        }
    }
}
