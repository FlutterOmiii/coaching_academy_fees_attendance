<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Coach;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $coaches = Coach::orderBy('id')->pluck('id')->all();

        // name, code, age_group, skill, capacity, start, end, days, ground, fee
        $batches = [
            ['Sub Junior Morning', 'BAT-SJM', 'under_10', 'beginner', 24, '06:30', '08:00', [1, 3, 5], 'Ground A - Nets', 1800],
            ['Junior Morning Elite', 'BAT-JME', 'under_12', 'intermediate', 22, '06:00', '08:00', [1, 2, 3, 4, 5], 'Ground A - Main', 2500],
            ['Under 14 Development', 'BAT-U14', 'under_14', 'intermediate', 25, '16:30', '18:30', [1, 3, 5], 'Ground B - Main', 2800],
            ['Under 16 Elite', 'BAT-U16', 'under_16', 'advanced', 20, '16:00', '19:00', [1, 2, 3, 4, 5], 'Ground B - Main', 3500],
            ['Under 19 Academy', 'BAT-U19', 'under_19', 'advanced', 18, '06:00', '09:00', [1, 2, 3, 4, 5, 6], 'Centre Wicket', 4200],
            ['Senior Squad', 'BAT-SNR', 'senior', 'professional', 16, '16:00', '19:00', [2, 4, 6], 'Centre Wicket', 5000],
            ['Weekend Beginners', 'BAT-WKB', 'open', 'beginner', 30, '08:00', '10:00', [0, 6], 'Ground A - Nets', 1500],
            ['Pace Bowling Specialist', 'BAT-PBS', 'open', 'advanced', 12, '17:00', '19:00', [2, 4], 'Ground C - Nets', 3200],
        ];

        foreach ($batches as $index => [$name, $code, $age, $skill, $cap, $start, $end, $days, $ground, $fee]) {
            $batch = Batch::create([
                'name' => $name,
                'code' => $code,
                'coach_id' => $coaches[$index % count($coaches)] ?? null,
                'age_group' => $age,
                'skill_level' => $skill,
                'capacity' => $cap,
                'start_time' => $start,
                'end_time' => $end,
                'training_days' => $days,
                'ground' => $ground,
                'monthly_fee' => $fee,
                'start_date' => now()->subMonths(18)->startOfMonth()->toDateString(),
                'description' => $name.' training programme.',
                'status' => 'active',
            ]);

            // Head coach plus one assistant.
            $batch->coaches()->attach($batch->coach_id, [
                'role' => 'head',
                'assigned_on' => $batch->start_date,
            ]);

            $assistant = $coaches[($index + 3) % count($coaches)];
            if ($assistant !== $batch->coach_id) {
                $batch->coaches()->attach($assistant, [
                    'role' => 'assistant',
                    'assigned_on' => $batch->start_date,
                ]);
            }
        }
    }
}
