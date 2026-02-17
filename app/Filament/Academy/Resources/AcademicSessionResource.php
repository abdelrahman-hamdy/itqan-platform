<?php

namespace App\Filament\Academy\Resources;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\CreateAcademicSession;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\EditAcademicSession;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\ListAcademicSessions;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\ViewAcademicSession;
use App\Filament\Shared\Actions\SessionStatusActions;
use App\Filament\Shared\Resources\BaseAcademicSessionResource;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

/**
 * Academic Session Resource for Academy Panel
 *
 * Academy admins can manage all academic sessions in their academy.
 * Shows all sessions (not filtered by teacher).
 */
class AcademicSessionResource extends BaseAcademicSessionResource
{
    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 4;

    /**
     * Filter sessions to current academy only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->where('academy_id', auth()->user()->academy_id);
    }

    /**
     * Session info section with teacher and student selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        $academyId = auth()->user()->academy_id;

        return Section::make('معلومات الجلسة')
            ->schema([
                Hidden::make('academy_id')
                    ->default(fn () => $academyId),

                TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('status')
                    ->label('حالة الجلسة')
                    ->options(SessionStatus::options())
                    ->default(SessionStatus::SCHEDULED->value)
                    ->required(),

                Hidden::make('session_type')
                    ->default('individual'),

                Select::make('academic_teacher_id')
                    ->label('المعلم')
                    ->options(function () use ($academyId) {
                        return AcademicTeacherProfile::where('academy_id', $academyId)
                            ->with('user')
                            ->get()
                            ->mapWithKeys(fn ($profile) => [
                                $profile->id => $profile->user
                                    ? trim(($profile->user->first_name ?? '') . ' ' . ($profile->user->last_name ?? '')) ?: 'معلم #' . $profile->id
                                    : 'معلم #' . $profile->id,
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),

                Select::make('student_id')
                    ->label('الطالب')
                    ->options(function () use ($academyId) {
                        return User::where('academy_id', $academyId)
                            ->where('user_type', UserType::STUDENT->value)
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'طالب #' . $user->id,
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),

                Hidden::make('academic_subscription_id'),
            ])->columns(2);
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

                static::makeStartSessionAction(),
                static::makeCompleteSessionAction(),
                static::makeJoinMeetingAction(),
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
            'index' => ListAcademicSessions::route('/'),
            'create' => CreateAcademicSession::route('/create'),
            'view' => ViewAcademicSession::route('/{record}'),
            'edit' => EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
