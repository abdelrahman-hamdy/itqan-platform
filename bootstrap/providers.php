<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\Filament\AcademicTeacherPanelProvider::class,
    App\Providers\Filament\AcademyPanelProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\SupervisorPanelProvider::class,
    App\Providers\Filament\TeacherPanelProvider::class,
    App\Providers\PaymentServiceProvider::class,
    App\Providers\WireChatServiceProvider::class,
    App\Providers\TranslationCheckerServiceProvider::class,
];

// Only load Telescope in local environment when package is installed
if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
