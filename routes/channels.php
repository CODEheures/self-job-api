<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
Broadcast::routes(['middleware' => 'auth:api']);
Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('new-answer-on.{id}', function ($user, $id) {
    $advert = \App\Advert::find($id);
    return ($advert && $advert->isAccessibleByUser($user));
});

Broadcast::channel('update-adverts-for-company.{id}', function ($user, $id) {
   return  (int)$user->company_id === (int)$id;
});