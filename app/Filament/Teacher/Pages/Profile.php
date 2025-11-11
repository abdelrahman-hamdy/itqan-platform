<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use App\Services\CalendarService;
use Carbon\Carbon;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static string $view = 'filament.teacher.pages.profile';
    
    protected static ?string $navigationLabel = 'ملفي الشخصي';
    
    protected static ?string $title = 'ملفي الشخصي';
    
    protected static ?string $navigationGroup = 'ملفي الشخصي';
    
    protected static ?int $navigationSort = 1;

    public function getTitle(): string | Htmlable
    {
        return 'ملفي الشخصي';
    }

    public function getHeading(): string | Htmlable
    {
        return 'الملف الشخصي للمعلم';
    }

    public function mount(): void
    {
        // Check if user is a teacher
        if (!Auth::user()->isQuranTeacher() && !Auth::user()->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }
    }

    protected function getViewData(): array
    {
        $calendarService = app(CalendarService::class);
        $user = Auth::user();
        $date = now();
        
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        
        $events = $calendarService->getUserCalendar($user, $startDate, $endDate);
        $stats = $calendarService->getCalendarStats($user, $date);

        return [
            'events' => $events,
            'stats' => $stats,
            'user' => $user,
            'date' => $date,
            'view' => 'month'
        ];
    }
} 