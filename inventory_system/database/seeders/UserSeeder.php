<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'System Admin',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'is_active' => true,
            ]
        );

        // Manager user
        User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name'     => 'Inventory Manager',
                'password' => Hash::make('password'),
                'role'     => 'manager',
                'is_active' => true,
            ]
        );

        // Staff user
        User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name'     => 'Warehouse Staff',
                'password' => Hash::make('password'),
                'role'     => 'staff',
                'is_active' => true,
            ]
        );

        // Additional random users
        User::factory()->count(5)->create();
    }
}