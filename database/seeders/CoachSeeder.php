<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Coach;
use App\Models\CoachAvailability;
use Illuminate\Database\Seeder;

class CoachSeeder extends Seeder
{
    public function run(): void
    {
        $coaches = [
            ['Vikram', 'Singh', 'batting', 'BCCI Level 2', 'Former Ranji Trophy batsman', 14, 55000],
            ['Anil', 'Kumble', 'bowling', 'BCCI Level 2', 'Specialist spin bowling coach', 11, 52000],
            ['Suresh', 'Raina', 'fielding', 'BCCI Level 1', 'Fielding and agility specialist', 8, 42000],
            ['Farhan', 'Qureshi', 'wicket_keeping', 'BCCI Level 1', 'Wicket-keeping technique coach', 9, 44000],
            ['Deepak', 'Mehta', 'fitness', 'NSCA Certified', 'Strength and conditioning coach', 6, 38000],
            ['Rohit', 'Verma', 'all_round', 'BCCI Level 2', 'Head coach, senior squad', 16, 62000],
            ['Kavita', 'Iyer', 'batting', 'BCCI Level 1', 'Junior batting coach', 5, 35000],
            ['Sanjay', 'Patil', 'bowling', 'BCCI Level 1', 'Pace bowling coach', 7, 40000],
        ];

        // The coach login account created by AdminSeeder is linked to the first coach.
        $coachAdminId = Admin::where('email', 'coach@academy.com')->value('id');

        foreach ($coaches as $index => [$first, $last, $spec, $cert, $bio, $years, $salary]) {
            $coach = Coach::create([
                'coach_code' => 'CCH'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'admin_id' => $index === 0 ? $coachAdminId : null,
                'first_name' => $first,
                'last_name' => $last,
                'date_of_birth' => now()->subYears(28 + $index)->subMonths($index * 3)->toDateString(),
                'gender' => in_array($first, ['Kavita'], true) ? 'female' : 'male',
                'email' => strtolower($first.'.'.$last).'@academy.com',
                'phone' => '98765'.str_pad((string) (10000 + $index), 5, '0', STR_PAD_LEFT),
                'address' => 'Sector '.(10 + $index).', Sports Complex Road',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '4110'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'specialization' => $spec,
                'qualification' => 'B.P.Ed, Sports Science',
                'certification_level' => $cert,
                'experience_years' => $years,
                'joining_date' => now()->subMonths(36 - ($index * 3))->toDateString(),
                'monthly_salary' => $salary,
                'bio' => $bio,
                'status' => $index === 7 ? 'on_leave' : 'active',
            ]);

            $this->seedAvailability($coach);
        }
    }

    /** Weekday mornings and evenings; Sunday off. */
    private function seedAvailability(Coach $coach): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $day) {
            CoachAvailability::create([
                'coach_id' => $coach->id,
                'day_of_week' => $day,
                'start_time' => '06:00',
                'end_time' => '09:00',
                'is_available' => true,
            ]);

            CoachAvailability::create([
                'coach_id' => $coach->id,
                'day_of_week' => $day,
                'start_time' => '16:00',
                'end_time' => '19:00',
                'is_available' => $day !== 6,
            ]);
        }
    }
}
