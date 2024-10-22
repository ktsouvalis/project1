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
    //////////////////FOR PERSONAL ACCESS TOKENS//////////////////////
    // protected $authenticatedUser;

    // public function __construct()
    // {
    //     $this->authenticatedUser = Auth::guard('api')->user();
    // }
    /////////////////////////////////////////////////////////////////

    public function initializeMiddleware(): void
    {
        //FOR CLIENT CREDENTIALS TOKENS
        $this->middleware('scope:manage-resources');

        //FOR PERSONAL ACCESS TOKENS
        // $this->middleware('auth:api');
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

    // WHEN USING CLIENT CREDENTIALS TOKENS, SESSIONS ARE NOT AVAILABLE. 
    // SO WE NEED TO PSEUDO-LOGIN THE USER THAT SENDED THE REQUEST.
    // REQUIRES 'request_sender' KEY IN THE REQUEST BODY (as input hidden maybe)

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Post $post)
    {
        // $request_sender = $request->all()['request_sender'];
        // Auth::login(User::findOrFail($request_sender));
        // Gate::authorize('view', $post); // CHECK IF THE PSEUDO-LOGGED IN USER CAN VIEW THE POST
        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $input = $request->input();
        // $request_sender = $input['request_sender'];
        // Auth::login(User::findOrFail($request_sender));
        // Gate::authorize('update', $post); // CHECK IF THE PSEUDO-LOGGED IN USER CAN UPDATE THE POST
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
        // $request_sender = $request->all()['request_sender'];
        // Auth::login(User::findOrFail($request_sender));
        // Gate::authorize('delete', $post); // CHECK IF THE PSEUDO-LOGGED IN USER CAN DELETE THE POST
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
