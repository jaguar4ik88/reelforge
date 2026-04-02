<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем тестового пользователя
        User::firstOrCreate(
            ['email' => 'test@reelforge.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'plan' => 'free',
                'email_verified_at' => now(),
            ]
        );

        // Создаем админа
        User::firstOrCreate(
            ['email' => 'admin@reelforge.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'plan' => 'free',
                'email_verified_at' => now(),
            ]
        );
    }
}
