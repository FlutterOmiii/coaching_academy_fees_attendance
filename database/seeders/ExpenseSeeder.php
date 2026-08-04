<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Coach;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $accountant = Admin::where('email', 'accounts@academy.com')->value('id')
            ?? Admin::where('email', 'admin@admin.com')->value('id');

        $categories = $this->seedCategories();

        // Salaries scale off the actual coaching wage bill, so it is realistically
        // the biggest line item.
        $wageBill = max(200000, (float) Coach::active()->sum('monthly_salary'));

        // 14 months of history so year + trend charts are full.
        foreach (range(13, 0) as $ago) {
            $month = Carbon::now()->subMonthsNoOverflow($ago)->startOfMonth();
            $this->seedMonth($month, $categories, $wageBill, $accountant);
        }
    }

    /** @return array<string, ExpenseCategory> */
    private function seedCategories(): array
    {
        $defs = [
            ['Salaries', '#4361ee', 'Coaching and support staff wages'],
            ['Ground Rent', '#00ab55', 'Turf, nets and ground hire'],
            ['Equipment & Kits', '#e2a03f', 'Bats, balls, nets, kits, gear'],
            ['Utilities', '#2196f3', 'Electricity, water, internet'],
            ['Maintenance', '#805dca', 'Repairs and upkeep'],
            ['Marketing', '#e7515a', 'Ads, banners, promotions'],
            ['Travel', '#0ea5e9', 'Transport and travel'],
            ['Refreshments', '#e91e63', 'Water, snacks for sessions'],
            ['Miscellaneous', '#3b3f5c', 'Other business expenses'],
        ];

        $out = [];
        foreach ($defs as [$name, $color, $desc]) {
            $out[$name] = ExpenseCategory::firstOrCreate(
                ['name' => $name],
                ['color' => $color, 'description' => $desc, 'status' => 'active']
            );
        }

        return $out;
    }

    private function seedMonth(Carbon $month, array $cats, float $wageBill, ?int $by): void
    {
        // Fixed monthly costs.
        $this->add($cats['Salaries'], 'Staff salaries — '.$month->format('F Y'),
            round($wageBill + random_int(-5000, 8000), -2), $month->copy()->day(min(28, 5)),
            'bank_transfer', 'Academy Payroll', $by);

        $this->add($cats['Ground Rent'], 'Ground & nets rent',
            random_int(24000, 28000), $month->copy()->day(3),
            'bank_transfer', 'Deccan Sports Ground', $by);

        $this->add($cats['Utilities'], 'Electricity & water',
            random_int(7000, 13000), $month->copy()->day(random_int(8, 14)),
            'upi', 'MSEB / Water Board', $by);

        $this->add($cats['Refreshments'], 'Session refreshments',
            random_int(2500, 5500), $month->copy()->day(random_int(10, 20)),
            'cash', 'Local Store', $by);

        // Occasional costs.
        if (random_int(1, 2) === 1) {
            $this->add($cats['Equipment & Kits'], $this->pick(['New cricket balls', 'Batting kits', 'Practice nets', 'Bowling machine service', 'Team jerseys']),
                random_int(12000, 55000), $month->copy()->day(random_int(5, 25)),
                $this->pick(['card', 'upi', 'bank_transfer']), $this->pick(['City Sports Supplies', 'ProCricket Store', 'SG Distributors']), $by);
        }

        if (random_int(1, 3) === 1) {
            $this->add($cats['Maintenance'], $this->pick(['Turf mowing', 'Net repairs', 'Pitch roller service', 'Floodlight fix']),
                random_int(4000, 16000), $month->copy()->day(random_int(6, 24)),
                'cash', $this->pick(['GreenTurf Services', 'Handy Repairs']), $by);
        }

        if (random_int(1, 3) === 1) {
            $this->add($cats['Marketing'], $this->pick(['Facebook ads', 'Admission banners', 'Pamphlet printing', 'Local newspaper ad']),
                random_int(8000, 28000), $month->copy()->day(random_int(2, 15)),
                $this->pick(['card', 'upi']), $this->pick(['AdWorks Media', 'PrintHub']), $by);
        }

        if (random_int(1, 4) === 1) {
            $this->add($cats['Travel'], 'Team travel for matches',
                random_int(5000, 18000), $month->copy()->day(random_int(12, 26)),
                'cash', 'Sharma Travels', $by);
        }

        if (random_int(1, 5) === 1) {
            $this->add($cats['Miscellaneous'], $this->pick(['Stationery', 'First-aid supplies', 'Software subscription', 'Guest coach fee']),
                random_int(2000, 9000), $month->copy()->day(random_int(1, 27)),
                $this->pick(['cash', 'upi']), null, $by);
        }
    }

    private function add(ExpenseCategory $cat, string $title, $amount, Carbon $date, string $method, ?string $vendor, ?int $by): void
    {
        // Don't create future-dated rows for the current partial month.
        if ($date->isFuture()) {
            $date = Carbon::today();
        }

        Expense::create([
            'expense_category_id' => $cat->id,
            'title' => $title,
            'amount' => $amount,
            'expense_date' => $date->toDateString(),
            'payment_method' => $method,
            'vendor' => $vendor,
            'reference_no' => strtoupper(substr(md5($title.$date), 0, 8)),
            'created_by' => $by,
        ]);
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }
}
