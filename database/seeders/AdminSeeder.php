<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Rajesh Sharma',
                'email' => 'admin@admin.com',
                'role' => Admin::ROLE_OWNER,
                'phone' => '9876500001',
            ],
            [
                'name' => 'Priya Nair',
                'email' => 'manager@academy.com',
                'role' => Admin::ROLE_ADMIN,
                'phone' => '9876500002',
            ],
            [
                'name' => 'Vikram Singh',
                'email' => 'coach@academy.com',
                'role' => Admin::ROLE_COACH,
                'phone' => '9876500003',
            ],
            [
                'name' => 'Anita Desai',
                'email' => 'accounts@academy.com',
                'role' => Admin::ROLE_ACCOUNTANT,
                'phone' => '9876500004',
            ],
        ];

        foreach ($accounts as $account) {
            Admin::updateOrCreate(
                ['email' => $account['email']],
                array_merge($account, [
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ])
            );
        }
    }
}
