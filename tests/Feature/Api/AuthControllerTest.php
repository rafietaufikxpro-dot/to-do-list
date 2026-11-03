<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Budi',
            'email' => 'budi@mail.com',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'budi@mail.com')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'budi@mail.com']);
    }

    public function test_rejects_registration_with_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'budi@mail.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Budi',
            'email' => 'budi@mail.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Email sudah terdaftar');
    }

    public function test_logs_in_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'budi@mail.com', 'password' => 'password123']);

        $response = $this->postJson('/api/login', [
            'email' => 'budi@mail.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_rejects_login_with_a_wrong_password(): void
    {
        User::factory()->create(['email' => 'budi@mail.com', 'password' => 'password123']);

        $response = $this->postJson('/api/login', [
            'email' => 'budi@mail.com',
            'password' => 'salah-banget',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah');
    }

    public function test_returns_the_profile_of_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_returns_401_for_profile_without_a_token(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_logout_deletes_the_current_token(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test-token')->plainTextToken);

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logout berhasil');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
