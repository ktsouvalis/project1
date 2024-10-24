<?php

namespace Tests\Feature;

use Mockery;
use Exception;
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
            'scope' => 'manage-resources',
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
        $post = Post::factory()->create(['user_id' => $user->id]);

        // Act: Make a GET request to show the post with the token
        $response = $this->getJson(route('posts.show', $post), [
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
        $post = Post::factory()->create(['user_id' => $user->id]);
        $updatedData = ['title' => 'Updated Post Title', 'content' => 'Updated Post Content'];

        // Act: Make a PUT request to update the post with the token
        $response = $this->putJson(route('posts.update', $post), $updatedData, [
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
        $post = Post::factory()->create(['user_id' => $user->id]);

        // Act: Make a DELETE request to delete the post with the token
        $response = $this->deleteJson(route('posts.destroy', $post), [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
    }

    public function test_create_post_throws_exception()
    {     
        //Mocking does not work for the create method of the post model, so we will try to insert invalid data
        $postData = ['titles' => 'Test Title', 'content' => 'Test Content'];

        $response = $this->postJson(route('posts.store'), $postData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(500);
    }

    public function test_update_post_throws_exception()
    {
        $user = User::factory()->create();
        // Mock the Post model to throw an exception when trying to update
        $this->instance(Post::class, Mockery::mock(Post::class, function ($mock) {
            $mock->shouldReceive('update')->andThrow(new Exception('Post update failed'));
        }));
        
        $post = Post::factory()->create(['user_id' => $user->id]);
        $updatedData = ['title' => 'Updated Title', 'content' => 'Updated Content'];

        $response = $this->putJson(route('posts.update', $post), $updatedData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(500);
    }

    public function test_delete_post_throws_exception()
    {
        $user = User::factory()->create();
        // Mock the Post model to throw an exception when trying to update
        $this->instance(Post::class, Mockery::mock(Post::class, function ($mock) {
            $mock->shouldReceive('destroy')->andThrow(new Exception('Post delete failed'));
        }));
        
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson(route('posts.update', $post), [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(500);
    }

    public function test_show_specific_users_posts(){
        $user = User::factory()->create();
        // Create posts
        Post::factory()->count(5)->create(['user_id' => $user->id]); 
        
        // Act: Make a GET request to update the post
        $response = $user->posts()->get();

        // Assert: Check response content
        $this->assertCount(5, $response);
    }

    public function test_show_the_user_of_a_post(){
        $user = User::factory()->create();

        // Create a post for the authenticated user
        $post = Post::factory()->create(['user_id' => $user->id]);

        // Act: Make a GET request to update the post
        $response = $post->user;

        // Assert: Check response content
        $this->assertEquals($user->id, $response->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
