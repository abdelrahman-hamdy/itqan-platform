<?php

namespace App\Filament\Resources;

use App\Enums\SessionStatus;
use App\Filament\Resources\QuranSessionResource\Pages;
use App\Filament\Shared\Resources\BaseQuranSessionResource;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Quran Session Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseQuranSessionResource for shared form/table definitions.
 */
class QuranSessionResource extends BaseQuranSessionResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'جلسات القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 7;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all sessions, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Get teacher/circle selection section for SuperAdmin.
     */
    protected static function getTeacherCircleFormSection(): ?Section
    {
        return Section::make('المعلم والحلقة')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('quran_teacher_id')
                            ->relationship('quranTeacher', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: 'معلم #'.$record->id
                            )
                            ->label('المعلم')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('circle_id')
                            ->relationship('circle', 'name')
                            ->label('الحلقة الجماعية')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('session_type') === 'group'),

                        Forms\Components\Select::make('individual_circle_id')
                            ->relationship('individualCircle', 'id', fn ($query) => $query->with(['student', 'quranTeacher']))
                            ->label('الحلقة الفردية')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $studentName = $record->student
                                    ? trim(($record->student->first_name ?? '').' '.($record->student->last_name ?? ''))
                                    : 'طالب غير محدد';
                                $teacherName = $record->quranTeacher
                                    ? trim(($record->quranTeacher->first_name ?? '').' '.($record->quranTeacher->last_name ?? ''))
                                    : 'معلم غير محدد';

                                return $studentName.' - '.$teacherName;
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('session_type') === 'individual'),
                    ]),
            ]);
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
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
                    'sessionType' => 'quran',
                ]))
                ->openUrlInNewTab(),
            Tables\Actions\ViewAction::make()
                ->label('عرض'),
            Tables\Actions\EditAction::make()
                ->label('تعديل'),
            Tables\Actions\RestoreAction::make()
                ->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
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
    // Form Sections Override (SuperAdmin-specific)
    // ========================================

    /**
     * Additional notes section for SuperAdmin.
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
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Table columns with teacher, circle, and academy columns.
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

            BadgeColumn::make('session_type')
                ->label('النوع')
                ->formatStateUsing(fn (string $state): string => static::formatSessionType($state))
                ->colors([
                    'primary' => 'individual',
                    'success' => 'group',
                    'warning' => 'trial',
                ]),

            TextColumn::make('scheduled_at')
                ->label('الموعد')
                ->dateTime('Y-m-d H:i')
                ->timezone(AcademyContextService::getTimezone())
                ->sortable(),

            TextColumn::make('duration_minutes')
                ->label('المدة')
                ->suffix(' د')
                ->sortable()
                ->toggleable(),

            BadgeColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof SessionStatus) {
                        return $state->label();
                    }
                    $status = SessionStatus::tryFrom($state);

                    return $status?->label() ?? $state;
                })
                ->colors(SessionStatus::colorOptions()),

            TextColumn::make('academy.name')
                ->label('الأكاديمية')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with teacher, academy, and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(SessionStatus::options()),

            SelectFilter::make('session_type')
                ->label('نوع الجلسة')
                ->options(static::getSessionTypeOptions()),

            SelectFilter::make('quran_teacher_id')
                ->label('المعلم')
                ->relationship('quranTeacher', 'name')
                ->searchable()
                ->preload(),

            SelectFilter::make('academy_id')
                ->label('الأكاديمية')
                ->relationship('academy', 'name')
                ->searchable()
                ->preload(),

            Filter::make('today')
                ->label('جلسات اليوم')
                ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

            Filter::make('this_week')
                ->label('هذا الأسبوع')
                ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])),

            Filter::make('completed')
                ->label('المكتملة')
                ->query(fn (Builder $query): Builder => $query->where('status', SessionStatus::COMPLETED->value)),

            Tables\Filters\TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranSessions::route('/'),
            'create' => Pages\CreateQuranSession::route('/create'),
            'view' => Pages\ViewQuranSession::route('/{record}'),
            'edit' => Pages\EditQuranSession::route('/{record}/edit'),
        ];
    }
}
