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


/////////////////////////////////////////ISSUING PERSONAL ACCESS TOKENS//////////////////////////////////////////////////////
// Route::post('/login', function (Request $request) {
//     $credentials = $request->only('email', 'password');
//     $user = User::where('email', $credentials['email'])->first();
//     if ($user && Hash::check($credentials['password'], $user->password)) {
//         $token = $user->createToken('Personal Access Token')->accessToken;

//         return response()->json([
//             'message' => 'Authenticated',
//             'token' => $token
//         ], 200);
//     }
//     return response()->json(['message' => 'Unauthenticated'], 401);
// })->name('login');


// Route::get('/posts', [PostController::class, 'index'])
//     ->middleware('auth:api', );

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');
///////////////////////////////////////// END ISSUING PERSONAL ACCESS TOKENS//////////////////////////////////////////////////////

Route::resource('posts', PostController::class)
    ->middleware('client');




