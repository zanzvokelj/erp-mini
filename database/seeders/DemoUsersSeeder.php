<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@admin.com',
                'name' => 'Admin',
                'role' => 'admin',
            ],
            [
                'email' => 'sadmin@sadmin.com',
                'name' => 'Super Admin',
                'role' => 'admin',
            ],
            [
                'email' => 'sales@example.com',
                'name' => 'Sales User',
                'role' => 'sales',
            ],
            [
                'email' => 'finance@example.com',
                'name' => 'Finance User',
                'role' => 'finance',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
