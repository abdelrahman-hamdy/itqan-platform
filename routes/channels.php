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

// Session attendance channel — user must belong to the same academy as the session
// Covers QuranSession and AcademicSession (both use integer primary keys in same academy)
Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    if (! $user || ! $user->academy_id) {
        return false;
    }
    $academyId = $user->academy_id;

    return \App\Models\QuranSession::where('id', $sessionId)->where('academy_id', $academyId)->exists()
        || \App\Models\AcademicSession::where('id', $sessionId)->where('academy_id', $academyId)->exists();
});

// Notification channel for real-time notifications
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Academy meetings control channel — admins and teachers of the same academy
Broadcast::channel('academy.{academyId}.meetings', function ($user, $academyId) {
    if (! $user || ! $user->academy_id) {
        return false;
    }

    return (int) $user->academy_id === (int) $academyId;
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
