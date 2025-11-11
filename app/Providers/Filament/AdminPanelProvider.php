<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AcademyContext;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->login()
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->brandName('منصة إتقان - لوحة التحكم')
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(asset('images/favicon.ico'))
            ->resources($this->getResources())
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\PlatformOverviewWidget::class,
                \App\Filament\Widgets\QuranOverviewWidget::class,
                \App\Filament\Widgets\AcademyStatsWidget::class,
                \App\Filament\Widgets\AcademyContextWidget::class,
                \App\Filament\Widgets\RecentActivitiesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                AcademyContext::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->sidebarFullyCollapsibleOnDesktop()
            ->navigationGroups([
                'إدارة النظام',
                'إدارة الأكاديميات',
                'إدارة المستخدمين',
                'إدارة القرآن',
                'النظام الأكاديمي',
                'إدارة التعليم الأكاديمي',
                'إدارة الدورات المسجلة',
                'الإعدادات',
                'الإعدادات العامة',
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::topbar.start',
                fn (): string => view('filament.hooks.academy-selector')->render()
            );
    }

    protected function getResources(): array
    {
        // Register all resources - each resource will handle its own visibility through canViewAny()
        return [
            // إدارة النظام - Super Admin Resources
            \App\Filament\Resources\AcademyManagementResource::class,
            \App\Filament\Resources\AcademyGeneralSettingsResource::class,

            // إدارة المستخدمين - Academy Level
            \App\Filament\Resources\UserResource::class,
            \App\Filament\Resources\AdminResource::class,
            \App\Filament\Resources\StudentProfileResource::class,
            \App\Filament\Resources\ParentProfileResource::class,
            \App\Filament\Resources\SupervisorProfileResource::class,
            \App\Filament\Resources\AcademicTeacherProfileResource::class,

            // إدارة القرآن - Super Admin Resources
            \App\Filament\Resources\QuranCircleResource::class,
            \App\Filament\Resources\QuranPackageResource::class,

            // إدارة القرآن - Academy Level Resources
            \App\Filament\Resources\QuranTeacherProfileResource::class,
            \App\Filament\Resources\QuranSubscriptionResource::class,
            \App\Filament\Resources\QuranTrialRequestResource::class,

            // إدارة التعليم الأكاديمي
            \App\Filament\Resources\InteractiveCourseResource::class,
            \App\Filament\Resources\AcademicGradeLevelResource::class,
            \App\Filament\Resources\SubjectResource::class,
            \App\Filament\Resources\RecordedCourseResource::class,
            \App\Filament\Resources\AcademicPackageResource::class,

            // الإعدادات
            // Business Services - Access controlled by resource authorization
            \App\Filament\Resources\BusinessServiceCategoryResource::class,
            \App\Filament\Resources\BusinessServiceRequestResource::class,
            \App\Filament\Resources\PortfolioItemResource::class,
        ];
    }

    public function boot(): void
    {
        // Temporarily increase memory limit to debug memory exhaustion
        ini_set('memory_limit', '2048M');

        // Set Arabic locale for the admin panel
        app()->setLocale('ar');
    }
}
