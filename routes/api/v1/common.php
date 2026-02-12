<?php

use App\Http\Controllers\Api\V1\Chat\MessageReactionController;
use App\Http\Controllers\Api\V1\Common\ChatController;
use App\Http\Controllers\Api\V1\Common\MeetingTokenController;
use App\Http\Controllers\Api\V1\Common\NotificationController;
use App\Http\Controllers\Api\V1\Common\SupervisedChatController;
use App\Http\Controllers\Api\V1\ProfileOptionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Common Routes
|--------------------------------------------------------------------------
|
| Common routes accessible by all authenticated users (Students, Parents, Teachers)
| Requires: auth:sanctum, api.resolve.academy, api.academy.active, api.user.academy
|
*/

// Notifications
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])
        ->name('api.v1.notifications.index');

    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('api.v1.notifications.unread-count');

    Route::put('/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('api.v1.notifications.mark-read');

    Route::put('/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('api.v1.notifications.mark-all-read');

    Route::delete('/{id}', [NotificationController::class, 'destroy'])
        ->name('api.v1.notifications.destroy');

    Route::delete('/clear-all', [NotificationController::class, 'clearAll'])
        ->name('api.v1.notifications.clear-all');

    // Device tokens for push notifications (FCM)
    Route::post('/device-token', [NotificationController::class, 'registerDeviceToken'])
        ->name('api.v1.notifications.device-token.register');

    Route::delete('/device-token', [NotificationController::class, 'removeDeviceToken'])
        ->name('api.v1.notifications.device-token.remove');
});

// Meeting Tokens & Controls (LiveKit)
Route::prefix('meetings')->group(function () {
    Route::get('/{sessionType}/{sessionId}/token', [MeetingTokenController::class, 'getToken'])
        ->where('sessionType', 'quran|academic|interactive')
        ->name('api.v1.meetings.token');

    Route::get('/{sessionType}/{sessionId}/info', [MeetingTokenController::class, 'getInfo'])
        ->where('sessionType', 'quran|academic|interactive')
        ->name('api.v1.meetings.info');

    Route::post('/{sessionType}/{sessionId}/start', [MeetingTokenController::class, 'startMeeting'])
        ->where('sessionType', 'quran|academic|interactive')
        ->name('api.v1.meetings.start');

    Route::post('/{sessionType}/{sessionId}/end', [MeetingTokenController::class, 'endMeeting'])
        ->where('sessionType', 'quran|academic|interactive')
        ->name('api.v1.meetings.end');

    Route::get('/{sessionType}/{sessionId}/participants', [MeetingTokenController::class, 'getParticipants'])
        ->where('sessionType', 'quran|academic|interactive')
        ->name('api.v1.meetings.participants');

    Route::post('/{sessionType}/{sessionId}/kick', [MeetingTokenController::class, 'kickParticipant'])
        ->where('sessionType', 'quran|academic|interactive')
        ->name('api.v1.meetings.kick');
});

// Chat (WireChat)
Route::prefix('chat')->group(function () {
    Route::get('/conversations', [ChatController::class, 'conversations'])
        ->name('api.v1.chat.conversations.index');

    Route::post('/conversations', [ChatController::class, 'createConversation'])
        ->name('api.v1.chat.conversations.create');

    // Archived conversations - MUST be before /conversations/{id}
    Route::get('/conversations/archived', [ChatController::class, 'archivedConversations'])
        ->name('api.v1.chat.conversations.archived');

    Route::get('/conversations/{id}', [ChatController::class, 'showConversation'])
        ->name('api.v1.chat.conversations.show');

    Route::get('/conversations/{id}/messages', [ChatController::class, 'messages'])
        ->name('api.v1.chat.conversations.messages');

    Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage'])
        ->name('api.v1.chat.conversations.send');

    Route::put('/conversations/{id}/read', [ChatController::class, 'markAsRead'])
        ->name('api.v1.chat.conversations.read');

    Route::get('/unread-count', [ChatController::class, 'unreadCount'])
        ->name('api.v1.chat.unread-count');

    // Supervised chat creation
    Route::post('/supervised', [SupervisedChatController::class, 'createSupervisedChat'])
        ->name('api.v1.chat.supervised.create');

    Route::post('/supervisor-student', [SupervisedChatController::class, 'createSupervisorStudentChat'])
        ->name('api.v1.chat.supervisor-student.create');

    // Typing indicators
    Route::post('/conversations/{id}/typing', [ChatController::class, 'typing'])
        ->name('api.v1.chat.conversations.typing');

    // Message editing and deletion
    Route::put('/messages/{messageId}', [ChatController::class, 'editMessage'])
        ->name('api.v1.chat.messages.edit');

    Route::delete('/messages/{messageId}', [ChatController::class, 'deleteMessage'])
        ->name('api.v1.chat.messages.delete');

    // Message reactions
    Route::post('/messages/{messageId}/reactions', [MessageReactionController::class, 'store'])
        ->name('api.v1.chat.messages.reactions.store');

    Route::delete('/messages/{messageId}/reactions/{emoji}', [MessageReactionController::class, 'destroy'])
        ->name('api.v1.chat.messages.reactions.destroy');

    Route::get('/messages/{messageId}/reactions', [MessageReactionController::class, 'index'])
        ->name('api.v1.chat.messages.reactions.index');

    // Archive conversations
    Route::post('/conversations/{id}/archive', [ChatController::class, 'archiveConversation'])
        ->name('api.v1.chat.conversations.archive');

    Route::delete('/conversations/{id}/archive', [ChatController::class, 'unarchiveConversation'])
        ->name('api.v1.chat.conversations.unarchive');

    // Conversation details and media
    Route::get('/conversations/{id}/details', [ChatController::class, 'conversationDetails'])
        ->name('api.v1.chat.conversations.details');

    Route::get('/conversations/{id}/media', [ChatController::class, 'conversationMedia'])
        ->name('api.v1.chat.conversations.media');
});

// Profile Options (Form dropdown data)
Route::get('/profile-options', [ProfileOptionsController::class, 'index'])
    ->name('api.v1.profile-options');
