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
Route::get('test', 'Api\TestController@test');

Route::post('register', 'Api\Auth\OAuthController@register');
Route::post('login', 'Api\Auth\OAuthController@login');
Route::post('refresh', 'Api\Auth\OAuthController@refresh');
Route::get('existUser', 'Api\UserController@exist');
Route::post('getAdverts', 'Api\AdvertController@getAdverts');
Route::get('advert/{id}', 'Api\AdvertController@show');

Route::middleware('auth:api')->group(function () {
    Route::post('logout', 'Api\Auth\OAuthController@logout');
    Route::get('user', 'Api\UserController@getUser');
    Route::post('user/set', 'Api\UserController@setProperty');
});
