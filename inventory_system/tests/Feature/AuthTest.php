<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     */
use RefreshDatabase;
 
    /*
    |--------------------------------------------------------------------------
    | Registration Tests
    |--------------------------------------------------------------------------
    */
 
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
        ]);
 
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                    'token_type',
                ],
            ]);
 
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }
 
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
 
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Another User',
            'email'                 => 'taken@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }
 
    public function test_registration_fails_with_weak_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'test@example.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }
 
    /*
    |--------------------------------------------------------------------------
    | Login Tests
    |--------------------------------------------------------------------------
    */
 
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password1')]);
 
        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password1',
        ])->assertStatus(200)
          ->assertJsonStructure([
              'data' => ['user', 'token', 'token_type'],
          ]);
    }
 
    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();
 
        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }
 
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password'  => bcrypt('Password1'),
            'is_active' => false,
        ]);
 
        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password1',
        ])->assertStatus(403);
    }
 
    /*
    |--------------------------------------------------------------------------
    | Logout / Me Tests
    |--------------------------------------------------------------------------
    */
 
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
 
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);
    }
 
    public function test_authenticated_user_can_get_own_profile(): void
    {
        $user = User::factory()->create();
 
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    }
 
    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }
}
