<?php

/*
|--------------------------------------------------------------------------
| Help Center Routes
|--------------------------------------------------------------------------
|
| Routes for the in-app help center (مركز المساعدة).
|
| These routes are intentionally NOT scoped to a specific subdomain — the
| help content is identical for all academies/tenants on the platform.
| Using a plain /help prefix means the routes work on any domain or
| subdomain the user is currently authenticated on, whether they arrive
| from the Academy panel, Admin panel, or any web portal.
|
| Role-based article access is enforced in HelpCenterController.
|
| URL patterns (works on any domain):
|   GET /help                   → HelpCenterController@index
|   GET /help/search            → HelpCenterController@search
|   GET /help/common/{slug}     → HelpCenterController@commonArticle
|   GET /help/{role}/{slug}     → HelpCenterController@article
|
| NOTE: common/{slug} MUST be registered before {role}/{slug} to prevent
| "common" from being captured as a role wildcard.
|
*/

use App\Http\Controllers\HelpCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('help')->name('help.')->group(function () {

    Route::get('/', [HelpCenterController::class, 'index'])->name('index');

    Route::get('/search', [HelpCenterController::class, 'search'])->name('search');

    // Must be before /{role}/{slug}
    Route::get('/common/{slug}', [HelpCenterController::class, 'commonArticle'])->name('common');

    Route::get('/{role}/{slug}', [HelpCenterController::class, 'article'])->name('article');

});
