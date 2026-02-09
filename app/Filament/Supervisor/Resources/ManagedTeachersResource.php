<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\UserType;
use App\Filament\Supervisor\Resources\ManagedTeachersResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Managed Teachers Resource for Supervisor Panel
 * Allows supervisors to view and manage assigned teacher profiles
 * Only visible when can_manage_teachers = true
 */
class ManagedTeachersResource extends BaseSupervisorResource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المعلمون';

    protected static ?string $modelLabel = 'معلم';

    protected static ?string $pluralModelLabel = 'المعلمون';

    protected static ?string $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 1;

    /**
     * Only show navigation if supervisor can manage teachers and has assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canManageTeachers() && static::hasAssignedTeachers();
    }

    /**
     * Override query to filter by assigned teacher IDs.
     */
    public static function getEloquentQuery(): Builder
    {
        $teacherIds = static::getAllAssignedTeacherIds();

        return User::query()
            ->whereIn('id', $teacherIds)
            ->whereIn('user_type', [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المعلم')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('الاسم الأول')
                            ->disabled(),

                        Forms\Components\TextInput::make('last_name')
                            ->label('اسم العائلة')
                            ->disabled(),

                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->disabled(),

                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->disabled(),

                        Forms\Components\Select::make('user_type')
                            ->label('نوع المعلم')
                            ->options([
                                'quran_teacher' => 'معلم قرآن',
                                'academic_teacher' => 'معلم أكاديمي',
                            ])
                            ->disabled(),

                        Forms\Components\Toggle::make('active_status')
                            ->label('نشط')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('ملاحظات المشرف')
                    ->schema([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->helperText('يمكنك إضافة ملاحظاتك حول هذا المعلم'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),

                BadgeColumn::make('user_type')
                    ->label('النوع')
                    ->colors([
                        'success' => 'quran_teacher',
                        'warning' => 'academic_teacher',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'quran_teacher' => 'معلم قرآن',
                        'academic_teacher' => 'معلم أكاديمي',
                        default => $state,
                    }),

                TextColumn::make('resources_count')
                    ->label('الموارد')
                    ->state(function ($record) {
                        if ($record->user_type === UserType::QURAN_TEACHER->value) {
                            $circles = $record->quranCircles()->count();
                            $individual = $record->quranIndividualCircles()->count();

                            return $circles.' حلقة، '.$individual.' فردي';
                        } elseif ($record->user_type === UserType::ACADEMIC_TEACHER->value) {
                            $profile = $record->academicTeacherProfile;
                            $lessons = $profile ? $profile->privateSessions()->count() : 0;

                            return $lessons.' درس';
                        }

                        return '-';
                    }),

                Tables\Columns\IconColumn::make('active_status')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المعلم')
                    ->options([
                        'quran_teacher' => 'معلم قرآن',
                        'academic_teacher' => 'معلم أكاديمي',
                    ]),

                Tables\Filters\TernaryFilter::make('active_status')
                    ->label('الحالة')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\Action::make('view_earnings')
                    ->label('الأرباح')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (User $record): string => ManagedTeacherEarningsResource::getUrl('index', [
                        'tableFilters[teacher_id][value]' => $record->id,
                    ]))
                    ->visible(fn () => static::canManageTeachers()),
            ])
            ->bulkActions([
                // No bulk actions for supervisors
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagedTeachers::route('/'),
            'view' => Pages\ViewManagedTeacher::route('/{record}'),
        ];
    }

    /**
     * Supervisors can view teacher profiles but not edit them
     */
    public static function canEdit($record): bool
    {
        return false;
    }
}
