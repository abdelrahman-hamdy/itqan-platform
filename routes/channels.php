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

// WireChat participant channels (format: participant.{encodedType}.{userId})
// The encodedType is hex-encoded class name (e.g., 4170705c4d6f64656c735c55736572 = App\Models\User)
Broadcast::channel('participant.{encodedType}.{userId}', function ($user, $encodedType, $userId) {
    // Allow user to listen to their own participant channel
    return (int) $user->id === (int) $userId;
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

// WireChat conversation channel for MessageCreated and MessageDeleted events
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is a participant in this WireChat conversation
    $isParticipant = \Namu\WireChat\Models\Participant::where('conversation_id', $conversationId)
        ->where('participantable_type', \App\Models\User::class)
        ->where('participantable_id', $user->id)
        ->exists();

    return $isParticipant;
});

// Global presence channel for all authenticated users (for online status)
Broadcast::channel('online', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar ?? null,
            'user_type' => $user->user_type,
        ];
    }
    return false;
});

// Presence channel for specific academy (multi-tenancy support)
Broadcast::channel('online.academy.{academyId}', function ($user, $academyId) {
    if ($user && (int) $user->academy_id === (int) $academyId) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar ?? null,
            'user_type' => $user->user_type,
        ];
    }
    return false;
});

// Presence channel for WireChat conversations
Broadcast::channel('presence-conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is a participant in this conversation
    $isParticipant = \Namu\WireChat\Models\Participant::where('conversation_id', $conversationId)
        ->where('participantable_type', \App\Models\User::class)
        ->where('participantable_id', $user->id)
        ->whereNull('exited_at')
        ->exists();

    if ($isParticipant) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar ?? null,
            'user_type' => $user->user_type,
        ];
    }

    return false;
});
