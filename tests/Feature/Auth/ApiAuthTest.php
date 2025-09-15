<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_register_returns_201_and_token()
    {
        Notification::fake();

        $res = $this->postJson('/api/signup', [
            'name' => 'Bob',
            'username' => 'bob',
            'email' => 'bob@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $res->assertStatus(201)->assertJsonStructure(['token']);
    }

    public function test_api_login_returns_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $res = $this->postJson('/api/signin', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $res->assertStatus(200)->assertJsonStructure(['token', 'user']);
    }

    public function test_api_logout_revokes_tokens()
    {
        $user = User::factory()->create();
        $token = $user->createToken('x')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);

        $res = $this->postJson('/api/logout');
        $res->assertStatus(200);
    }
}
