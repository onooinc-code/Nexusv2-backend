<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo admin user
        User::updateOrCreate(
            ['email' => 'admin@nexus.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
            ]
        );

        // Create demo user
        User::updateOrCreate(
            ['email' => 'demo@nexus.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password123'),
            ]
        );

        // Create test user
        User::updateOrCreate(
            ['email' => 'test@nexus.local'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
            ]
        );
    }
}
