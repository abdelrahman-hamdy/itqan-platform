<?php

namespace App\Providers\Filament;

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
use App\Services\AcademyContextService;
use App\Http\Middleware\AcademyContext;

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
                'إدارة المستخدمين',
                'إدارة القرآن',
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
        $resources = [
            // إدارة المستخدمين - Academy Level
            \App\Filament\Resources\UserResource::class,
            \App\Filament\Resources\AdminResource::class,
            \App\Filament\Resources\StudentProfileResource::class,
            \App\Filament\Resources\ParentProfileResource::class,
            \App\Filament\Resources\SupervisorProfileResource::class,
            \App\Filament\Resources\AcademicTeacherProfileResource::class,
            
            // إدارة التعليم الأكاديمي
            \App\Filament\Resources\InteractiveCourseResource::class,
            \App\Filament\Resources\GradeLevelResource::class,
            \App\Filament\Resources\SubjectResource::class,
            \App\Filament\Resources\RecordedCourseResource::class,
            
            // الإعدادات
            \App\Filament\Resources\AcademicSettingsResource::class,
            \App\Filament\Resources\GoogleSettingsResource::class,
            \App\Filament\Resources\VideoSettingsResource::class,
        ];

        // Add resources based on user role
        if (AcademyContextService::isSuperAdmin()) {
            // Super Admin sees global resources
            $resources = array_merge($resources, [
                \App\Filament\Resources\SuperAdminQuranTeacherResource::class,
                \App\Filament\Resources\SuperAdminQuranSubscriptionResource::class,
                \App\Filament\Resources\SuperAdminQuranTrialRequestResource::class,
                \App\Filament\Resources\AcademyManagementResource::class,
                \App\Filament\Resources\QuranCircleResource::class,
                \App\Filament\Resources\QuranPackageResource::class,
            ]);
        } else {
            // Regular admins see academy-scoped resources
            $resources = array_merge($resources, [
                \App\Filament\Resources\QuranTeacherProfileResource::class,
                \App\Filament\Resources\QuranSubscriptionResource::class,
                \App\Filament\Resources\QuranTrialRequestResource::class,
                \App\Filament\Resources\QuranCircleResource::class,
                \App\Filament\Resources\QuranPackageResource::class,
            ]);
        }

        return $resources;
    }

    public function boot(): void
    {
        // Temporarily increase memory limit to debug memory exhaustion
        ini_set('memory_limit', '2048M');
        
        // Set Arabic locale for the admin panel
        app()->setLocale('ar');
    }
}
