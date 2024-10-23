<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Validator;

Route::post('/register', function(Request $request){
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    if($validator->fails()){
        return response()->json(['message' => 'Validation failed '.$validator->errors()], 422);
    }

    $user = User::create([
        'name' => $validator->validated()['name'],
        'email' => $validator->validated()['email'],
        'password' => Hash::make($validator->validated()['password']),
    ]);

    return response()->json(['message' => 'User created successfully', 'user'=>$user], 201);
})->name('register');



Route::post('/login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        // Check for existing valid token
        $existingToken = $user->tokens()
            ->where('name', 'Personal Access Token')
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();
        if ($existingToken) {
            return response()->json([
                'message' => 'Authenticated with old token. COntact administrators to issue new token if needed',
            ], 200);
        }

        $token = Auth::user()->createToken('Personal Access Token',['manage-posts'])->accessToken;
        return response()->json([
            'message' => 'Authenticated',
            'token' => $token
        ], 200);
    }
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('api-login');

Route::middleware(['auth:api', 'scope:manage-posts'])->group(function () {
    Route::resource('posts', PostController::class);
});