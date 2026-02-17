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

// Notification channel for real-time notifications
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| WireChat Broadcast Channels
|--------------------------------------------------------------------------
|
| WireChat registers its channels via loadRoutesFrom() in its service provider,
| which is skipped when routes are cached in production. We load them here
| since withBroadcasting() uses require() directly, bypassing route cache.
|
*/

require base_path('vendor/namu/wirechat/routes/channels.php');
