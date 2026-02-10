<?php

use App\Http\Controllers\Api\V1\Supervisor\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Supervisor Routes
|--------------------------------------------------------------------------
|
| Supervisor-specific functionality
| Requires: auth:sanctum, api.resolve.academy, api.academy.active, api.user.academy
|
*/

Route::middleware('api.is.supervisor')->group(function () {

    // Supervised Chat Groups
    Route::prefix('chat')->group(function () {
        Route::get('/supervised-groups', [ChatController::class, 'getSupervisedGroups'])
            ->name('api.v1.supervisor.chat.supervised-groups');

        Route::get('/supervised-groups/{id}/members', [ChatController::class, 'getGroupMembers'])
            ->name('api.v1.supervisor.chat.group-members');
    });
});
