<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_home(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Đăng nhập');
    }

    public function test_admin_can_log_in_with_username_and_password(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin441',
            'password' => 'a-secure-test-password',
        ]);

        $this->post('/login', [
            'username' => 'admin441',
            'password' => 'a-secure-test-password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_invalid_password_is_rejected(): void
    {
        User::factory()->create([
            'username' => 'admin441',
            'password' => 'a-secure-test-password',
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'admin441',
            'password' => 'incorrect-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_log_out(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
