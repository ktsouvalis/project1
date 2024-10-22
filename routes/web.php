<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

/////////////////////////////////////////ISSUING ACCESS TOKENS//////////////////////////////////////////////////////
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
// Route::get('/redirect/{client}/{scope}', function (Request $request, Client $client, $scope) {
//     $query = http_build_query([
//         'client_id' => $client->id,
//         'redirect_uri' => $client->redirect,
//         'response_type' => 'code',
//         'scope' => $scope,
//         'prompt' => 'consent',
//     ]);

//     return redirect('http://localhost:8000/oauth/authorize?' . $query);
// })->middleware('auth');
/////////////////////////////////////////END ISSUING ACCESS TOKENS//////////////////////////////////////////////////////

