<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'login')->name('login');

Route::post('/login', function(Request $request){
    $credentials = $request->only('email', 'password');
    if(Auth::attempt($credentials)){
        $user = Auth::user();
        return redirect()->intended('/');
    }
    else{
        return jsonResponse(['message' => 'Wrong Credentials'], 400);
    }
});

Route::get('/logout', function(){
    Auth::logout();
    return redirect()->route('login');
});


