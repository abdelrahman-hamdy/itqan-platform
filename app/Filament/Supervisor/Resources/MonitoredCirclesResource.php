<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Supervisor\Resources\MonitoredCirclesResource\Pages;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Circles Resource for Supervisor Panel
 * Allows supervisors to view and monitor Quran circles
 */
class MonitoredCirclesResource extends BaseSupervisorResource
{
    protected static ?string $model = QuranCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'الحلقات المراقبة';

    protected static ?string $modelLabel = 'حلقة مراقبة';

    protected static ?string $pluralModelLabel = 'الحلقات المراقبة';

    protected static ?string $navigationGroup = 'الحلقات المراقبة';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الحلقة')
                    ->schema([
                        Forms\Components\TextInput::make('circle_code')
                            ->label('رمز الحلقة')
                            ->disabled(),

                        Forms\Components\TextInput::make('name_ar')
                            ->label('اسم الحلقة')
                            ->disabled(),

                        Forms\Components\Select::make('quran_teacher_id')
                            ->relationship('quranTeacher', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->full_name ?? 'غير محدد')
                            ->label('المعلم')
                            ->disabled(),

                        Forms\Components\Select::make('circle_type')
                            ->label('نوع الحلقة')
                            ->options([
                                'group' => 'جماعية',
                                'trial' => 'تجريبية',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('specialization')
                            ->label('التخصص')
                            ->options([
                                'memorization' => 'الحفظ',
                                'recitation' => 'التلاوة',
                                'interpretation' => 'التفسير',
                                'arabic_language' => 'اللغة العربية',
                                'complete' => 'متكامل',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('memorization_level')
                            ->label('مستوى الحفظ')
                            ->options(\App\Enums\DifficultyLevel::options())
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('إحصائيات الحلقة')
                    ->schema([
                        Forms\Components\TextInput::make('enrolled_students')
                            ->label('الطلاب المسجلين')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('max_students')
                            ->label('الحد الأقصى للطلاب')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('sessions_completed')
                            ->label('الجلسات المكتملة')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('avg_rating')
                            ->label('متوسط التقييم')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('completion_rate')
                            ->label('معدل الإكمال')
                            ->suffix('%')
                            ->numeric()
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('ملاحظات المشرف')
                    ->schema([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->helperText('يمكنك إضافة ملاحظاتك حول هذه الحلقة'),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('teacher_name')
                    ->label('المعلم')
                    ->state(fn ($record) => $record->quranTeacher?->user?->name ?? 'غير محدد'),

                Tables\Columns\BadgeColumn::make('circle_type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'group',
                        'warning' => 'trial',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('specialization')
                    ->label('التخصص')
                    ->colors([
                        'success' => 'memorization',
                        'info' => 'recitation',
                        'warning' => 'interpretation',
                        'danger' => 'arabic_language',
                        'primary' => 'complete',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'memorization' => 'الحفظ',
                        'recitation' => 'التلاوة',
                        'interpretation' => 'التفسير',
                        'arabic_language' => 'اللغة العربية',
                        'complete' => 'متكامل',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('enrolled_students')
                    ->label('الطلاب')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => ' / ' . $record->max_students),

                Tables\Columns\TextColumn::make('sessions_completed')
                    ->label('الجلسات')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_rating')
                    ->label('التقييم')
                    ->numeric(1)
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\IconColumn::make('status')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d H:i')
                    ->timezone(static::getTimezone())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('circle_type')
                    ->label('نوع الحلقة')
                    ->options([
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                    ]),

                Tables\Filters\SelectFilter::make('specialization')
                    ->label('التخصص')
                    ->options([
                        'memorization' => 'الحفظ',
                        'recitation' => 'التلاوة',
                        'interpretation' => 'التفسير',
                        'arabic_language' => 'اللغة العربية',
                        'complete' => 'متكامل',
                    ]),

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->relationship('quranTeacher', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->full_name ?? 'غير محدد')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('الحالة')
                    ->trueLabel('نشطة')
                    ->falseLabel('غير نشطة'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\Action::make('view_sessions')
                    ->label('الجلسات')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (QuranCircle $record): string => MonitoredSessionsResource::getUrl('index', [
                        'tableFilters[circle_id][value]' => $record->id,
                    ])),
            ])
            ->bulkActions([
                // Supervisors typically don't need bulk actions
            ]);
    }

    /**
     * Override query to filter by assigned teachers or department
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['quranTeacher.user', 'academy']);

        $profile = static::getCurrentSupervisorProfile();

        if ($profile) {
            // If supervisor has assigned teachers, filter by them
            $assignedTeachers = $profile->assigned_teachers ?? [];
            if (!empty($assignedTeachers)) {
                $query->whereIn('quran_teacher_id', $assignedTeachers);
            }

            // If supervisor is not for quran department, return empty
            if (!$profile->canAccessDepartment('quran') && $profile->department !== 'general') {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            // Could add sessions relation manager here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoredCircles::route('/'),
            'view' => Pages\ViewMonitoredCircle::route('/{record}'),
        ];
    }

    /**
     * Supervisors can view but typically not edit circles
     */
    public static function canEdit($record): bool
    {
        return false;
    }
}
