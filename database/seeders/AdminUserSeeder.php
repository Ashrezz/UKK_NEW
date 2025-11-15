<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = trim(env('ADMIN_EMAIL', 'admin@default.com'));
        $username = trim(env('ADMIN_USERNAME', ''));

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password123')),
                'role' => 'admin',
                'username' => $username ?: null,
            ]
        );

        // If user exists but username missing and ADMIN_USERNAME provided, set it
        if ($username && (!$user->username || trim($user->username) === '')) {
            $user->username = $username;
            $user->save();
        }
    }
}
