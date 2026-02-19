<?php

namespace App\Filament\Supervisor\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages\ListMonitoredIndividualCircles;
use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages\CreateMonitoredIndividualCircle;
use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages\ViewMonitoredIndividualCircle;
use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages\EditMonitoredIndividualCircle;
use Illuminate\Database\Eloquent\Model;
use App\Enums\UserType;
use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages;
use App\Models\QuranIndividualCircle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'الحلقات الفردية';

    protected static ?string $modelLabel = 'حلقة فردية';

    protected static ?string $pluralModelLabel = 'الحلقات الفردية';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                                        return QuranTeacherProfile::whereIn('user_id', $teacherIds)
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

                                        return User::where('user_type', UserType::STUDENT->value)
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
                TextColumn::make('circle_code')
                    ->label('رمز الحلقة')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->name),

                TextColumn::make('quranTeacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('specialization')
                    ->badge()
                    ->label('التخصص')
                    ->formatStateUsing(fn (?string $state): string => $state ? (QuranIndividualCircle::SPECIALIZATIONS[$state] ?? $state) : '-')
                    ->colors([
                        'success' => 'memorization',
                        'info' => 'recitation',
                        'warning' => 'interpretation',
                        'danger' => 'tajweed',
                        'primary' => 'complete',
                    ]),

                TextColumn::make('memorization_level')
                    ->badge()
                    ->label('المستوى')
                    ->formatStateUsing(fn (?string $state): string => $state ? (QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state) : '-')
                    ->color('gray'),

                TextColumn::make('sessions_completed')
                    ->label('الجلسات')
                    ->formatStateUsing(fn ($record): string => "{$record->sessions_completed} / {$record->total_sessions}")
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_memorized_pages')
                    ->label('صفحات الحفظ')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_reviewed_pages')
                    ->label('صفحات المراجعة')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d')
                    ->timezone(static::getTimezone())
                    ->placeholder('لم تبدأ')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
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

                SelectFilter::make('specialization')
                    ->label('التخصص')
                    ->options(QuranIndividualCircle::SPECIALIZATIONS)
                    ->placeholder('الكل'),

                SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options(QuranIndividualCircle::MEMORIZATION_LEVELS)
                    ->placeholder('الكل'),

                SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedQuranTeacherIds();

                        return User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->name]);
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('الكل'),

                Filter::make('has_progress')
                    ->label('لها تقدم')
                    ->query(fn (Builder $query): Builder => $query->where('total_memorized_pages', '>', 0)
                        ->orWhere('total_reviewed_pages', '>', 0)),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض'),
                    EditAction::make()
                        ->label('تعديل'),
                    Action::make('toggle_status')
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
                    Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (QuranIndividualCircle $record): string => MonitoredQuranSessionsResource::getUrl('index', [
                            'tableFilters[individual_circle_id][value]' => $record->id,
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
            'index' => ListMonitoredIndividualCircles::route('/'),
            'create' => CreateMonitoredIndividualCircle::route('/create'),
            'view' => ViewMonitoredIndividualCircle::route('/{record}'),
            'edit' => EditMonitoredIndividualCircle::route('/{record}/edit'),
        ];
    }

    /**
     * Supervisors can edit any circle shown in their filtered list.
     * The query already filters to only show circles for assigned teachers.
     */
    public static function canEdit(Model $record): bool
    {
        return true;
    }
}
