<?php

namespace App\Filament\Supervisor\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Models\AcademicTeacherProfile;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages\ListMonitoredAcademicLessons;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages\CreateMonitoredAcademicLesson;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages\ViewMonitoredAcademicLesson;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages\EditMonitoredAcademicLesson;
use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages;
use App\Models\AcademicIndividualLesson;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Academic Lessons Resource for Supervisor Panel
 * Aligned with Admin AcademicIndividualLessonResource - allows supervisors to manage
 * academic lessons for their assigned teachers
 */
class MonitoredAcademicLessonsResource extends BaseSupervisorResource
{
    protected static ?string $model = AcademicIndividualLesson::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الدروس الأكاديمية';

    protected static ?string $modelLabel = 'درس أكاديمي';

    protected static ?string $pluralModelLabel = 'الدروس الأكاديمية';

    protected static string | \UnitEnum | null $navigationGroup = 'الدروس الأكاديمية';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الدرس الأساسية')
                    ->schema([
                        TextInput::make('lesson_code')
                            ->label('رمز الدرس')
                            ->disabled(),

                        TextInput::make('name')
                            ->label('اسم الدرس')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('وصف الدرس')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('academic_teacher_id')
                            ->label('المعلم')
                            ->options(function () {
                                $profileIds = static::getAssignedAcademicTeacherProfileIds();
                                if (empty($profileIds)) {
                                    return ['0' => 'لا توجد معلمين مُسندين'];
                                }

                                return AcademicTeacherProfile::whereIn('id', $profileIds)
                                    ->with('user')
                                    ->get()
                                    ->mapWithKeys(function ($profile) {
                                        return [$profile->id => $profile->user?->name ?? 'غير محدد'];
                                    })->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('academic_subject_id')
                            ->relationship('academicSubject', 'name')
                            ->label('المادة')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('academic_grade_level_id')
                            ->relationship('academicGradeLevel', 'name')
                            ->label('المستوى الدراسي')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('إعدادات الجلسات')
                    ->schema([
                        TextInput::make('total_sessions')
                            ->label('عدد الجلسات الكلي')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        TextInput::make('sessions_scheduled')
                            ->label('الجلسات المجدولة')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('sessions_completed')
                            ->label('الجلسات المكتملة')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('sessions_remaining')
                            ->label('الجلسات المتبقية')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('default_duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->default(60)
                            ->minValue(15)
                            ->maxValue(180)
                            ->required(),
                    ])
                    ->columns(5),

                Section::make('التوقيت')
                    ->schema([
                        DateTimePicker::make('started_at')
                            ->label('تاريخ البدء')
                            ->timezone(AcademyContextService::getTimezone()),

                        DateTimePicker::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->timezone(AcademyContextService::getTimezone()),

                        DateTimePicker::make('last_session_at')
                            ->label('آخر جلسة')
                            ->timezone(AcademyContextService::getTimezone())
                            ->disabled(),
                    ])
                    ->columns(3),

                Section::make('أهداف التعلم والمواد')
                    ->schema([
                        Repeater::make('learning_objectives')
                            ->label('أهداف التعلم')
                            ->schema([
                                TextInput::make('objective')
                                    ->label('الهدف')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة هدف')
                            ->collapsible()
                            ->collapsed(),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),

                        Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->rows(3),
                    ])->columns(2),

                Section::make('ملاحظات المشرف')
                    ->schema([
                        Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->helperText('ملاحظات المشرف الخاصة بهذا الدرس'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lesson_code')
                    ->label('رمز الدرس')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('اسم الدرس')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('academicTeacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('academicSubject.name')
                    ->label('المادة')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('academicGradeLevel.name')
                    ->label('المستوى')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('sessions_completed')
                    ->label('الجلسات')
                    ->suffix(fn (AcademicIndividualLesson $record): string => " / {$record->total_sessions}")
                    ->sortable(),

                TextColumn::make('progress_percentage')
                    ->label('التقدم')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 80 => 'success',
                        (float) $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('academic_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $profileIds = static::getAssignedAcademicTeacherProfileIds();

                        return AcademicTeacherProfile::whereIn('id', $profileIds)
                            ->with('user')
                            ->get()
                            ->mapWithKeys(fn ($profile) => [$profile->id => $profile->user?->name ?? 'غير محدد']);
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('academic_subject_id')
                    ->label('المادة')
                    ->relationship('academicSubject', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض'),
                    EditAction::make()
                        ->label('تعديل'),
                    Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (AcademicIndividualLesson $record): string => MonitoredAllSessionsResource::getUrl('index', [
                            'tableFilters[academic_individual_lesson_id][value]' => $record->id,
                            'activeTab' => 'academic',
                        ])),
                    DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }

    /**
     * Only show navigation if supervisor has assigned academic teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedAcademicTeachers();
    }

    /**
     * Override query to filter by assigned academic teacher profile IDs.
     * Academic lessons use AcademicTeacherProfile IDs, not User IDs.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['academicTeacher.user', 'student', 'academicSubject', 'academicGradeLevel', 'academy']);

        // Filter by assigned academic teacher profile IDs
        $profileIds = static::getAssignedAcademicTeacherProfileIds();

        if (! empty($profileIds)) {
            $query->whereIn('academic_teacher_id', $profileIds);
        } else {
            // No teachers assigned - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoredAcademicLessons::route('/'),
            'create' => CreateMonitoredAcademicLesson::route('/create'),
            'view' => ViewMonitoredAcademicLesson::route('/{record}'),
            'edit' => EditMonitoredAcademicLesson::route('/{record}/edit'),
        ];
    }

    // CRUD permissions inherited from BaseSupervisorResource
    // Supervisors can edit/delete lessons for their assigned teachers
}
