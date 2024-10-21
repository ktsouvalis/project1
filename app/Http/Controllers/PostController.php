<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected $authenticatedUser;

    public function __construct()
    {
        $this->authenticatedUser = Auth::guard('api')->user();
    }

    public function initializeMiddleware(): void
    {
        $this->middleware('scope:manage-posts');
        
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = $this->authenticatedUser->posts;
        return response()->json($posts);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->input();
        $input['user_id'] = $this->authenticatedUser->id;
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        //TODO: policy check and deletion
    }
}
