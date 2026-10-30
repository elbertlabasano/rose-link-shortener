<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['idcode' => '20260211'],
            [
                'name' => 'Jathnier',
                'is_active' => true,
                // Placeholder until the account profile is confirmed through SSO.
                'email' => '20260211@users.invalid',
                'password' => Str::random(64),
            ],
        );

    }
}
