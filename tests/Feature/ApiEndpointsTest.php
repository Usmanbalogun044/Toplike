<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\UserWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function actingSanctum(User $user)
    {
        // Laravel 12: use actingAs with sanctum guard
        $this->actingAs($user, 'sanctum');
    }

    public function test_health_route()
    {
        $res = $this->get('/');
        $res->assertStatus(200);
    }

    public function test_signup_and_login()
    {
        $payload = [
            'name' => 'Alice',
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    $res = $this->postJson('/api/signup', $payload);
    $res->assertStatus(201);

        $login = $this->postJson('/api/signin', [
            'email' => 'alice@example.com',
            'password' => 'password',
        ]);
        $login->assertStatus(200);
    }

    public function test_profile_me_requires_auth()
    {
        $res = $this->getJson('/api/myprofile');
        $res->assertStatus(401);
    }

    public function test_myprofile_with_auth()
    {
        $user = User::factory()->create();
        UserWallet::create(['user_id' => $user->id, 'balance' => 0]);
        $this->actingSanctum($user);

        $res = $this->getJson('/api/myprofile');
        $res->assertStatus(200);
    }

    public function test_posts_list_requires_auth()
    {
        $res = $this->getJson('/api/post/all');
        $res->assertStatus(401);
    }

    public function test_wallet_endpoints()
    {
        $user = User::factory()->create();
        UserWallet::create(['user_id' => $user->id, 'balance' => 1000]);
        $this->actingSanctum($user);

        $res = $this->getJson('/api/wallet');
        $res->assertStatus(200);

        $res2 = $this->getJson('/api/wallet/transactions');
        $res2->assertStatus(200);
    }

    public function test_like_toggle_flow()
    {
        $user = User::factory()->create();
        $this->actingSanctum($user);

        $postUser = User::factory()->create();
        $post = Post::create([
            'user_id' => $postUser->id,
            'caption' => 'Test',
            'type' => 'image',
            'is_visible' => true,
        ]);
    $post->media()->create(['type' => 'image', 'file_path' => 'x.jpg']);

        $res = $this->postJson("/api/like-post/{$post->id}");
        $res->assertStatus(200)->assertJsonStructure(['likes_count']);

        $res2 = $this->postJson("/api/like-post/{$post->id}");
        $res2->assertStatus(200)->assertJsonStructure(['likes_count']);

        $list = $this->getJson("/api/like/list-user/{$post->id}");
        $list->assertStatus(200);
    }
}
