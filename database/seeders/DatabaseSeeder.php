<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Phase02Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'is_admin' => true,
                'is_super_admin' => true,
                'password' => bcrypt('password'),
            ]
        );

        $this->call([SettingSeeder::class]);
        $this->call([AgentPersonaSeeder::class]);
        $this->call([MCPServerSeeder::class]);
        $this->call([DefaultAgentToolSeeder::class]);
        $this->call([SystemAgentSeeder::class]);
        $this->call([Phase02Seeder::class]);
        $this->call([AiProvidersSeeder::class]);
    }
}
