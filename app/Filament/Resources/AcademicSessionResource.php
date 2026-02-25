<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Filament\Resources\AcademicSessionResource\Pages;
use App\Filament\Resources\AcademicSessionResource\Pages\CreateAcademicSession;
use App\Filament\Resources\AcademicSessionResource\Pages\EditAcademicSession;
use App\Filament\Resources\AcademicSessionResource\Pages\ListAcademicSessions;
use App\Filament\Resources\AcademicSessionResource\Pages\ViewAcademicSession;
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
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
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

/**
 * Academic Session Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseAcademicSessionResource for shared form/table definitions.
 */
class AcademicSessionResource extends BaseAcademicSessionResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all sessions, excluding soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Session info section with teacher and student selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                // Hidden academy_id - auto-set from context
                Hidden::make('academy_id')
                    ->default(fn () => auth()->user()->academy_id),

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
                    ->relationship('academicTeacher', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user ? trim(($record->user->first_name ?? '').' '.($record->user->last_name ?? '')) ?: 'معلم #'.$record->id : 'معلم #'.$record->id)
                    ->label('المعلم')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Select::make('student_id')
                    ->relationship('student', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: 'طالب #'.$record->id)
                    ->label('الطالب')
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Hidden::make('academic_subscription_id'),
            ])->columns(2);
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
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

                RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections (SuperAdmin-specific)
    // ========================================

    /**
     * Notes section for SuperAdmin.
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
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with dynamic filter-by, status, and date range.
     * Same layout pattern as QuranSessionResource.
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

            SelectFilter::make('academic_teacher_id')
                ->label('المعلم')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return AcademicTeacherProfile::query()
                        ->with('user')
                        ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($profile) => [
                            $profile->id => $profile->user
                                ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                                : 'معلم #'.$profile->id,
                        ])
                        ->toArray();
                }),

            SelectFilter::make('student_id')
                ->label('الطالب')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return User::query()
                        ->where('user_type', 'student')
                        ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                        ])
                        ->toArray();
                }),

            SelectFilter::make('academic_individual_lesson_id')
                ->label('الدرس الفردي')
                ->options(fn () => AcademicIndividualLesson::query()
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

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Table columns with teacher column.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Essential columns that should always be visible
        $essentialNames = ['session_code', 'status'];

        // Insert teacher column after title (toggleable)
        $teacherColumn = TextColumn::make('academicTeacher.user.id')
            ->label('المعلم')
            ->formatStateUsing(fn ($record) => $record->academicTeacher?->user
                    ? trim(($record->academicTeacher->user->first_name ?? '').' '.($record->academicTeacher->user->last_name ?? '')) ?: 'معلم #'.$record->academicTeacher->id
                    : 'معلم #'.($record->academic_teacher_id ?? '-')
            )
            ->searchable()
            ->toggleable();

        // Build result: make non-essential parent columns toggleable
        $result = [];
        foreach ($columns as $column) {
            if (! in_array($column->getName(), $essentialNames)) {
                $column->toggleable();
            }
            $result[] = $column;
            if ($column->getName() === 'title') {
                $result[] = $teacherColumn;
            }
        }

        return $result;
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
