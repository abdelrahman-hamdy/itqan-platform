<?php

namespace App\Filament\Academy\Resources;

use App\Enums\SessionStatus;
use App\Filament\Academy\Resources\QuranSessionResource\Pages\CreateQuranSession;
use App\Filament\Academy\Resources\QuranSessionResource\Pages\EditQuranSession;
use App\Filament\Academy\Resources\QuranSessionResource\Pages\ListQuranSessions;
use App\Filament\Academy\Resources\QuranSessionResource\Pages\ViewQuranSession;
use App\Filament\Pages\ObserveSessionPage;
use App\Filament\Shared\Resources\BaseQuranSessionResource;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Actions\Action;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Quran Session Resource for Academy Panel
 *
 * Academy admins can manage all Quran sessions in their academy.
 * Shows all sessions (not filtered by teacher).
 */
class QuranSessionResource extends BaseQuranSessionResource
{
    protected static ?string $navigationLabel = 'جلسات القرآن';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 3;

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
                                        $user->id => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'معلم #'.$user->id,
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
                                            ? trim(($record->student->first_name ?? '').' '.($record->student->last_name ?? ''))
                                            : 'طالب غير محدد';
                                        $teacherName = $record->quranTeacher
                                            ? trim(($record->quranTeacher->first_name ?? '').' '.($record->quranTeacher->last_name ?? ''))
                                            : 'معلم غير محدد';

                                        return [$record->id => $studentName.' - '.$teacherName];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->visible(fn (Get $get) => $get('session_type') === 'individual'),
                    ]),
            ]);
    }

    /**
     * Academy admin table actions with session control and soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            Action::make('observe_meeting')
                ->label('مراقبة الجلسة')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn ($record): bool => $record->meeting_room_name
                    && in_array(
                        $record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status),
                        [SessionStatus::READY, SessionStatus::ONGOING]
                    ))
                ->url(fn ($record): string => ObserveSessionPage::getUrl().'?'.http_build_query([
                    'sessionId' => $record->id,
                    'sessionType' => 'quran',
                ]))
                ->openUrlInNewTab(),
            ViewAction::make()
                ->label('عرض'),
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
            RestoreAction::make()
                ->label(__('filament.actions.restore')),
            ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

    /**
     * Bulk actions for academy admins with soft deletes.
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
     * Table columns with teacher, circle, and student columns.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('session_code')
                ->label('رمز الجلسة')
                ->searchable()
                ->sortable(),

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->limit(30),

            TextColumn::make('quranTeacher.id')
                ->label('المعلم')
                ->formatStateUsing(fn ($record) => trim(($record->quranTeacher?->first_name ?? '').' '.($record->quranTeacher?->last_name ?? '')) ?: 'معلم #'.($record->quranTeacher?->id ?? '-')
                )
                ->searchable()
                ->sortable(),

            TextColumn::make('circle_display')
                ->label('الحلقة')
                ->getStateUsing(function ($record) {
                    if ($record->session_type === 'individual' || $record->session_type === 'trial') {
                        return $record->individualCircle?->name;
                    }

                    return $record->circle?->name;
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function ($q) use ($search) {
                        $q->whereHas('circle', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('individualCircle', fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
                    });
                })
                ->placeholder('-')
                ->toggleable(),

            TextColumn::make('student.id')
                ->label('الطالب')
                ->formatStateUsing(fn ($record) => trim(($record->student?->first_name ?? '').' '.($record->student?->last_name ?? '')) ?: null
                )
                ->searchable()
                ->placeholder('جماعية')
                ->toggleable(),

            TextColumn::make('session_type')
                ->badge()
                ->label('النوع')
                ->formatStateUsing(fn (string $state): string => static::formatSessionType($state))
                ->colors([
                    'primary' => 'individual',
                    'success' => 'group',
                    'warning' => 'trial',
                ])
                ->toggleable(),

            TextColumn::make('scheduled_at')
                ->label('الموعد')
                ->dateTime('Y-m-d H:i')
                ->timezone(AcademyContextService::getTimezone())
                ->sortable()
                ->toggleable(),

            TextColumn::make('duration_minutes')
                ->label('المدة')
                ->suffix(' د')
                ->sortable()
                ->toggleable(),

            TextColumn::make('status')
                ->badge()
                ->label('الحالة')
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof SessionStatus) {
                        return $state->label();
                    }
                    $status = SessionStatus::tryFrom($state);

                    return $status?->label() ?? $state;
                })
                ->colors(SessionStatus::colorOptions())
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override
    // ========================================

    /**
     * Extended filters with teacher, date range, and trashed.
     */
    protected static function getTableFilters(): array
    {
        $academyId = auth()->user()->academy_id;

        return [
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(SessionStatus::options()),

            SelectFilter::make('session_type')
                ->label('نوع الجلسة')
                ->options(static::getSessionTypeOptions()),

            Filter::make('filter_by')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('filter_type')
                                ->label('تصفية حسب')
                                ->options([
                                    'group_circle' => 'الحلقة الجماعية',
                                    'individual_circle' => 'الحلقة الفردية',
                                    'teacher' => 'المعلم',
                                    'student' => 'الطالب',
                                ])
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('filter_value', null)),

                            Select::make('filter_value')
                                ->label('القيمة')
                                ->options(function (Get $get) use ($academyId) {
                                    return match ($get('filter_type')) {
                                        'group_circle' => QuranCircle::where('academy_id', $academyId)
                                            ->pluck('name', 'id')
                                            ->toArray(),
                                        'individual_circle' => QuranIndividualCircle::where('academy_id', $academyId)
                                            ->with(['student', 'quranTeacher'])
                                            ->get()
                                            ->mapWithKeys(fn ($ic) => [
                                                $ic->id => trim(($ic->student?->first_name ?? '').' '.($ic->student?->last_name ?? ''))
                                                    .' - '.trim(($ic->quranTeacher?->first_name ?? '').' '.($ic->quranTeacher?->last_name ?? '')),
                                            ])
                                            ->toArray(),
                                        'teacher' => User::where('academy_id', $academyId)
                                            ->whereHas('quranTeacherProfile')
                                            ->get()
                                            ->mapWithKeys(fn ($u) => [
                                                $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'معلم #'.$u->id,
                                            ])
                                            ->toArray(),
                                        'student' => User::where('academy_id', $academyId)
                                            ->where('user_type', 'student')
                                            ->get()
                                            ->mapWithKeys(fn ($u) => [
                                                $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
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
                        'group_circle' => $query->where('circle_id', $value),
                        'individual_circle' => $query->where('individual_circle_id', $value),
                        'teacher' => $query->where('quran_teacher_id', $value),
                        'student' => $query->where('student_id', $value),
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
    // Authorization Overrides
    // ========================================

    public static function canDelete(Model $record): bool
    {
        return true;
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
