<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Laravel\Passport\Client;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostTest extends TestCase
{
    use RefreshDatabase;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Retrieve a token using the client ID and secret
        $client = new Client;
        $client->name = "Tester";
        $client->secret = "xczeOa552JWExlzvHhIAiuXS8hPV93NIzplF8p34";
        $client->redirect = '';
        $client->personal_access_client = false;
        $client->password_client = false;
        $client->revoked = false;
        $client->save();


        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ]);

        $this->token = $response->json('access_token');
    }

    /**
     * Test index method.
     */
    public function test_can_list_all_posts()
    {
        Post::factory()->count(5)->create();

        // Act: Make a GET request to the index route with the token
        $response = $this->getJson(route('posts.index'), [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)->assertJsonCount(5);
    }

    /**
     * Test store method with rate limiting.
     */
    public function test_can_create_post()
    {
        $user = User::factory()->create();
        $postData = Post::factory()->make(['user_id' => $user->id])->toArray();

        // Act: Make a POST request to store a post with the token
        $response = $this->postJson(route('posts.store'), $postData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Post created successfully']);
        $this->assertDatabaseHas('posts', ['title' => $postData['title']]);
    }

    /**
     * Test rate limiting on post creation.
     */
    public function test_rate_limiting_on_post_creation()
    {
        $user = User::factory()->create();
        $postData = Post::factory()->make(['user_id' => $user->id])->toArray();
        // Make 5 actual POST requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('posts.store'), $postData, [
                'Authorization' => 'Bearer ' . $this->token,
            ]);

            // Assert that the first 5 requests are successful
            $response->assertStatus(201);
        }

        // Act: Make the 6th POST request with the token
        $response = $this->postJson(route('posts.store'), $postData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test show method with Gate authorization.
     */
    public function test_can_show_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Mock Gate authorization
        // Gate::shouldReceive('authorize')->once()->with('view', $post)->andReturn(true);

        // Act: Make a GET request to show the post with the token
        $response = $this->getJson(route('posts.show', ['post' => $post->id, 'request_sender' => $user->id]), [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => $post->title]);
    }

    /**
     * Test update method with Gate authorization.
     */
    public function test_can_update_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $updatedData = ['title' => 'Updated Post Title', 'content' => 'Updated Post Content'];

        // Mock Gate authorization
        // Gate::shouldReceive('authorize')->once()->with('update', $post)->andReturn(true);

        // Act: Make a PUT request to update the post with the token
        $response = $this->putJson(route('posts.update', ['post' => $post->id, 'request_sender' => $user->id]), $updatedData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Post updated successfully']);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated Post Title', 'content' => 'Updated Post Content']);
    }

    /**
     * Test delete method with Gate authorization.
     */
    public function test_can_delete_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Mock Gate authorization
        // Gate::shouldReceive('authorize')->once()->with('delete', $post)->andReturn(true);

        // Act: Make a DELETE request to delete the post with the token
        $response = $this->deleteJson(route('posts.destroy', ['post' => $post->id, 'request_sender' => $user->id]), [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
    }
}
