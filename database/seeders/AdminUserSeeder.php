<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) config('admin.name'));
        $username = trim((string) config('admin.username'));
        $password = (string) config('admin.password');

        if ($name === '' || $username === '' || $password === '') {
            throw new RuntimeException(
                'Set ADMIN_NAME, ADMIN_USERNAME, and ADMIN_PASSWORD in .env before seeding the admin user.',
            );
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD must contain at least 12 characters.');
        }

        if (mb_strlen($username) > 80 || preg_match('/^[A-Za-z0-9._-]+$/', $username) !== 1) {
            throw new RuntimeException(
                'ADMIN_USERNAME must be at most 80 characters and use only letters, numbers, dots, underscores, or hyphens.',
            );
        }

        if (User::query()->count() > 1) {
            throw new RuntimeException('More than one user exists; resolve this before updating the single admin account.');
        }

        $admin = User::query()->first() ?? new User;
        $admin->fill([
            'name' => $name,
            'username' => $username,
        ]);

        if (! $admin->exists || ! Hash::check($password, $admin->password)) {
            $admin->password = $password;
        }

        $admin->save();

        $this->command?->info("Admin account [{$username}] is ready.");
    }
}
