<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages\CreateAcademicSession;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages\EditAcademicSession;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages\ListAcademicSessions;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages\ViewAcademicSession;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Resources\BaseAcademicSessionResource;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Academic Session Resource for AcademicTeacher Panel
 *
 * Teachers can view and manage their own sessions only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseAcademicSessionResource for shared form/table definitions.
 */
class AcademicSessionResource extends BaseAcademicSessionResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';

    protected static string|\UnitEnum|null $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter sessions to current teacher only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacherProfile = static::getCurrentAcademicTeacherProfile();

        if ($teacherProfile) {
            return $query->where('academic_teacher_id', $teacherProfile->id);
        }

        return $query->whereRaw('1 = 0'); // Return no results
    }

    /**
     * Session info section - teacher is auto-assigned.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة الأساسية')
            ->schema([
                // Hidden fields for auto-assignment
                Hidden::make('academy_id')
                    ->default(fn () => static::getCurrentTeacherAcademy()?->id),

                Hidden::make('academic_teacher_id')
                    ->default(fn () => static::getCurrentAcademicTeacherProfile()?->id),

                Hidden::make('academic_subscription_id'),

                Select::make('student_id')
                    ->label('الطالب')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => static::searchStudentsInAcademy($search))
                    ->getOptionLabelUsing(fn ($value) => static::getStudentLabelById($value))
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                Hidden::make('session_type')
                    ->default('individual'),
            ])->columns(2);
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                MeetingActions::viewMeeting('academic'),
            ]),
        ];
    }

    /**
     * Bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }

    // ========================================
    // Table Filters Override (Teacher-specific)
    // ========================================

    /**
     * Teacher-specific filters (simplified - no teacher filter needed).
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(SessionStatus::options()),

            SelectFilter::make('attendance_status')
                ->label('حالة الحضور')
                ->options(array_merge(
                    [SessionStatus::SCHEDULED->value => SessionStatus::SCHEDULED->label()],
                    AttendanceStatus::options()
                )),

            Filter::make('filter_by')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('filter_type')
                                ->label('تصفية حسب')
                                ->options([
                                    'student' => 'الطالب',
                                    'individual_lesson' => 'الدرس الفردي',
                                ])
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('filter_value', null)),

                            Select::make('filter_value')
                                ->label('القيمة')
                                ->options(function (Get $get) {
                                    $teacherProfile = static::getCurrentAcademicTeacherProfile();

                                    return match ($get('filter_type')) {
                                        'student' => User::query()
                                            ->where('user_type', UserType::STUDENT->value)
                                            ->whereIn('id', function ($query) use ($teacherProfile) {
                                                $query->select('student_id')
                                                    ->from('academic_sessions')
                                                    ->where('academic_teacher_id', $teacherProfile?->id)
                                                    ->whereNotNull('student_id')
                                                    ->distinct();
                                            })
                                            ->get()
                                            ->mapWithKeys(fn ($u) => [
                                                $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                                            ])
                                            ->toArray(),
                                        'individual_lesson' => AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile?->id)
                                            ->with(['student'])
                                            ->get()
                                            ->mapWithKeys(fn ($lesson) => [
                                                $lesson->id => ($lesson->name ?? 'درس #'.$lesson->id)
                                                    .' - '.trim(($lesson->student?->first_name ?? '').' '.($lesson->student?->last_name ?? '')),
                                            ])
                                            ->toArray(),
                                        default => [],
                                    };
                                })
                                ->searchable()
                                ->visible(fn (Get $get) => filled($get('filter_type'))),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $type = $data['filter_type'] ?? null;
                    $value = $data['filter_value'] ?? null;

                    if (! $type || ! $value) {
                        return $query;
                    }

                    return match ($type) {
                        'student' => $query->where('student_id', $value),
                        'individual_lesson' => $query->where('academic_individual_lesson_id', $value),
                        default => $query,
                    };
                })
                ->columnSpan(2),

            Filter::make('date_range')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('from')
                                ->label('من تاريخ'),
                            DatePicker::make('until')
                                ->label('إلى تاريخ'),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '<=', $date));
                })
                ->columnSpan(2),
        ];
    }

    // ========================================
    // Helper Methods for Current Teacher
    // ========================================

    /**
     * Get the current logged-in teacher's profile.
     */
    protected static function getCurrentAcademicTeacherProfile(): ?AcademicTeacherProfile
    {
        return Auth::user()?->academicTeacherProfile;
    }

    /**
     * Get the current teacher's academy.
     */
    protected static function getCurrentTeacherAcademy(): ?Academy
    {
        return Auth::user()?->academy;
    }

    /**
     * Search for students within the current academy.
     * Used by Select component's getSearchResultsUsing.
     */
    protected static function searchStudentsInAcademy(string $search): array
    {
        $academyId = static::getCurrentTeacherAcademy()?->id;

        return User::query()
            ->where('user_type', UserType::STUDENT->value)
            ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(fn ($user) => [
                $user->id => static::formatStudentName($user),
            ])
            ->toArray();
    }

    /**
     * Get student display name by ID (with academy verification).
     * Used by Select component's getOptionLabelUsing.
     */
    protected static function getStudentLabelById($value): ?string
    {
        $academyId = static::getCurrentTeacherAcademy()?->id;
        $user = User::where('id', $value)
            ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
            ->first();

        return $user ? static::formatStudentName($user) : null;
    }

    /**
     * Format student name for display.
     */
    protected static function formatStudentName(User $user): string
    {
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $fullName ?: $user->name ?? 'طالب #'.$user->id;
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers can create sessions.
     */
    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        // Only allow deletion of scheduled sessions
        return $record->academic_teacher_id === $user->academicTeacherProfile->id &&
               $record->status === SessionStatus::SCHEDULED;
    }

    // ========================================
    // Pages
    // ========================================

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
