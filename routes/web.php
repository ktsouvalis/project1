<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', function(){
    return view('register');
});

Route::post('/register', function (Request $request) {
    $validatedData = $request->validate([
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    $user = User::create([
        'name' => 'ktsouvalis',
        'email' => $validatedData['email'],
        'password' => Hash::make($validatedData['password']),
    ]);

    return redirect('/register')->with('success', 'User created successfully');
})->name('register');

Route::get('/login', function(){
    return view('login');
})->name('login');

Route::post('/login', function(Request $request){
    if(auth()->attempt(['email'=>$request['email'], 'password'=>$request['password']])){
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ]);
});

Route::get('/logout', function(){
    Auth::logout();
    return redirect('/');
});

//FOR POSTMAN
 // Route::get('/redirect', function (Request $request) {
//     $query = http_build_query([
//         'client_id' => '3',
//         'redirect_uri' => 'https://oauth.pstmn.io/v1/callback',
//         'response_type' => 'code',
//         'scope' => '',
//         'prompt' => 'consent',
//     ]);

//     return redirect('http://localhost:8000/oauth/authorize?' . $query);
// })->middleware('auth');

// FOR ANOTHER APP RUNNING ON redirect_uri
Route::get('/redirect/{client}/{scope}', function (Request $request, Client $client, $scope) {
    $query = http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => $client->redirect,
        'response_type' => 'code',
        'scope' => $scope,
        'prompt' => 'consent',
    ]);

    return redirect('http://localhost:8000/oauth/authorize?' . $query);
})->middleware('auth');