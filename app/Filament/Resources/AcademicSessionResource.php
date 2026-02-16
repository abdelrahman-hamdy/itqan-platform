<?php

namespace App\Filament\Resources;

use App\Enums\SessionStatus;
use App\Filament\Resources\AcademicSessionResource\Pages;
use App\Filament\Shared\Resources\BaseAcademicSessionResource;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables;
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

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

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
                Forms\Components\Hidden::make('academy_id')
                    ->default(fn () => auth()->user()->academy_id),

                Forms\Components\TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('status')
                    ->label('حالة الجلسة')
                    ->options(SessionStatus::options())
                    ->default(SessionStatus::SCHEDULED->value)
                    ->required(),

                Forms\Components\Hidden::make('session_type')
                    ->default('individual'),

                Forms\Components\Select::make('academic_teacher_id')
                    ->relationship('academicTeacher', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user ? trim(($record->user->first_name ?? '').' '.($record->user->last_name ?? '')) ?: 'معلم #'.$record->id : 'معلم #'.$record->id)
                    ->label('المعلم')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: 'طالب #'.$record->id)
                    ->label('الطالب')
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Forms\Components\Hidden::make('academic_subscription_id'),
            ])->columns(2);
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('observe_meeting')
                    ->label('مراقبة الجلسة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->meeting_room_name
                        && in_array(
                            $record->status instanceof \App\Enums\SessionStatus ? $record->status : \App\Enums\SessionStatus::tryFrom($record->status),
                            [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]
                        ))
                    ->url(fn ($record): string => \App\Filament\Pages\ObserveSessionPage::getUrl().'?'.http_build_query([
                        'sessionId' => $record->id,
                        'sessionType' => 'academic',
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),

                static::makeStartSessionAction(),
                static::makeCompleteSessionAction(),
                static::makeJoinMeetingAction(),

                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
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
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
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
                    \App\Enums\AttendanceStatus::options()
                )),

            Filter::make('filter_by')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('filter_type')
                                ->label('تصفية حسب')
                                ->options([
                                    'teacher' => 'المعلم',
                                    'student' => 'الطالب',
                                    'individual_lesson' => 'الدرس الفردي',
                                ])
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('filter_value', null)),

                            Forms\Components\Select::make('filter_value')
                                ->label('القيمة')
                                ->options(function (Forms\Get $get) {
                                    return match ($get('filter_type')) {
                                        'teacher' => AcademicTeacherProfile::query()
                                            ->with('user')
                                            ->get()
                                            ->mapWithKeys(fn ($profile) => [
                                                $profile->id => $profile->user
                                                    ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                                                    : 'معلم #'.$profile->id,
                                            ])
                                            ->toArray(),
                                        'student' => User::query()
                                            ->where('user_type', 'student')
                                            ->get()
                                            ->mapWithKeys(fn ($u) => [
                                                $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                                            ])
                                            ->toArray(),
                                        'individual_lesson' => AcademicIndividualLesson::query()
                                            ->with(['student', 'academicTeacher.user'])
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
                                ->visible(fn (Forms\Get $get) => filled($get('filter_type'))),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $type = $data['filter_type'] ?? null;
                    $value = $data['filter_value'] ?? null;

                    if (! $type || ! $value) {
                        return $query;
                    }

                    return match ($type) {
                        'teacher' => $query->where('academic_teacher_id', $value),
                        'student' => $query->where('student_id', $value),
                        'individual_lesson' => $query->where('academic_individual_lesson_id', $value),
                        default => $query,
                    };
                })
                ->columnSpan(2),

            Filter::make('date_range')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('from')
                                ->label('من تاريخ'),
                            Forms\Components\DatePicker::make('until')
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

        // Insert teacher column after title
        $teacherColumn = Tables\Columns\TextColumn::make('academicTeacher.user.id')
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
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicSessions::route('/'),
            'create' => Pages\CreateAcademicSession::route('/create'),
            'view' => Pages\ViewAcademicSession::route('/{record}'),
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
