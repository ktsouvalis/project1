<?php

namespace Tests\Feature;

use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected $token;
    protected $user;
    /**
     * Set up method to run before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Generate a personal access client
        $client = new Client();
        $client->name = 'Test Client';
        $client->secret = 'yOYIUOKv8348hDwgFI2O9mKZPf8rwxGUBgHR9v7Y';
        $client->redirect = '';
        $client->revoked = false;
        $client->personal_access_client = true;
        $client->password_client = false;
        $client->save();

        // Set environment variables for the test
        config(['passport.personal_access_client.id' => $client->id]);
        config(['passport.personal_access_client.secret' => $client->secret]);

        // Create a user and authenticate with Passport
        $user = User::factory()->create();
        $this->token = $user->createToken('TestToken', ['manage-posts'])->accessToken;
        $this->user = $user;
    }

    /**
     * Test index method.
     */
    public function test_can_list_all_posts()
    {
        // Create posts
        Post::factory()->count(5)->create();

        // Act: Make a GET request to the index route
        $response = $this->getJson(route('posts.index'), [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Assert: Check response status and content
        $response->assertStatus(200)->assertJsonCount(5);
    }

    /**
     * Test store method.
     */
    public function test_can_create_post()
    {
        // Create post data
        $postData = Post::factory()->make()->toArray();

        // Act: Make a POST request to the store route
        $response = $this->postJson(route('posts.store'), $postData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Assert: Check response and database
        $response->assertStatus(201)->assertJson(['message' => 'Post created successfully']);
        $this->assertDatabaseHas('posts', ['title' => $postData['title']]);
    }

    /**
     * Test store method with rate limiting.
     */
    public function test_rate_limiting_on_post_creation()
    {
        // Create post data
        $postData = Post::factory()->make()->toArray();

        // Make 5 POST requests successfully
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('posts.store'), $postData, [
                'Authorization' => 'Bearer ' . $this->token,
            ]);
            $response->assertStatus(201);
        }

        // Act: Make the 6th POST request
        $response = $this->postJson(route('posts.store'), $postData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Assert: Check rate limiting
        $response->assertStatus(429);
    }

    /**
     * Test show method.
     */
    public function test_can_show_post()
    {
        // Create a post for the authenticated user
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Act: Make a GET request to show the post
        $response = $this->getJson(route('posts.show', $post), [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Assert: Check response status
        $response->assertStatus(200);
    }

    /**
     * Test update method.
     */
    public function test_can_update_post()
    {
        // Create a post for the authenticated user
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $updatedData = ['title' => 'Updated Title', 'content' => 'Updated Content'];

        // Act: Make a PUT request to update the post
        $response = $this->putJson(route('posts.update', $post), $updatedData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Assert: Check response and database
        $response->assertStatus(200)->assertJson(['message' => 'Post updated successfully']);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated Title']);
    }

    /**
     * Test destroy method.
     */
    public function test_can_delete_post()
    {
        // Create a post for the authenticated user
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Act: Make a DELETE request to delete the post
        $response = $this->deleteJson(route('posts.destroy', $post), [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Assert: Check response
        $response->assertStatus(200);
    }

    public function test_cannot_show_post_of_another_user(){
        // Create a post for the authenticated user
        $post = Post::factory()->create();

        // Create another user
        $user = User::factory()->create();
        $another_token = $user->createToken('TestToken', ['manage-posts'])->accessToken;

        // Act: Make a GET request to update the post
        $response = $this->getJson(route('posts.update', $post), [
            'Authorization' => 'Bearer ' . $another_token,
        ]);

        // Assert: Check response
        $response->assertStatus(403);
    }

    public function test_cannot_update_post_of_another_user(){
        // Create a post for the authenticated user
        $post = Post::factory()->create();
        // Create another user
        $user = User::factory()->create();
        $another_token = $user->createToken('TestToken', ['manage-posts'])->accessToken;
        // Act: Make a PUT request to update the post
        $response = $this->putJson(route('posts.update', $post), [], [
            'Authorization' => 'Bearer ' . $another_token,
        ]);

        // Assert: Check response
        $response->assertStatus(403);
    }

    public function test_cannot_delete_post_of_another_user(){
        // Create a post for an existing random user
        $post = Post::factory()->create();

        // Create another user
        $user = User::factory()->create();
        $another_token = $user->createToken('TestToken', ['manage-posts'])->accessToken;

        // Act: Make a DELETE request to update the post
        $response = $this->deleteJson(route('posts.destroy', $post), [], [
            'Authorization' => 'Bearer ' . $another_token,
        ]);

        // Assert: Check response
        $response->assertStatus(403);
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
        // Mock the Post model to throw an exception when trying to update
        $this->instance(Post::class, Mockery::mock(Post::class, function ($mock) {
            $mock->shouldReceive('update')->andThrow(new Exception('Post update failed'));
        }));
        
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $updatedData = ['title' => 'Updated Title', 'content' => 'Updated Content'];

        $response = $this->putJson(route('posts.update', $post), $updatedData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(500);
    }

    public function test_delete_post_throws_exception()
    {
        // Mock the Post model to throw an exception when trying to update
        $this->instance(Post::class, Mockery::mock(Post::class, function ($mock) {
            $mock->shouldReceive('destroy')->andThrow(new Exception('Post delete failed'));
        }));
        
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson(route('posts.update', $post), [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(500);
    }

    public function test_show_specific_users_posts(){
        // Create posts
        Post::factory()->count(5)->create(['user_id' => $this->user->id]); 
        
        // Act: Make a GET request to update the post
        $response = $this->user->posts()->get();

        // Assert: Check response content
        $this->assertCount(5, $response);
    }

    public function test_show_the_user_of_a_post(){
        // Create a post for the authenticated user
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Act: Make a GET request to update the post
        $response = $post->user;

        // Assert: Check response content
        $this->assertEquals($this->user->id, $response->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}