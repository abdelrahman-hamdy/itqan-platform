<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\QuranSessionResource\Pages\CreateQuranSession;
use App\Filament\Academy\Resources\QuranSessionResource\Pages\EditQuranSession;
use App\Filament\Academy\Resources\QuranSessionResource\Pages\ListQuranSessions;
use App\Filament\Academy\Resources\QuranSessionResource\Pages\ViewQuranSession;
use App\Filament\Shared\Actions\SessionStatusActions;
use App\Filament\Shared\Resources\BaseQuranSessionResource;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quran Session Resource for Academy Panel
 *
 * Academy admins can manage all Quran sessions in their academy.
 * Shows all sessions (not filtered by teacher).
 */
class QuranSessionResource extends BaseQuranSessionResource
{
    protected static ?string $navigationLabel = 'جلسات القرآن';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 3;

    /**
     * Filter sessions to current academy only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->where('academy_id', auth()->user()->academy_id);
    }

    /**
     * Teacher/circle selection section for academy admins.
     */
    protected static function getTeacherCircleFormSection(): ?Section
    {
        $academyId = auth()->user()->academy_id;

        return Section::make('المعلم والحلقة')
            ->schema([
                Grid::make(2)
                    ->schema([
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

                        Select::make('circle_id')
                            ->label('الحلقة الجماعية')
                            ->options(function () use ($academyId) {
                                return QuranCircle::where('academy_id', $academyId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->visible(fn (Get $get) => $get('session_type') === 'group'),

                        Select::make('individual_circle_id')
                            ->label('الحلقة الفردية')
                            ->options(function () use ($academyId) {
                                return QuranIndividualCircle::where('academy_id', $academyId)
                                    ->with(['student', 'quranTeacher'])
                                    ->get()
                                    ->mapWithKeys(function ($record) {
                                        $studentName = $record->student
                                            ? trim(($record->student->first_name ?? '') . ' ' . ($record->student->last_name ?? ''))
                                            : 'طالب غير محدد';
                                        $teacherName = $record->quranTeacher
                                            ? trim(($record->quranTeacher->first_name ?? '') . ' ' . ($record->quranTeacher->last_name ?? ''))
                                            : 'معلم غير محدد';

                                        return [$record->id => $studentName . ' - ' . $teacherName];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->visible(fn (Get $get) => $get('session_type') === 'individual'),
                    ]),
            ]);
    }

    /**
     * Academy admin table actions with session control.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),

                SessionStatusActions::startSession(),
                SessionStatusActions::completeSession(),
                SessionStatusActions::joinMeeting(),
                SessionStatusActions::cancelSession(role: 'admin'),
            ]),
        ];
    }

    /**
     * Bulk actions for academy admins.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuranSessions::route('/'),
            'create' => CreateQuranSession::route('/create'),
            'view' => ViewQuranSession::route('/{record}'),
            'edit' => EditQuranSession::route('/{record}/edit'),
        ];
    }
}
