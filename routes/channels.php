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

// Conversation private channel for typing indicators and real-time updates
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is part of this conversation
    // This checks if the user has messages with the other participant
    $hasMessages = \App\Models\ChMessage::where(function($query) use ($user, $conversationId) {
        $query->where(['from_id' => $user->id, 'to_id' => $conversationId])
              ->orWhere(['from_id' => $conversationId, 'to_id' => $user->id]);
    })->exists();

    return $hasMessages ? true : false;
});

// Presence channel for group chats to show online users
Broadcast::channel('presence-group.{groupId}', function ($user, $groupId) {
    // Check if user is a member of this group
    $member = \App\Models\ChatGroupMember::where('group_id', $groupId)
        ->where('user_id', $user->id)
        ->first();

    if ($member) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'role' => $member->role,
        ];
    }

    return false;
});

// Presence channel for tracking online status in conversations
Broadcast::channel('presence-chat.{conversationId}', function ($user, $conversationId) {
    // For now, allow if user is authenticated
    // You can add more specific logic here
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
    ];
});
