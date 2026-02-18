<?php

namespace App\Filament\Academy\Resources;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\CreateAcademicSession;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\EditAcademicSession;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\ListAcademicSessions;
use App\Filament\Academy\Resources\AcademicSessionResource\Pages\ViewAcademicSession;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Resources\BaseAcademicSessionResource;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Academic Session Resource for Academy Panel
 *
 * Academy admins can manage all academic sessions in their academy.
 * Shows all sessions (not filtered by teacher).
 */
class AcademicSessionResource extends BaseAcademicSessionResource
{
    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 4;

    /**
     * Filter sessions to current academy only, including soft-deleted.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query
            ->where('academy_id', auth()->user()->academy_id)
            ->withoutGlobalScopes([SoftDeletingScope::class]);
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
                                    ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                                    : 'معلم #'.$profile->id,
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
                                $user->id => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'طالب #'.$user->id,
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),

                Hidden::make('academic_subscription_id'),
            ])->columns(2);
    }

    /**
     * Academy admin table actions with session control and soft deletes.
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
                DeleteAction::make()
                    ->label('حذف'),
            ]),
        ];
    }

    /**
     * Bulk actions with soft deletes.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections
    // ========================================

    /**
     * Notes section for academy admins.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            Section::make('ملاحظات')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Textarea::make('session_notes')
                                ->label('ملاحظات الجلسة')
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
        ];
    }

    // ========================================
    // Table Columns Override
    // ========================================

    /**
     * Add teacher column for academy admins.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Insert teacher column after title
        $teacherColumn = TextColumn::make('academicTeacher.user.id')
            ->label('المعلم')
            ->formatStateUsing(fn ($record) => $record->academicTeacher?->user
                    ? trim(($record->academicTeacher->user->first_name ?? '').' '.($record->academicTeacher->user->last_name ?? '')) ?: 'معلم #'.$record->academicTeacher->id
                    : 'معلم #'.($record->academic_teacher_id ?? '-')
            )
            ->searchable();

        // Find position after title column and insert
        $result = [];
        foreach ($columns as $column) {
            $result[] = $column;
            if ($column->getName() === 'title') {
                $result[] = $teacherColumn;
            }
        }

        return $result;
    }

    // ========================================
    // Table Filters Override
    // ========================================

    /**
     * Extended filters with teacher, student, lesson, date range.
     */
    protected static function getTableFilters(): array
    {
        $academyId = auth()->user()->academy_id;

        return [
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(SessionStatus::options()),

            SelectFilter::make('academic_teacher_id')
                ->label('المعلم')
                ->options(fn () => AcademicTeacherProfile::where('academy_id', auth()->user()->academy_id)
                    ->with('user')
                    ->get()
                    ->mapWithKeys(fn ($profile) => [
                        $profile->id => $profile->user
                            ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                            : 'معلم #'.$profile->id,
                    ])
                )
                ->searchable(),

            SelectFilter::make('student_id')
                ->label('الطالب')
                ->options(fn () => User::where('academy_id', auth()->user()->academy_id)
                    ->where('user_type', 'student')
                    ->get()
                    ->mapWithKeys(fn ($u) => [
                        $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                    ])
                )
                ->searchable(),

            SelectFilter::make('academic_individual_lesson_id')
                ->label('الدرس الفردي')
                ->options(fn () => AcademicIndividualLesson::where('academy_id', auth()->user()->academy_id)
                    ->with(['student', 'academicTeacher.user'])
                    ->get()
                    ->mapWithKeys(fn ($lesson) => [
                        $lesson->id => ($lesson->name ?? 'درس #'.$lesson->id)
                            .' - '.trim(($lesson->student?->first_name ?? '').' '.($lesson->student?->last_name ?? '')),
                    ])
                )
                ->searchable(),

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
