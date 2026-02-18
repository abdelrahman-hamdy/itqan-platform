<?php

namespace App\Filament\Supervisor\Resources;

use App\Models\SupervisorProfile;
use App\Models\AcademicTeacherProfile;
use App\Services\AcademyContextService;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages\ListMonitoredAllSessions;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages\CreateMonitoredSession;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages\ViewMonitoredSession;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages\EditMonitoredSession;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;
use App\Models\QuranSession;
use Filament\Forms;
use Filament\Tables\Table;

/**
 * Unified Sessions Resource for Supervisor Panel
 * Displays all session types (Quran, Academic, Interactive Course) with tabs
 * Supervisors have full CRUD access to sessions for their assigned teachers
 */
class MonitoredAllSessionsResource extends BaseSupervisorResource
{
    // This resource uses a virtual model approach - actual queries are done in pages
    protected static ?string $model = QuranSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'جميع الجلسات';

    protected static ?string $modelLabel = 'جلسة';

    protected static ?string $pluralModelLabel = 'جميع الجلسات';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة الجلسات';

    protected static ?int $navigationSort = 10;

    /**
     * Get supervisor profile from session
     */
    protected static function getCurrentSupervisorProfile(): ?SupervisorProfile
    {
        $user = auth()->user();

        return $user?->supervisorProfile;
    }

    /**
     * Get assigned Quran teacher IDs
     */
    public static function getAssignedQuranTeacherIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();

        return $profile?->getAssignedQuranTeacherIds() ?? [];
    }

    /**
     * Get assigned Academic teacher IDs (User IDs)
     */
    public static function getAssignedAcademicTeacherIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();

        return $profile?->getAssignedAcademicTeacherIds() ?? [];
    }

    /**
     * Get assigned Academic teacher profile IDs
     */
    public static function getAssignedAcademicTeacherProfileIds(): array
    {
        $userIds = static::getAssignedAcademicTeacherIds();
        if (empty($userIds)) {
            return [];
        }

        return AcademicTeacherProfile::whereIn('user_id', $userIds)
            ->pluck('id')->toArray();
    }

    /**
     * Get derived interactive course IDs from academic teachers
     */
    public static function getDerivedInteractiveCourseIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();

        return $profile?->getDerivedInteractiveCourseIds() ?? [];
    }

    /**
     * Check if supervisor has any Quran teachers
     */
    public static function hasAssignedQuranTeachers(): bool
    {
        return ! empty(static::getAssignedQuranTeacherIds());
    }

    /**
     * Check if supervisor has any Academic teachers
     */
    public static function hasAssignedAcademicTeachers(): bool
    {
        return ! empty(static::getAssignedAcademicTeacherIds());
    }

    /**
     * Check if supervisor has any interactive courses
     */
    public static function hasDerivedInteractiveCourses(): bool
    {
        return ! empty(static::getDerivedInteractiveCourseIds());
    }

    /**
     * Get timezone for display
     */
    protected static function getTimezone(): string
    {
        return AcademyContextService::getTimezone();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجلسة')
                    ->schema([
                        TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->required()
                            ->maxLength(255),

                        Select::make('status')
                            ->label('الحالة')
                            ->options(SessionStatus::options())
                            ->required(),

                        Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->helperText('أهداف ومحتوى الجلسة')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('التوقيت')
                    ->schema([
                        DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->timezone(static::getTimezone()),

                        Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(60)
                            ->required(),

                        // started_at and ended_at are auto-filled by Start/End Session actions
                        // They are displayed as read-only in the view page
                    ])->columns(2),

                Section::make('الملاحظات')
                    ->schema([
                        Textarea::make('session_notes')
                            ->label('ملاحظات الجلسة')
                            ->rows(3)
                            ->helperText('ملاحظات خاصة بالمعلم عن سير الجلسة'),

                        Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(3)
                            ->helperText('ملاحظات من المشرف بعد المراجعة'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Table configuration is handled by the ListMonitoredAllSessions page per tab
        return $table
            ->columns(SessionTableColumns::getQuranSessionColumns())
            ->deferFilters(false)
            ->defaultSort('scheduled_at', 'desc');
    }

    /**
     * Show navigation for supervisors with assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedQuranTeachers()
            || static::hasAssignedAcademicTeachers()
            || static::hasDerivedInteractiveCourses();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoredAllSessions::route('/'),
            'create' => CreateMonitoredSession::route('/create'),
            'view' => ViewMonitoredSession::route('/{record}'),
            'edit' => EditMonitoredSession::route('/{record}/edit'),
        ];
    }

    // CRUD permissions are inherited from BaseSupervisorResource
    // which enables full CRUD for assigned teachers' sessions
}
