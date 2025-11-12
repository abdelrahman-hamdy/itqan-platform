<?php

use App\Http\Controllers\Api\Chat\ChatApiController;
use Illuminate\Support\Facades\Route;

/**
 * Mobile-Ready Chat API Routes
 *
 * All routes are prefixed with /api/chat
 * All routes require authentication via Sanctum
 *
 * Base URL: https://your-domain.com/api/chat
 */

Route::middleware(['auth:sanctum'])->group(function () {

    // Contacts Management
    Route::get('/contacts', [ChatApiController::class, 'getContacts'])
        ->name('api.chat.contacts');

    Route::get('/search', [ChatApiController::class, 'searchUsers'])
        ->name('api.chat.search');

    Route::get('/user-info', [ChatApiController::class, 'getUserInfo'])
        ->name('api.chat.user-info');

    // Messages Management
    Route::get('/messages', [ChatApiController::class, 'getMessages'])
        ->name('api.chat.messages.index');

    Route::post('/messages', [ChatApiController::class, 'sendMessage'])
        ->name('api.chat.messages.send');

    Route::delete('/messages', [ChatApiController::class, 'deleteMessage'])
        ->name('api.chat.messages.delete');

    Route::post('/messages/mark-read', [ChatApiController::class, 'markAsRead'])
        ->name('api.chat.messages.mark-read');

    // Unread Count
    Route::get('/unread-count', [ChatApiController::class, 'getUnreadCount'])
        ->name('api.chat.unread-count');
});
