<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class PilotSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'user_type'       => 'pilot',
            'display_name'    => 'Test Pilot',
            'email'           => 'pilot@wingman.com',
            'password'        => Hash::make('password123'),
            'games_expertise' => ['CODM', 'MLBB'],
            'is_verified'     => true,
            'is_active'       => true,
        ]);

        User::create([
            'user_type'    => 'admin',
            'display_name' => 'Admin',
            'email'        => 'admin@wingman.com',
            'password'     => Hash::make('password123'),
            'is_verified'  => true,
            'is_active'    => true,
        ]);
    }
}