<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class PostController extends Controller
{
    public function initializeMiddleware(): void
    {
        $this->middleware('scope:manage-resources');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Post::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->input();
        
        //Rate Limitng
        $user_id = $input['user_id'];
        $key = 'store-posts|' . $user_id;

        if(RateLimiter::tooManyAttempts($key, 5)){
            return response()->json(['message' => 'Too many posts created. Please wait ' . RateLimiter::availableIn($key) . ' seconds before retrying.'], 429);
        }

        RateLimiter::hit($key, 60);
        //End Rate Limiting
        
        try{
            Post::create($input);
        }
        catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json(['message' => 'Post creation failed'], 500);
        }
        return response()->json(['message' => 'Post created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Post $post)
    {
        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $input = $request->input();
        try{
            $post->update([
                'title'=>$input['title'], 
                'content'=>$input['content']
            ]);
        }
        catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json(['message' => 'Post update failed'], 500);
        }
        return response()->json(['message' => 'Post updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Post $post)
    {    

        try{
            $post->delete();
        }
        catch(Exception $e){
            Log::error($e->getMessage());
            return response()->json(['message' => 'Post deletion failed'], 500);
        }
        return response()->json(['message' => 'Post deleted successfully'], 200);
    }
}
