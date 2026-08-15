<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'academy' => [
                'academy_name' => 'Mumbai Cricket Academy',
                'academy_tagline' => 'Building cricketers, building character',
                'academy_email' => 'info@mumbaicricketacademy.com',
                'academy_phone' => '+91 98765 00000',
                'academy_address' => 'Mumbai, Maharashtra',
                'academy_established' => '2014',
            ],
            'finance' => [
                'currency_symbol' => '₹',
                'currency_code' => 'INR',
                'fee_due_day' => '10',
                'late_fee_amount' => '200',
            ],
            'general' => [
                'date_format' => 'd M Y',
                'timezone' => 'Asia/Kolkata',
                'attendance_alert_threshold' => '75',
            ],
        ];

        foreach ($settings as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'group' => $group]
                );
            }
        }
    }
}
