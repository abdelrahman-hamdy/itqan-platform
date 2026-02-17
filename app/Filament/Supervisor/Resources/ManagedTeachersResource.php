<?php

namespace App\Filament\Supervisor\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use App\Filament\Supervisor\Resources\ManagedTeachersResource\Pages\ListManagedTeachers;
use App\Filament\Supervisor\Resources\ManagedTeachersResource\Pages\ViewManagedTeacher;
use App\Enums\UserType;
use App\Filament\Supervisor\Resources\ManagedTeachersResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Tables;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المعلمون';

    protected static ?string $modelLabel = 'معلم';

    protected static ?string $pluralModelLabel = 'المعلمون';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المعلمين';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المعلم')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('الاسم الأول')
                            ->disabled(),

                        TextInput::make('last_name')
                            ->label('اسم العائلة')
                            ->disabled(),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->disabled(),

                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->disabled(),

                        Select::make('user_type')
                            ->label('نوع المعلم')
                            ->options([
                                'quran_teacher' => 'معلم قرآن',
                                'academic_teacher' => 'معلم أكاديمي',
                            ])
                            ->disabled(),

                        Toggle::make('active_status')
                            ->label('نشط')
                            ->disabled(),
                    ])->columns(2),

                Section::make('ملاحظات المشرف')
                    ->schema([
                        Textarea::make('supervisor_notes')
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

                TextColumn::make('user_type')
                    ->badge()
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

                IconColumn::make('active_status')
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
                SelectFilter::make('user_type')
                    ->label('نوع المعلم')
                    ->options([
                        'quran_teacher' => 'معلم قرآن',
                        'academic_teacher' => 'معلم أكاديمي',
                    ]),

                TernaryFilter::make('active_status')
                    ->label('الحالة')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض'),
                Action::make('view_earnings')
                    ->label('الأرباح')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (User $record): string => ManagedTeacherEarningsResource::getUrl('index', [
                        'tableFilters[teacher_id][value]' => $record->id,
                    ]))
                    ->visible(fn () => static::canManageTeachers()),
            ])
            ->toolbarActions([
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
            'index' => ListManagedTeachers::route('/'),
            'view' => ViewManagedTeacher::route('/{record}'),
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
