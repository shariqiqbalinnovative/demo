<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

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
Broadcast::routes(['middleware' => ['auth']]);


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});



// Broadcast::channel('notification', function ($user) {
//     return true;
// });
// Broadcast::channel('notification', function ($user) {
// Broadcast::channel('notification.{id}', function ($user , $id) {
//     // return (int) $user->id === (int) $id;
//     return !is_null($user);
//     // return true;
// });
