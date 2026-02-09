<?php

namespace Tests\Feature;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Add test route dynamically
        Route::get('/api/test', function () {
            return response()->json(['message' => 'Hello world']);
        })->middleware('auth:api');
    }

    protected function tearDown(): void
    {
        // Test route will be automatically cleaned up when the test ends
        parent::tearDown();
    }

    #[test]
    public function test_logged_in_user_can_access_protected_route(): void
    {
        // Login and get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('access_token');

        // Access protected route
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/test');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Hello world']);
    }

    #[test]
    public function test_non_authenticated_user_gets_401_on_protected_route(): void
    {
        $response = $this->getJson('/api/test');

        $response->assertStatus(401);
    }

    #[test]
    public function test_user_after_logout_gets_401_on_protected_route(): void
    {
        // Login and get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('access_token');

        // Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/logout');

        $logoutResponse->assertStatus(200);

        // Try to access protected route after logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/test');

        $response->assertStatus(401);
    }

    #[test]
    public function test_user_after_refresh_can_access_protected_route(): void
    {
        // Login and get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);
        $originalToken = $loginResponse->json('access_token');

        // Refresh token
        $refreshResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$originalToken,
        ])->postJson('/api/refresh');

        $refreshResponse->assertStatus(200);
        $newToken = $refreshResponse->json('access_token');

        // Access protected route with new token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->getJson('/api/test');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Hello world']);
    }
}
