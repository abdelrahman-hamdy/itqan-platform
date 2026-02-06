<?php

namespace App\Providers;

use App\Support\TranslationChecker\SafeTranslationManager;
use Bottelet\TranslationChecker\Sort\SorterContract;
use Bottelet\TranslationChecker\TranslationManager;
use Bottelet\TranslationChecker\Translator\TranslatorContract;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider to override the translation checker's TranslationManager
 * with our safe version that handles parsing errors gracefully.
 */
class TranslationCheckerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override the TranslationManager binding with our safe version
        $this->app->bind(TranslationManager::class, function ($app) {
            return new SafeTranslationManager(
                $app->make(SorterContract::class),
                $app->make(TranslatorContract::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
