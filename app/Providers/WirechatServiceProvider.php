<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class WirechatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge custom configuration
        $this->mergeConfigFrom(
            config_path('wirechat.php'),
            'wirechat'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure WireChat settings for multi-tenancy
        $this->configureWirechat();

        // Fix WireChat broadcasting for multi-tenancy
        $this->fixWirechatBroadcasting();

        // Share user data with all views
        View::composer('wirechat::*', function ($view) {
            if (auth()->check()) {
                $view->with('currentUser', auth()->user());
                $view->with('academyId', auth()->user()->academy_id);
            }
        });

        // Register custom Blade directives
        $this->registerBladeDirectives();
    }

    /**
     * Fix WireChat broadcasting to work with multi-tenancy
     *
     * WireChat's MessageCreated event is queued by default, which fails in multi-tenancy
     * This listener intercepts the event and re-broadcasts it immediately
     */
    protected function fixWirechatBroadcasting(): void
    {
        // Listen to WireChat's MessageCreated event and re-broadcast immediately
        \Illuminate\Support\Facades\Event::listen(
            \Namu\WireChat\Events\MessageCreated::class,
            function ($event) {
                // The original event is queued, but we need immediate broadcast for multi-tenancy
                // We'll broadcast it again immediately to ensure it works

                // Log for debugging
                \Illuminate\Support\Facades\Log::info('🔧 [WireChat Fix] Re-broadcasting MessageCreated immediately', [
                    'message_id' => $event->message->id,
                    'conversation_id' => $event->message->conversation_id,
                ]);

                // Broadcast immediately (not queued)
                broadcast(new \App\Events\WireChat\MessageCreatedNow($event->message))->toOthers();
            }
        );
    }

    /**
     * Configure WireChat settings
     */
    protected function configureWirechat(): void
    {
        // Set dynamic configurations
        config([
            'wirechat.routes.prefix' => 'chat',
            'wirechat.routes.middleware' => ['web', 'auth'],
            'wirechat.home_route' => '/dashboard',
            'wirechat.user_model' => \App\Models\User::class,
            'wirechat.color' => '#6366f1',
            'wirechat.layout' => 'wirechat::layouts.app', // Use WireChat layout with sidebar included
        ]);
    }

    /**
     * Register custom Blade directives for WireChat
     */
    protected function registerBladeDirectives(): void
    {
        // Directive to check if user can chat with another user
        Blade::if('canChatWith', function ($user) {
            if (!auth()->check()) {
                return false;
            }

            $currentUser = auth()->user();

            // Check if both users are in the same academy
            if ($currentUser->academy_id !== $user->academy_id) {
                // Only super admins can chat across academies
                return $currentUser->user_type === 'super_admin';
            }

            // Check user type permissions
            return $this->checkChatPermission($currentUser, $user);
        });
    }

    /**
     * Check if two users can chat based on their types
     */
    protected function checkChatPermission($currentUser, $targetUser): bool
    {
        $currentType = $currentUser->user_type;
        $targetType = $targetUser->user_type;

        // Super admins can chat with anyone
        if ($currentType === 'super_admin' || $targetType === 'super_admin') {
            return true;
        }

        // Academy admins can chat with anyone in their academy
        if ($currentType === 'academy_admin' || $targetType === 'academy_admin') {
            return true;
        }

        // Teachers can chat with students, parents, and other teachers
        $teacherTypes = ['quran_teacher', 'academic_teacher'];
        if (in_array($currentType, $teacherTypes)) {
            return in_array($targetType, ['student', 'parent', 'supervisor', ...$teacherTypes]);
        }

        // Students can chat with teachers and supervisors
        if ($currentType === 'student') {
            return in_array($targetType, [...$teacherTypes, 'supervisor']);
        }

        // Parents can chat with teachers and supervisors
        if ($currentType === 'parent') {
            return in_array($targetType, [...$teacherTypes, 'supervisor']);
        }

        // Supervisors can chat with everyone except other supervisors
        if ($currentType === 'supervisor') {
            return $targetType !== 'supervisor';
        }

        return false;
    }
}