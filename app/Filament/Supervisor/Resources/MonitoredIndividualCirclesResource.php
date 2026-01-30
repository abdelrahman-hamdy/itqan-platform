<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages;
use App\Models\QuranIndividualCircle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Individual Circles Resource for Supervisor Panel
 * Aligned with Admin QuranIndividualCircleResource - allows supervisors to manage
 * individual Quran circles (1-on-1 sessions) for their assigned teachers
 */
class MonitoredIndividualCirclesResource extends BaseSupervisorResource
{
    protected static ?string $model = QuranIndividualCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'الحلقات الفردية';

    protected static ?string $modelLabel = 'حلقة فردية';

    protected static ?string $pluralModelLabel = 'الحلقات الفردية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الحلقة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('circle_code')
                                    ->label('رمز الحلقة')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('يتم إنشاؤه تلقائياً'),

                                TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->maxLength(255)
                                    ->helperText('يتم إنشاؤه تلقائياً إذا تُرك فارغاً'),

                                Select::make('quran_teacher_id')
                                    ->label('معلم القرآن')
                                    ->options(function () {
                                        $teacherIds = static::getAssignedQuranTeacherIds();
                                        if (empty($teacherIds)) {
                                            return ['0' => 'لا توجد معلمين مُسندين'];
                                        }

                                        return \App\Models\QuranTeacherProfile::whereIn('user_id', $teacherIds)
                                            ->active()
                                            ->get()
                                            ->mapWithKeys(function ($teacher) {
                                                $userId = $teacher->user_id;
                                                $fullName = $teacher->display_name ?? $teacher->full_name ?? 'معلم غير محدد';

                                                return [$userId => $fullName];
                                            })->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->options(function () {
                                        $academy = static::getCurrentSupervisorAcademy();

                                        return \App\Models\User::where('user_type', 'student')
                                            ->when($academy, fn ($q) => $q->where('academy_id', $academy->id))
                                            ->with('studentProfile')
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                $displayName = $user->studentProfile?->display_name ?? $user->name;

                                                return [$user->id => $displayName];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('specialization')
                                    ->label('التخصص')
                                    ->options(QuranIndividualCircle::SPECIALIZATIONS)
                                    ->default('memorization')
                                    ->required(),

                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options(QuranIndividualCircle::MEMORIZATION_LEVELS)
                                    ->default('beginner')
                                    ->required(),
                            ]),

                        Textarea::make('description')
                            ->label('وصف الحلقة')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        TagsInput::make('learning_objectives')
                            ->label('أهداف التعلم')
                            ->placeholder('أضف هدفاً تعليمياً')
                            ->helperText('أهداف محددة لهذه الحلقة الفردية')
                            ->reorderable()
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('الحلقة نشطة')
                            ->default(true)
                            ->helperText('يتم تعطيلها تلقائياً عند إيقاف الاشتراك')
                            ->columnSpanFull(),
                    ]),

                Section::make('تتبع التقدم')
                    ->description('يتم حسابها تلقائياً من واجبات الجلسات')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_memorized_pages')
                                    ->label('إجمالي الصفحات المحفوظة')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->helperText('يتم تحديثه من واجبات الحفظ الجديد'),

                                TextInput::make('total_reviewed_pages')
                                    ->label('إجمالي الصفحات المراجعة')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->helperText('يتم تحديثه من واجبات المراجعة'),

                                TextInput::make('total_reviewed_surahs')
                                    ->label('إجمالي السور المراجعة')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->helperText('يتم تحديثه من واجبات المراجعة الشاملة'),
                            ]),
                    ]),

                Section::make('ملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('ملاحظات داخلية للإدارة'),

                                Textarea::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->rows(3)
                                    ->maxLength(2000)
                                    ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('circle_code')
                    ->label('رمز الحلقة')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->name),

                Tables\Columns\TextColumn::make('quranTeacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('specialization')
                    ->label('التخصص')
                    ->formatStateUsing(fn (?string $state): string => $state ? (QuranIndividualCircle::SPECIALIZATIONS[$state] ?? $state) : '-')
                    ->colors([
                        'success' => 'memorization',
                        'info' => 'recitation',
                        'warning' => 'interpretation',
                        'danger' => 'tajweed',
                        'primary' => 'complete',
                    ]),

                Tables\Columns\BadgeColumn::make('memorization_level')
                    ->label('المستوى')
                    ->formatStateUsing(fn (?string $state): string => $state ? (QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state) : '-')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sessions_completed')
                    ->label('الجلسات')
                    ->formatStateUsing(fn ($record): string => "{$record->sessions_completed} / {$record->total_sessions}")
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_memorized_pages')
                    ->label('صفحات الحفظ')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_reviewed_pages')
                    ->label('صفحات المراجعة')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d')
                    ->timezone(static::getTimezone())
                    ->placeholder('لم تبدأ')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشطة')
                    ->falseLabel('غير نشطة')
                    ->placeholder('الكل'),

                Tables\Filters\SelectFilter::make('specialization')
                    ->label('التخصص')
                    ->options(QuranIndividualCircle::SPECIALIZATIONS),

                Tables\Filters\SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options(QuranIndividualCircle::MEMORIZATION_LEVELS),

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedQuranTeacherIds();

                        return \App\Models\User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->name]);
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('has_progress')
                    ->label('لها تقدم')
                    ->query(fn (Builder $query): Builder => $query->where('total_memorized_pages', '>', 0)
                        ->orWhere('total_reviewed_pages', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل')
                        ->visible(fn (QuranIndividualCircle $record): bool => static::canEdit($record)),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn (QuranIndividualCircle $record) => $record->is_active ? 'إيقاف' : 'تفعيل')
                        ->icon(fn (QuranIndividualCircle $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                        ->color(fn (QuranIndividualCircle $record) => $record->is_active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (QuranIndividualCircle $record) => $record->is_active ? 'إيقاف الحلقة' : 'تفعيل الحلقة')
                        ->modalDescription(fn (QuranIndividualCircle $record) => $record->is_active
                            ? 'هل أنت متأكد من إيقاف هذه الحلقة الفردية؟'
                            : 'هل أنت متأكد من تفعيل هذه الحلقة الفردية؟'
                        )
                        ->action(fn (QuranIndividualCircle $record) => $record->update([
                            'is_active' => ! $record->is_active,
                        ])),
                    Tables\Actions\Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (QuranIndividualCircle $record): string => MonitoredAllSessionsResource::getUrl('index', [
                            'activeTab' => 'quran',
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
     * Only show navigation if supervisor has assigned Quran teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedQuranTeachers();
    }

    /**
     * Override query to filter by assigned Quran teacher IDs.
     * Eager load relationships to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['quranTeacher', 'student', 'subscription', 'academy']);

        // Filter by assigned Quran teacher IDs
        $teacherIds = static::getAssignedQuranTeacherIds();

        if (! empty($teacherIds)) {
            $query->whereIn('quran_teacher_id', $teacherIds);
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
            'index' => Pages\ListMonitoredIndividualCircles::route('/'),
            'create' => Pages\CreateMonitoredIndividualCircle::route('/create'),
            'view' => Pages\ViewMonitoredIndividualCircle::route('/{record}'),
            'edit' => Pages\EditMonitoredIndividualCircle::route('/{record}/edit'),
        ];
    }

    // CRUD permissions inherited from BaseSupervisorResource
    // Supervisors can edit/delete individual circles for their assigned teachers
}
