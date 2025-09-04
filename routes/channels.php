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

// Chatify private channels authorization
Broadcast::channel('chatify.{userId}', function ($user, $userId) {
    // Allow user to listen to their own private channel
    return (int) $user->id === (int) $userId;
});

// Also support the "private-" prefix format that Pusher uses
Broadcast::channel('private-chatify.{userId}', function ($user, $userId) {
    // Allow user to listen to their own private channel
    return (int) $user->id === (int) $userId;
});

// Public test channels for WebSocket testing without authentication
Broadcast::channel('chatify-test.{userId}', function ($user, $userId) {
    // Always return true for public test channels
    return true;
});
