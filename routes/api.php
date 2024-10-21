<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::resource('posts', PostController::class)
    ->middleware('auth:api');

// Route::get('/posts', [PostController::class, 'index'])
//     ->middleware(['auth:api', 'scope:list-posts']);