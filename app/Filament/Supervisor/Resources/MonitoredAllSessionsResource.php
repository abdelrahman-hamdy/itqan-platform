<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Enums\AttendanceStatus;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Unified Sessions Resource for Supervisor Panel
 * Displays all session types (Quran, Academic, Interactive Course) with tabs
 * Supervisors have full CRUD access to sessions for their assigned teachers
 */
class MonitoredAllSessionsResource extends BaseSupervisorResource
{
    // This resource uses a virtual model approach - actual queries are done in pages
    protected static ?string $model = QuranSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'جميع الجلسات';

    protected static ?string $modelLabel = 'جلسة';

    protected static ?string $pluralModelLabel = 'جميع الجلسات';

    protected static ?string $navigationGroup = 'إدارة الجلسات';

    protected static ?int $navigationSort = 10;

    /**
     * Get supervisor profile from session
     */
    protected static function getCurrentSupervisorProfile(): ?\App\Models\SupervisorProfile
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
        return \App\Models\AcademicTeacherProfile::whereIn('user_id', $userIds)
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
        return !empty(static::getAssignedQuranTeacherIds());
    }

    /**
     * Check if supervisor has any Academic teachers
     */
    public static function hasAssignedAcademicTeachers(): bool
    {
        return !empty(static::getAssignedAcademicTeacherIds());
    }

    /**
     * Check if supervisor has any interactive courses
     */
    public static function hasDerivedInteractiveCourses(): bool
    {
        return !empty(static::getDerivedInteractiveCourseIds());
    }

    /**
     * Get timezone for display
     */
    protected static function getTimezone(): string
    {
        return \App\Services\AcademyContextService::getTimezone();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(SessionStatus::options())
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->helperText('أهداف ومحتوى الجلسة')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->timezone(static::getTimezone()),

                        Forms\Components\Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(60)
                            ->required(),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('وقت البدء')
                            ->timezone(static::getTimezone())
                            ->helperText('يُملأ تلقائياً عند بدء الجلسة'),

                        Forms\Components\DateTimePicker::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->timezone(static::getTimezone())
                            ->helperText('يُملأ تلقائياً عند انتهاء الجلسة'),
                    ])->columns(2),

                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('session_notes')
                            ->label('ملاحظات الجلسة')
                            ->rows(3)
                            ->helperText('ملاحظات خاصة بالمعلم عن سير الجلسة'),

                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(3)
                            ->helperText('ملاحظات من المشرف بعد المراجعة'),
                    ])->columns(2),
            ]);
    }

    /**
     * Get Quran sessions table configuration
     */
    public static function getQuranSessionsTable(Table $table): Table
    {
        return $table
            ->columns(SessionTableColumns::getQuranSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                    ]),

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedQuranTeacherIds();
                        return \App\Models\User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('circle_id')
                    ->label('الحلقة')
                    ->relationship('circle', 'name_ar')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('add_note')
                    ->label('ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->default(fn (QuranSession $record) => $record->supervisor_notes),
                    ])
                    ->action(function (QuranSession $record, array $data): void {
                        $record->update([
                            'supervisor_notes' => $data['supervisor_notes'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /**
     * Get Academic sessions table configuration
     */
    public static function getAcademicSessionsTable(Table $table): Table
    {
        return $table
            ->columns(SessionTableColumns::getAcademicSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('academic_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $profileIds = static::getAssignedAcademicTeacherProfileIds();
                        return \App\Models\AcademicTeacherProfile::whereIn('id', $profileIds)
                            ->with('user')
                            ->get()
                            ->mapWithKeys(fn ($profile) => [$profile->id => $profile->user?->name ?? 'غير محدد']);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('add_note')
                    ->label('ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->default(fn (AcademicSession $record) => $record->supervisor_notes),
                    ])
                    ->action(function (AcademicSession $record, array $data): void {
                        $record->update([
                            'supervisor_notes' => $data['supervisor_notes'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /**
     * Get Interactive Course sessions table configuration
     */
    public static function getInteractiveCourseSessionsTable(Table $table): Table
    {
        return $table
            ->columns(SessionTableColumns::getInteractiveCourseSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('course_id')
                    ->label('الدورة')
                    ->options(function () {
                        $courseIds = static::getDerivedInteractiveCourseIds();
                        return \App\Models\InteractiveCourse::whereIn('id', $courseIds)
                            ->pluck('title', 'id');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('add_note')
                    ->label('ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->default(fn (InteractiveCourseSession $record) => $record->supervisor_notes),
                    ])
                    ->action(function (InteractiveCourseSession $record, array $data): void {
                        $record->update([
                            'supervisor_notes' => $data['supervisor_notes'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Default table for Quran sessions (used when viewing single record)
        return static::getQuranSessionsTable($table);
    }

    /**
     * Show navigation if supervisor has any assigned teachers
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
            'index' => Pages\ListMonitoredAllSessions::route('/'),
            'create' => Pages\CreateMonitoredSession::route('/create'),
            'view' => Pages\ViewMonitoredSession::route('/{record}'),
            'edit' => Pages\EditMonitoredSession::route('/{record}/edit'),
        ];
    }

    // CRUD permissions are inherited from BaseSupervisorResource
    // which enables full CRUD for assigned teachers' sessions
}
