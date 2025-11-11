<?php

use Illuminate\Support\Facades\Broadcast;

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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat private channels authorization
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    // Allow user to listen to their own private channel
    return (int) $user->id === (int) $userId;
});

// Also support the "private-" prefix format that Reverb/Pusher uses
Broadcast::channel('private-chat.{userId}', function ($user, $userId) {
    // Allow user to listen to their own private channel
    return (int) $user->id === (int) $userId;
});
