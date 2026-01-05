<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource\Pages;
use App\Models\TeacherReview;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Managed Teacher Reviews Resource for Supervisor Panel
 * Allows supervisors to view reviews of assigned teachers
 * Only visible when can_manage_teachers = true
 */
class ManagedTeacherReviewsResource extends BaseSupervisorResource
{
    protected static ?string $model = TeacherReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'تقييمات المعلمين';

    protected static ?string $modelLabel = 'تقييم معلم';

    protected static ?string $pluralModelLabel = 'تقييمات المعلمين';

    protected static ?string $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 4;

    /**
     * Only show navigation if supervisor can manage teachers and has assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canManageTeachers() && static::hasAssignedTeachers();
    }

    /**
     * Get assigned teacher profile IDs by type.
     */
    protected static function getAssignedTeacherProfileIds(): array
    {
        $quranTeacherUserIds = static::getAssignedQuranTeacherIds();
        $academicTeacherUserIds = static::getAssignedAcademicTeacherIds();

        $quranProfileIds = [];
        $academicProfileIds = [];

        // Get Quran teacher profile IDs
        if (!empty($quranTeacherUserIds)) {
            $quranProfileIds = QuranTeacherProfile::whereIn('user_id', $quranTeacherUserIds)
                ->pluck('id')
                ->toArray();
        }

        // Get Academic teacher profile IDs
        if (!empty($academicTeacherUserIds)) {
            $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherUserIds)
                ->pluck('id')
                ->toArray();
        }

        return [
            'quran' => $quranProfileIds,
            'academic' => $academicProfileIds,
        ];
    }

    /**
     * Override query to filter by assigned teacher profiles.
     */
    public static function getEloquentQuery(): Builder
    {
        $profileIds = static::getAssignedTeacherProfileIds();

        $query = TeacherReview::query()
            ->with(['reviewable.user', 'student']);

        // Build filter based on assigned teacher profiles
        $hasQuran = !empty($profileIds['quran']);
        $hasAcademic = !empty($profileIds['academic']);

        if ($hasQuran || $hasAcademic) {
            $query->where(function ($q) use ($profileIds, $hasQuran, $hasAcademic) {
                if ($hasQuran) {
                    $q->orWhere(function ($sq) use ($profileIds) {
                        $sq->where('reviewable_type', QuranTeacherProfile::class)
                           ->whereIn('reviewable_id', $profileIds['quran']);
                    });
                }
                if ($hasAcademic) {
                    $q->orWhere(function ($sq) use ($profileIds) {
                        $sq->where('reviewable_type', AcademicTeacherProfile::class)
                           ->whereIn('reviewable_id', $profileIds['academic']);
                    });
                }
            });
        } else {
            // No teachers assigned - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التقييم')
                    ->schema([
                        Forms\Components\TextInput::make('reviewable.user.name')
                            ->label('المعلم')
                            ->disabled(),

                        Forms\Components\TextInput::make('reviewable_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => match($state) {
                                QuranTeacherProfile::class => 'معلم قرآن',
                                AcademicTeacherProfile::class => 'معلم أكاديمي',
                                default => $state,
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('student.full_name')
                            ->label('الطالب')
                            ->disabled(),

                        Forms\Components\TextInput::make('rating')
                            ->label('التقييم')
                            ->suffix('/ 5')
                            ->disabled(),

                        Forms\Components\Toggle::make('is_approved')
                            ->label('تم الموافقة')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('تاريخ الموافقة')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('نص التقييم')
                    ->schema([
                        Forms\Components\Textarea::make('comment')
                            ->label('التعليق')
                            ->rows(4)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reviewable.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reviewable_type')
                    ->label('نوع المعلم')
                    ->formatStateUsing(fn ($state) => match($state) {
                        QuranTeacherProfile::class => 'قرآن',
                        AcademicTeacherProfile::class => 'أكاديمي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        QuranTeacherProfile::class => 'success',
                        AcademicTeacherProfile::class => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('student.full_name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->sortable(),

                TextColumn::make('comment')
                    ->label('التعليق')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('موافق عليه')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->timezone(static::getTimezone())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reviewable_type')
                    ->label('نوع المعلم')
                    ->options([
                        QuranTeacherProfile::class => 'معلم قرآن',
                        AcademicTeacherProfile::class => 'معلم أكاديمي',
                    ]),

                Tables\Filters\SelectFilter::make('rating')
                    ->label('التقييم')
                    ->options([
                        '5' => '5 نجوم',
                        '4' => '4 نجوم',
                        '3' => '3 نجوم',
                        '2' => '2 نجوم',
                        '1' => '1 نجمة',
                    ]),

                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('الموافقة')
                    ->trueLabel('موافق عليه')
                    ->falseLabel('في الانتظار'),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAllAssignedTeacherIds();
                        return User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $userId = $data['value'];
                            $user = User::find($userId);

                            if ($user) {
                                if ($user->user_type === 'quran_teacher') {
                                    $profile = $user->quranTeacherProfile;
                                    if ($profile) {
                                        $query->where('reviewable_type', QuranTeacherProfile::class)
                                              ->where('reviewable_id', $profile->id);
                                    }
                                } elseif ($user->user_type === 'academic_teacher') {
                                    $profile = $user->academicTeacherProfile;
                                    if ($profile) {
                                        $query->where('reviewable_type', AcademicTeacherProfile::class)
                                              ->where('reviewable_id', $profile->id);
                                    }
                                }
                            }
                        }
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
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
            'index' => Pages\ListManagedTeacherReviews::route('/'),
            'view' => Pages\ViewManagedTeacherReview::route('/{record}'),
        ];
    }

    /**
     * Supervisors can view but not edit reviews
     */
    public static function canEdit($record): bool
    {
        return false;
    }
}
