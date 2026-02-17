<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages\CreateQuranIndividualCircle;
use App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages\EditQuranIndividualCircle;
use App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages\ListQuranIndividualCircles;
use App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages\ViewQuranIndividualCircle;
use App\Filament\Shared\Resources\BaseQuranIndividualCircleResource;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quran Individual Circle Resource for Academy Panel
 *
 * Academy admins can manage all individual circles in their academy.
 * Shows all circles (not filtered by teacher).
 */
class QuranIndividualCircleResource extends BaseQuranIndividualCircleResource
{
    protected static ?string $navigationLabel = 'الحلقات الفردية';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 4;

    /**
     * Filter circles to current academy only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->where('academy_id', auth()->user()->academy_id);
    }

    /**
     * Basic info section with teacher and student selection.
     */
    protected static function getBasicInfoFormSection(): Section
    {
        $academyId = auth()->user()->academy_id;

        return Section::make('معلومات الحلقة الأساسية')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الحلقة')
                            ->maxLength(255),

                        Select::make('quran_teacher_id')
                            ->label('المعلم')
                            ->options(function () use ($academyId) {
                                return User::where('academy_id', $academyId)
                                    ->whereHas('quranTeacherProfile')
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [
                                        $user->id => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'معلم #' . $user->id,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),

                        Select::make('student_id')
                            ->label('الطالب')
                            ->options(function () use ($academyId) {
                                return User::where('academy_id', $academyId)
                                    ->where('user_type', 'student')
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [
                                        $user->id => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'طالب #' . $user->id,
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Table actions for academy admins.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
            ]),
        ];
    }

    /**
     * No bulk actions for academy admins.
     */
    protected static function getTableBulkActions(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuranIndividualCircles::route('/'),
            'create' => CreateQuranIndividualCircle::route('/create'),
            'view' => ViewQuranIndividualCircle::route('/{record}'),
            'edit' => EditQuranIndividualCircle::route('/{record}/edit'),
        ];
    }
}
