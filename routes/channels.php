<?php

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
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

// Session attendance channel — user must belong to the same academy as the session.
// Covers QuranSession, AcademicSession, and InteractiveCourseSession.
// All three session types use auto-increment integer PKs, so IDs can overlap across types.
// We try each type in turn and verify academy_id tenant isolation on every lookup.
// InteractiveCourseSession has no academy_id column — resolved through its course relationship.
Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    if (! $user || ! $user->academy_id) {
        return false;
    }
    $academyId = $user->academy_id;

    if (QuranSession::where('id', $sessionId)->where('academy_id', $academyId)->exists()) {
        return true;
    }

    if (AcademicSession::where('id', $sessionId)->where('academy_id', $academyId)->exists()) {
        return true;
    }

    // InteractiveCourseSession has no direct academy_id column — resolve via course
    if (InteractiveCourseSession::where('id', $sessionId)
        ->whereHas('course', function ($query) use ($academyId) {
            $query->where('academy_id', $academyId);
        })
        ->exists()) {
        return true;
    }

    return false;
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
