<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_single_admin_from_environment_configuration(): void
    {
        config()->set('admin', [
            'name' => 'Quản trị viên',
            'username' => 'admin441',
            'password' => 'a-strong-local-password',
        ]);

        $this->seed(AdminUserSeeder::class);
        $originalPasswordHash = User::query()->sole()->password;
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $admin = User::query()->sole();

        $this->assertSame('admin441', $admin->username);
        $this->assertTrue(Hash::check('a-strong-local-password', $admin->password));
        $this->assertSame($originalPasswordHash, $admin->password);
    }

    public function test_it_refuses_to_seed_a_blank_password(): void
    {
        config()->set('admin', [
            'name' => 'Quản trị viên',
            'username' => 'admin441',
            'password' => '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_PASSWORD');

        $this->seed(AdminUserSeeder::class);
    }
}
