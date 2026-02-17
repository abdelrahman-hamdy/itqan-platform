<?php

namespace App\Filament\Shared\Resources;

use App\Enums\RecordingStatus;
use App\Models\InteractiveCourseSession;
use App\Models\SessionRecording;
use App\Services\AcademyContextService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Session Recording Resource
 *
 * Shared functionality for SuperAdmin, Academy, and AcademicTeacher panels.
 * Child classes must implement query scoping and authorization methods.
 *
 * @see \App\Models\SessionRecording
 */
abstract class BaseSessionRecordingResource extends Resource
{
    protected static ?string $model = SessionRecording::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $modelLabel = 'تسجيل جلسة';

    protected static ?string $pluralModelLabel = 'تسجيلات الجلسات';

    protected static ?string $navigationLabel = 'تسجيلات الجلسات';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     */
    abstract protected static function getTableBulkActions(): array;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    /**
     * Recordings are created automatically - no manual creation allowed.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Recordings should not be manually edited.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Delete permission uses RecordingPolicy.
     */
    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete', $record) ?? false;
    }

    /**
     * View permission uses RecordingPolicy.
     */
    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view', $record) ?? false;
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('started_at', 'desc')
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions())
            ->emptyStateHeading('لا توجد تسجيلات')
            ->emptyStateDescription('لم يتم تسجيل أي جلسات بعد.')
            ->emptyStateIcon('heroicon-o-video-camera');
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            // Session Title (from polymorphic recordable)
            Tables\Columns\TextColumn::make('session_title')
                ->label('عنوان الجلسة')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHasMorph('recordable', [InteractiveCourseSession::class], function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%");
                    });
                })
                ->limit(40)
                ->getStateUsing(function (SessionRecording $record): string {
                    $session = $record->recordable;
                    if ($session instanceof InteractiveCourseSession) {
                        return $session->title ?? $session->course?->title ?? 'جلسة غير معروفة';
                    }

                    return $session?->title ?? 'جلسة غير معروفة';
                }),

            // Course Title (for InteractiveCourseSession)
            Tables\Columns\TextColumn::make('course_title')
                ->label('الدورة')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHasMorph('recordable', [InteractiveCourseSession::class], function ($q) use ($search) {
                        $q->whereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
                    });
                })
                ->limit(30)
                ->getStateUsing(function (SessionRecording $record): ?string {
                    $session = $record->recordable;
                    if ($session instanceof InteractiveCourseSession) {
                        return $session->course?->title;
                    }

                    return null;
                }),

            // Status Badge
            Tables\Columns\TextColumn::make('status')
                ->label('الحالة')
                ->badge()
                ->color(fn ($state): string => $state instanceof RecordingStatus
                    ? $state->color()
                    : (RecordingStatus::tryFrom($state)?->color() ?? 'gray'))
                ->formatStateUsing(fn ($state): string => $state instanceof RecordingStatus
                    ? $state->label()
                    : (RecordingStatus::tryFrom($state)?->label() ?? $state)),

            // Duration (formatted)
            Tables\Columns\TextColumn::make('formatted_duration')
                ->label('المدة')
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('duration', $direction)),

            // File Size (formatted)
            Tables\Columns\TextColumn::make('formatted_file_size')
                ->label('حجم الملف')
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('file_size', $direction)),

            // Recording Started At
            Tables\Columns\TextColumn::make('started_at')
                ->label('بدء التسجيل')
                ->dateTime('Y-m-d H:i')
                ->timezone(fn () => AcademyContextService::getTimezone())
                ->sortable(),

            // Completed At
            Tables\Columns\TextColumn::make('completed_at')
                ->label('اكتمل في')
                ->dateTime('Y-m-d H:i')
                ->timezone(fn () => AcademyContextService::getTimezone())
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            // Created At
            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get the table filters - shared across panels.
     */
    protected static function getTableFilters(): array
    {
        return [
            // Status Filter
            Tables\Filters\SelectFilter::make('status')
                ->label('الحالة')
                ->options(RecordingStatus::options())
                ->multiple(),

            // Date Range Filter - Recordings from today
            Tables\Filters\Filter::make('recorded_today')
                ->label('تسجيلات اليوم')
                ->query(fn (Builder $query): Builder => $query->whereDate('started_at', today())),

            // Date Range Filter - Recordings this week
            Tables\Filters\Filter::make('recorded_this_week')
                ->label('تسجيلات هذا الأسبوع')
                ->query(fn (Builder $query): Builder => $query->whereBetween('started_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])),

            // Available Recordings Only (completed with file_path)
            Tables\Filters\Filter::make('available_only')
                ->label('المتاحة للتحميل فقط')
                ->query(fn (Builder $query): Builder => $query
                    ->where('status', RecordingStatus::COMPLETED->value)
                    ->whereNotNull('file_path')),
        ];
    }

    // ========================================
    // Shared Infolist Definition (View Page)
    // ========================================

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('معلومات التسجيل')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('display_name')
                            ->label('اسم التسجيل'),
                        Infolists\Components\TextEntry::make('recording_id')
                            ->label('معرف التسجيل')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('meeting_room')
                            ->label('غرفة الاجتماع'),
                    ]),
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->color(fn ($state): string => $state instanceof RecordingStatus
                                ? $state->color()
                                : (RecordingStatus::tryFrom($state)?->color() ?? 'gray'))
                            ->formatStateUsing(fn ($state): string => $state instanceof RecordingStatus
                                ? $state->label()
                                : (RecordingStatus::tryFrom($state)?->label() ?? $state)),
                        Infolists\Components\TextEntry::make('formatted_duration')
                            ->label('المدة'),
                        Infolists\Components\TextEntry::make('formatted_file_size')
                            ->label('حجم الملف'),
                    ]),
                ]),

            Infolists\Components\Section::make('معلومات الجلسة')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('session_title')
                            ->label('عنوان الجلسة')
                            ->getStateUsing(fn (SessionRecording $record): string => $record->recordable?->title ?? 'غير متوفر'),
                        Infolists\Components\TextEntry::make('course_title')
                            ->label('الدورة')
                            ->getStateUsing(fn (SessionRecording $record): string => $record->recordable?->course?->title ?? 'غير متوفر'),
                        Infolists\Components\TextEntry::make('teacher_name')
                            ->label('المعلم')
                            ->getStateUsing(fn (SessionRecording $record): string => $record->recordable?->course?->assignedTeacher?->user?->name ?? 'غير متوفر'),
                    ]),
                ]),

            Infolists\Components\Section::make('التواريخ')
                ->schema([
                    Infolists\Components\Grid::make(4)->schema([
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('بدء التسجيل')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                        Infolists\Components\TextEntry::make('ended_at')
                            ->label('انتهاء التسجيل')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                        Infolists\Components\TextEntry::make('processed_at')
                            ->label('تاريخ المعالجة')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('تاريخ الاكتمال')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                    ]),
                ])
                ->collapsible(),

            Infolists\Components\Section::make('معلومات الملف')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('file_path')
                            ->label('مسار الملف')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('file_name')
                            ->label('اسم الملف'),
                        Infolists\Components\TextEntry::make('file_format')
                            ->label('صيغة الملف')
                            ->badge(),
                    ]),
                ])
                ->visible(fn (SessionRecording $record): bool => $record->isCompleted())
                ->collapsible(),

            Infolists\Components\Section::make('خطأ المعالجة')
                ->schema([
                    Infolists\Components\TextEntry::make('processing_error')
                        ->label('رسالة الخطأ')
                        ->columnSpanFull(),
                ])
                ->visible(fn (SessionRecording $record): bool => $record->hasFailed())
                ->collapsed(),
        ]);
    }

    // ========================================
    // Shared Session Recording Actions
    // ========================================

    /**
     * Create download action.
     */
    protected static function makeDownloadAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('download')
            ->label('تحميل')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->visible(fn (SessionRecording $record): bool => $record->isAvailable())
            ->url(fn (SessionRecording $record): string => $record->getDownloadUrl() ?? '#')
            ->openUrlInNewTab();
    }

    /**
     * Create stream/play action.
     */
    protected static function makeStreamAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('stream')
            ->label('تشغيل')
            ->icon('heroicon-o-play')
            ->color('primary')
            ->visible(fn (SessionRecording $record): bool => $record->isAvailable())
            ->url(fn (SessionRecording $record): string => $record->getStreamUrl() ?? '#')
            ->openUrlInNewTab();
    }

    /**
     * Create delete action (marks as DELETED and cleans up storage files).
     *
     * Storage file cleanup is handled by SessionRecordingObserver when
     * markAsDeleted() changes the status to 'deleted'.
     */
    protected static function makeDeleteAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('delete_recording')
            ->label('حذف')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('حذف التسجيل')
            ->modalDescription('هل أنت متأكد من حذف هذا التسجيل؟ سيتم حذف الملف من التخزين أيضاً.')
            ->modalSubmitActionLabel('نعم، احذف')
            ->visible(fn (SessionRecording $record): bool => $record->status->canDelete() &&
                auth()->user()?->can('delete', $record))
            ->action(fn (SessionRecording $record) => $record->markAsDeleted());
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'recordable',
                'recordable.course',
                'recordable.course.assignedTeacher',
                'recordable.course.assignedTeacher.user',
                'recordable.course.academy',
            ])
            // Only show InteractiveCourseSession recordings (as per requirement)
            ->where('recordable_type', InteractiveCourseSession::class);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
