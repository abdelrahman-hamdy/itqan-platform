<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages;
use App\Models\AcademicIndividualLesson;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الدروس الأكاديمية';

    protected static ?string $modelLabel = 'درس أكاديمي';

    protected static ?string $pluralModelLabel = 'الدروس الأكاديمية';

    protected static ?string $navigationGroup = 'الدروس الأكاديمية';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدرس الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('lesson_code')
                            ->label('رمز الدرس')
                            ->disabled(),

                        Forms\Components\TextInput::make('name')
                            ->label('اسم الدرس')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدرس')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('academic_teacher_id')
                            ->label('المعلم')
                            ->options(function () {
                                $profileIds = static::getAssignedAcademicTeacherProfileIds();
                                if (empty($profileIds)) {
                                    return ['0' => 'لا توجد معلمين مُسندين'];
                                }

                                return \App\Models\AcademicTeacherProfile::whereIn('id', $profileIds)
                                    ->with('user')
                                    ->get()
                                    ->mapWithKeys(function ($profile) {
                                        return [$profile->id => $profile->user?->name ?? 'غير محدد'];
                                    })->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('academic_subject_id')
                            ->relationship('academicSubject', 'name')
                            ->label('المادة')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('academic_grade_level_id')
                            ->relationship('academicGradeLevel', 'name')
                            ->label('المستوى الدراسي')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('إعدادات الجلسات')
                    ->schema([
                        Forms\Components\TextInput::make('total_sessions')
                            ->label('عدد الجلسات الكلي')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('sessions_scheduled')
                            ->label('الجلسات المجدولة')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('sessions_completed')
                            ->label('الجلسات المكتملة')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('sessions_remaining')
                            ->label('الجلسات المتبقية')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('default_duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->default(60)
                            ->minValue(15)
                            ->maxValue(180)
                            ->required(),
                    ])
                    ->columns(5),

                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('تاريخ البدء')
                            ->timezone(AcademyContextService::getTimezone()),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->timezone(AcademyContextService::getTimezone()),

                        Forms\Components\DateTimePicker::make('last_session_at')
                            ->label('آخر جلسة')
                            ->timezone(AcademyContextService::getTimezone())
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('أهداف التعلم والمواد')
                    ->schema([
                        Forms\Components\Repeater::make('learning_objectives')
                            ->label('أهداف التعلم')
                            ->schema([
                                Forms\Components\TextInput::make('objective')
                                    ->label('الهدف')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة هدف')
                            ->collapsible()
                            ->collapsed(),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),

                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('ملاحظات المشرف')
                    ->schema([
                        Forms\Components\Textarea::make('supervisor_notes')
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

                Tables\Filters\SelectFilter::make('academic_subject_id')
                    ->label('المادة')
                    ->relationship('academicSubject', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (AcademicIndividualLesson $record): string => MonitoredAllSessionsResource::getUrl('index', [
                            'activeTab' => 'academic',
                        ])),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
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

        if (!empty($profileIds)) {
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
            'index' => Pages\ListMonitoredAcademicLessons::route('/'),
            'create' => Pages\CreateMonitoredAcademicLesson::route('/create'),
            'view' => Pages\ViewMonitoredAcademicLesson::route('/{record}'),
            'edit' => Pages\EditMonitoredAcademicLesson::route('/{record}/edit'),
        ];
    }

    // CRUD permissions inherited from BaseSupervisorResource
    // Supervisors can edit/delete lessons for their assigned teachers
}
