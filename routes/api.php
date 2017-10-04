<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', 'Api\Auth\OAuthController@register');
Route::post('login', 'Api\Auth\OAuthController@login');
Route::post('refresh', 'Api\Auth\OAuthController@refresh');
Route::get('existUser', 'Api\UserController@exist');


Route::middleware('auth:api')->group(function () {
    Route::post('logout', 'Api\Auth\OAuthController@logout');
    Route::get('user', 'Api\UserController@getUser');
});
