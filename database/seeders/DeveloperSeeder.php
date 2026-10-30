<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@wadahngopi.test'],
            [
                'name' => 'Wadah Admin',
                'password' => Hash::make('admin123'),
                'role' => UserRole::Admin->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'dev@wadahngopi.test'],
            [
                'name' => 'Wadah Developer',
                'password' => Hash::make('dev123'),
                'role' => UserRole::Developer->value,
            ]
        );

        $this->command->info('Akun Developer & Admin berhasil dibuat!');
        $this->command->warn('Email: dev@wadahngopi.test | Pass: dev123');
        $this->command->warn('Email: admin@wadahngopi.test | Pass: admin123');
    }
}
