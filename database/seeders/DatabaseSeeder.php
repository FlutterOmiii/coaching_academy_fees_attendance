<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: each seeder below depends on the ones before it.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            AdminSeeder::class,
            CoachSeeder::class,
            BatchSeeder::class,
            StudentSeeder::class,
            FeeSeeder::class,
            AttendanceSeeder::class,
            TournamentSeeder::class,
            AssessmentSeeder::class,
            EventSeeder::class,
        ]);
    }
}
