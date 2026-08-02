<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\CricketMatch;
use App\Models\Event;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = Admin::where('email', 'admin@admin.com')->value('id');

        $this->seedStandaloneEvents($ownerId);
        $this->mirrorMatches($ownerId);
        $this->mirrorTournaments($ownerId);
    }

    private function seedStandaloneEvents(?int $ownerId): void
    {
        $events = [
            ['Summer Coaching Camp', 'camp', 12, 'Academy Centre Wicket', 5],
            ['U16 Selection Trials', 'trial', 6, 'Ground B - Main', 1],
            ['Parents Progress Meeting', 'meeting', 9, 'Academy Clubhouse', 0],
            ['Independence Day Break', 'holiday', 21, null, 0],
            ['Fitness Assessment Week', 'camp', 16, 'Gym & Track', 4],
            ['Annual Prize Distribution', 'other', 34, 'Academy Auditorium', 0],
            ['Monsoon Skills Workshop', 'camp', -8, 'Indoor Nets', 2],
            ['Coach Development Session', 'meeting', -3, 'Academy Clubhouse', 0],
        ];

        foreach ($events as [$title, $type, $dayOffset, $venue, $durationDays]) {
            $start = Carbon::today()->addDays($dayOffset)->setTime(9, 0);

            Event::create([
                'title' => $title,
                'type' => $type,
                'start_at' => $start,
                'end_at' => $durationDays > 0
                    ? $start->copy()->addDays($durationDays)->setTime(17, 0)
                    : $start->copy()->addHours(3),
                'is_all_day' => $durationDays > 0,
                'venue' => $venue,
                'description' => $title.' — organised by the academy.',
                'color' => Event::TYPE_COLORS[$type],
                'status' => $dayOffset < 0 ? 'completed' : 'scheduled',
                'created_by' => $ownerId,
            ]);
        }
    }

    /** Surface scheduled matches on the calendar. */
    private function mirrorMatches(?int $ownerId): void
    {
        foreach (CricketMatch::upcoming()->get() as $match) {
            Event::create([
                'title' => 'Match: '.$match->title,
                'type' => 'match',
                'start_at' => Carbon::parse($match->match_date->toDateString().' '.($match->start_time ?? '09:00')),
                'end_at' => Carbon::parse($match->match_date->toDateString().' '.($match->start_time ?? '09:00'))->addHours(6),
                'venue' => $match->venue,
                'description' => $match->match_type.' fixture against '.$match->opponent_name,
                'color' => Event::TYPE_COLORS['match'],
                'status' => 'scheduled',
                'created_by' => $ownerId,
            ]);
        }
    }

    private function mirrorTournaments(?int $ownerId): void
    {
        foreach (Tournament::whereIn('status', ['upcoming', 'ongoing'])->get() as $tournament) {
            Event::create([
                'title' => $tournament->name,
                'type' => 'tournament',
                'start_at' => $tournament->start_date->copy()->setTime(8, 0),
                'end_at' => ($tournament->end_date ?? $tournament->start_date)->copy()->setTime(18, 0),
                'is_all_day' => true,
                'venue' => $tournament->venue,
                'description' => 'Organised by '.$tournament->organizer,
                'color' => Event::TYPE_COLORS['tournament'],
                'status' => 'scheduled',
                'created_by' => $ownerId,
            ]);
        }
    }
}
