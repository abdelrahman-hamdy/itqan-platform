<?php

/*
|--------------------------------------------------------------------------
| Chat Routes (WireChat)
|--------------------------------------------------------------------------
| Override WireChat package routes to provide Arabic titles and subdomain support.
*/

use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    Route::middleware(config('wirechat.routes.middleware'))
        ->prefix(config('wirechat.routes.prefix'))
        ->group(function () {
            Route::get('/', \App\Livewire\Pages\Chats::class)->name('chats');

            Route::get('/start-with/{user}', function ($subdomain, \App\Models\User $user) {
                // Log the attempt for debugging
                \Log::info('Chat start-with route called', [
                    'subdomain' => $subdomain,
                    'auth_user_id' => auth()->id(),
                    'target_user_id' => $user->id,
                    'target_user_name' => $user->name,
                ]);

                // Get or create conversation with the specified user
                $conversation = auth()->user()->getOrCreatePrivateConversation($user);

                if (!$conversation) {
                    \Log::error('Failed to create conversation in route', [
                        'auth_user_id' => auth()->id(),
                        'target_user_id' => $user->id,
                    ]);
                    // If conversation creation fails, redirect to chats list with error
                    return redirect()->route('chats', ['subdomain' => $subdomain])
                        ->with('error', 'حدث خطأ في إنشاء المحادثة. يرجى المحاولة لاحقاً.');
                }

                \Log::info('Conversation created/found successfully', [
                    'conversation_id' => $conversation->id,
                ]);

                return redirect()->route('chat', [
                    'subdomain' => $subdomain,
                    'conversation' => $conversation->id
                ]);
            })->name('chat.start-with');

            Route::get('/{conversation}', \App\Livewire\Pages\Chat::class)->middleware('belongsToConversation')->name('chat');
        });
});
