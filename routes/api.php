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

/**
 *
 * In this API user is only employers
 *
 */

// Post quiz answers of an advert
Route::post('quiz', 'Api\QuestionController@quizAnswers')->middleware('throttle:60');

// Get the quiz (questions list) of an advert
Route::get('quiz/{advertId}', 'Api\QuestionController@quiz')->middleware('throttle:60');



// Accepted high request throttle
Route::middleware('throttle:60')->group(function () {
    // Route for development tests
    Route::get('test', 'Api\TestController@test');

    // Get list of adverts: return the search
    Route::post('getAdverts', 'Api\AdvertController@getAdverts');

    // Show an advert
    Route::get('advert/{id}', 'Api\AdvertController@show');

    // Ask for new Password
    Route::post('resetPassword', 'Api\UserController@resetPassword');
    Route::get('resetPassword/{token}', 'Api\UserController@confirmResetPassword')->name('confirmResetPassword');

    // Auth routes
    Route::post('register', 'Api\Auth\OAuthController@register');
    Route::post('login', 'Api\Auth\OAuthController@login');
    Route::post('refresh', 'Api\Auth\OAuthController@refresh');
    Route::get('existUser', 'Api\UserController@exist');
    Route::get('isInvitedAndFreeUser', 'Api\UserController@isInvitedAndFree');
    Route::middleware('auth:api')->group(function () {

        // GET, SET and LOGOUT user
        Route::get('user', 'Api\UserController@getUser');
        Route::post('user/set', 'Api\UserController@setProperty');
        Route::post('logout', 'Api\Auth\OAuthController@logout');

        // Invite User
        Route::post('invite', 'Api\UserController@invite');

        // Get list of user advert
        Route::get('myAdverts', 'Api\AdvertController@getMyAdverts')->name('getMyAdverts');

        // Get list of answer of an advert
        Route::get('advert/answers/{id}', 'Api\AdvertController@getAdvertAnswers');

        // Post an advert
        Route::post('advert', 'Api\AdvertController@postAdvert');

        // Publish/unPublish an advert
        Route::put('advert/publish', 'Api\AdvertController@publishAdvert');

        // Delete an advert
        Route::delete('advert', 'Api\AdvertController@deleteAdvert');

        // Get the questions library (new, private, public)
        Route::get('question/library', 'Api\QuestionController@getLibrary')->name('getLibrary');

        // Remove a private question of a library
        Route::put('question/library/remove', 'Api\QuestionController@removeOfLibrary');

        // Change library type of question
        Route::put('question/library/type', 'Api\QuestionController@typeOfLibrary');

        //Pictures
        Route::group(['prefix' => 'picture'] , function () {
            Route::post('/', 'Api\PictureController@post');
            Route::delete('/', 'Api\PictureController@destroy');
        });

    });
});