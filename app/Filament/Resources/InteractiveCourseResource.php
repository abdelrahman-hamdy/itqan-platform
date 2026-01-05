<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseResource\Pages;
use App\Filament\Resources\InteractiveCourseResource\RelationManagers;
use App\Models\InteractiveCourse;
use App\Models\AcademicTeacherProfile;
use App\Models\AcademicSubject;
use App\Models\AcademicGradeLevel;
use App\Enums\SessionDuration;
use App\Enums\DifficultyLevel;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\AcademyContextService;

class InteractiveCourseResource extends BaseResource
{
    protected static ?string $model = InteractiveCourse::class;

    /**
     * Tenant ownership relationship for Filament multi-tenancy.
     */
    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'الدورات التفاعلية';
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';
    protected static ?string $modelLabel = 'دورة تفاعلية';
    protected static ?string $pluralModelLabel = 'الدورات التفاعلية';

    // Note: getEloquentQuery() is now handled by ScopedToAcademy trait

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدورة الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الدورة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثل: رياضيات متقدمة - الفصل الأول')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدورة')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('التخصص والمستوى')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('subject_id')
                                    ->label('المادة الدراسية')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return $academyId ? AcademicSubject::where('academy_id', $academyId)->where('is_active', true)->pluck('name', 'id') : [];
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('grade_level_id')
                                    ->label('المرحلة الدراسية')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return $academyId ? AcademicGradeLevel::where('academy_id', $academyId)
                                            ->whereNotNull('name')
                                            ->where('name', '!=', '')
                                            ->pluck('name', 'id') : [];
                                    })
                                    ->required()
                                    ->searchable(),

                                Forms\Components\Select::make('assigned_teacher_id')
                                    ->label('المعلم المعين')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return $academyId ? AcademicTeacherProfile::forAcademy($academyId)
                                            ->approved()
                                            ->active()
                                            ->with('user')
                                            ->get()
                                            ->mapWithKeys(function($teacher) {
                                                // Use linked user name if available, otherwise use profile's full name
                                                $displayName = $teacher->user ? $teacher->user->name : $teacher->full_name;
                                                // Use enum's label() method directly (education_level is cast to EducationalQualification enum)
                                                $qualification = $teacher->education_level?->label() ?? 'غير محدد';
                                                return [$teacher->id => $displayName . ' (' . $qualification . ')'];
                                            }) : [];
                                    })
                                    ->required()
                                    ->searchable(),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الدورة')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('total_sessions')
                                    ->label('إجمالي الجلسات')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(200)
                                    ->default(16)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Trigger recalculation of duration_weeks and end_date
                                        $set('duration_weeks', null);
                                    })
                                    ->suffix('جلسة'),

                                Forms\Components\TextInput::make('sessions_per_week')
                                    ->label('الجلسات أسبوعياً')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(7)
                                    ->default(2)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Trigger recalculation of duration_weeks and end_date
                                        $set('duration_weeks', null);
                                    })
                                    ->suffix('جلسة'),

                                Forms\Components\TextInput::make('duration_weeks')
                                    ->label('مدة الدورة (أسابيع)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->live()
                                    ->placeholder(function (Forms\Get $get) {
                                        $totalSessions = $get('total_sessions') ?? 0;
                                        $sessionsPerWeek = $get('sessions_per_week') ?? 0;
                                        
                                        if ($totalSessions > 0 && $sessionsPerWeek > 0) {
                                            $durationWeeks = ceil($totalSessions / $sessionsPerWeek);
                                            return (string) $durationWeeks;
                                        }
                                        
                                        return '0';
                                    }),

                                Forms\Components\Select::make('session_duration_minutes')
                                    ->label('مدة الجلسة (دقيقة)')
                                    ->options(SessionDuration::options())
                                    ->default(SessionDuration::SIXTY_MINUTES->value)
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_students')
                                    ->label('أقصى عدد طلاب')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(20)
                                    ->required()
                                    ->suffix('طالب'),

                                Forms\Components\Select::make('difficulty_level')
                                    ->label('مستوى الصعوبة')
                                    ->options(DifficultyLevel::options())
                                    ->default(DifficultyLevel::BEGINNER->value)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('الإعدادات المالية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('student_price')
                                    ->label('سعر الدورة للطالب')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(500)
                                    ->required()
                                    ->prefix('SAR'),

                                Forms\Components\TextInput::make('teacher_payment')
                                    ->label('دفع المعلم')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(2000)
                                    ->required()
                                    ->prefix('SAR'),

                                Forms\Components\Select::make('payment_type')
                                    ->label('نوع دفع المعلم')
                                    ->options([
                                        'fixed_amount' => 'مبلغ ثابت',
                                        'per_student' => 'لكل طالب',
                                        'per_session' => 'لكل جلسة',
                                    ])
                                    ->default('fixed_amount')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('التواريخ والجدولة')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->required()
                                    ->minDate(now()->addDays(7))
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Trigger recalculation of end_date
                                        $set('end_date', null);
                                    }),

                                Forms\Components\TextInput::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->live()
                                    ->formatStateUsing(function ($state, $record) {
                                        // If there's existing data, format it
                                        if ($state && $record && $record->end_date) {
                                            return $record->end_date->format('d/m/Y');
                                        }
                                        
                                        // If state exists but no record (form state), try to parse and format
                                        if ($state && is_string($state)) {
                                            try {
                                                return \Carbon\Carbon::parse($state)->format('d/m/Y');
                                            } catch (\Exception $e) {
                                                return $state;
                                            }
                                        }
                                        
                                        return null;
                                    })
                                    ->placeholder(function (Forms\Get $get) {
                                        $startDate = $get('start_date');
                                        $totalSessions = $get('total_sessions') ?? 0;
                                        $sessionsPerWeek = $get('sessions_per_week') ?? 0;
                                        
                                        if (!$startDate || $sessionsPerWeek === 0) {
                                            return 'تاريخ غير محدد';
                                        }
                                        
                                        $durationWeeks = ceil($totalSessions / $sessionsPerWeek);
                                        $endDate = date('d/m/Y', strtotime($startDate . ' +' . ($durationWeeks * 7) . ' days'));
                                        
                                        return $endDate;
                                    }),

                                Forms\Components\DatePicker::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->helperText('اتركه فارغاً للسماح بالتسجيل طوال فترة الدورة')
                                    ->before('start_date')
                                    ->maxDate(function (Forms\Get $get) {
                                        $startDate = $get('start_date');
                                        return $startDate ? date('Y-m-d', strtotime($startDate . ' -3 days')) : null;
                                    }),
                            ]),

                        Forms\Components\KeyValue::make('schedule')
                            ->label('الجدول الأسبوعي')
                            ->keyLabel('اليوم')
                            ->valueLabel('التوقيت')
                            ->default([
                                'الأحد' => '16:00 - 17:00',
                                'الثلاثاء' => '16:00 - 17:00',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('محتوى الدورة والأهداف')
                    ->schema([
                        Forms\Components\Repeater::make('learning_outcomes')
                            ->label('مخرجات التعلم')
                            ->schema([
                                Forms\Components\TextInput::make('outcome')
                                    ->label('المخرج')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة مخرج')
                            ->collapsible()
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->schema([
                                Forms\Components\TextInput::make('prerequisite')
                                    ->label('المتطلب')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة متطلب')
                            ->collapsible()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('course_outline')
                            ->label('مخطط الدورة')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('حالة الدورة والإعدادات')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('مفعل للنشر')
                            ->default(false)
                            ->helperText('هل يمكن للطلاب رؤية هذه الدورة والتسجيل فيها؟'),

                        Forms\Components\Select::make('status')
                            ->label('حالة الدورة')
                            ->options(\App\Enums\InteractiveCourseStatus::options())
                            ->default('published')
                            ->required()
                            ->helperText('حالة الدورة الحالية'),

                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('تسجيل جلسات الدورة')
                            ->default(true)
                            ->helperText('تفعيل تسجيل جميع جلسات هذه الدورة'),
                    ])->columns(3),
            ]);
    }

    /**
     * Eager load relationships to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'academy',
                'subject',
                'gradeLevel',
                'assignedTeacher.user',
            ])
            ->withCount('enrollments');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\TextColumn::make('course_code')
                    ->label('رمز الدورة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المرحلة')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),

                Tables\Columns\TextColumn::make('assignedTeacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if (!$record->assignedTeacher) {
                            return 'غير معين';
                        }
                        return $record->assignedTeacher->user 
                            ? $record->assignedTeacher->user->name 
                            : $record->assignedTeacher->full_name;
                    }),

                Tables\Columns\TextColumn::make('course_type_in_arabic')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'مكثف' => 'warning',
                        'تحضير للامتحانات' => 'danger',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('المسجلين')
                    ->counts('enrollments')
                    ->suffix(function ($record) {
                        return ' / ' . $record->max_students;
                    })
                    ->color(function ($record) {
                        $percentage = ($record->enrollments_count / $record->max_students) * 100;
                        return $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
                    }),

                Tables\Columns\TextColumn::make('student_price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(\App\Enums\InteractiveCourseStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof \App\Enums\InteractiveCourseStatus) {
                            return $state->label();
                        }
                        $statusEnum = \App\Enums\InteractiveCourseStatus::tryFrom($state);
                        return $statusEnum?->label() ?? $state;
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('المادة')
                    ->relationship('subject', 'name'),

                Tables\Filters\SelectFilter::make('grade_level_id')
                    ->label('المرحلة')
                    ->relationship('gradeLevel', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\InteractiveCourseStatus::options()),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options(DifficultyLevel::options()),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                Tables\Filters\Filter::make('upcoming')
                    ->label(__('filament.filters.upcoming'))
                    ->query(fn (Builder $query) => $query->where('start_date', '>', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('ongoing')
                    ->label(__('filament.filters.ongoing'))
                    ->query(fn (Builder $query) => $query
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filament.filters.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filament.filters.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('filament.filters.from_date') . ': ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('filament.filters.to_date') . ': ' . $data['until'];
                        }
                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveCourses::route('/'),
            'create' => Pages\CreateInteractiveCourse::route('/create'),
            'edit' => Pages\EditInteractiveCourse::route('/{record}/edit'),
        ];
    }
}
