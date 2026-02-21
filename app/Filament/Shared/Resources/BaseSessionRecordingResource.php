<?php

namespace App\Filament\Shared\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\Action;
use App\Enums\RecordingStatus;
use App\Models\InteractiveCourseSession;
use App\Models\SessionRecording;
use App\Services\AcademyContextService;
use Filament\Infolists;
use App\Filament\Resources\BaseResource;
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
abstract class BaseSessionRecordingResource extends BaseResource
{
    protected static ?string $model = SessionRecording::class;

    // SessionRecording has no direct academy relationship.
    // Disable Filament's tenant global scope - access control is handled
    // by scopeEloquentQuery() in each child resource.
    protected static bool $isScopedToTenant = false;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

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
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions())
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
            TextColumn::make('session_title')
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
            TextColumn::make('course_title')
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
            TextColumn::make('status')
                ->label('الحالة')
                ->badge()
                ->color(fn ($state): string => $state instanceof RecordingStatus
                    ? $state->color()
                    : (RecordingStatus::tryFrom($state)?->color() ?? 'gray'))
                ->formatStateUsing(fn ($state): string => $state instanceof RecordingStatus
                    ? $state->label()
                    : (RecordingStatus::tryFrom($state)?->label() ?? $state)),

            // Duration (formatted)
            TextColumn::make('formatted_duration')
                ->label('المدة')
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('duration', $direction)),

            // File Size (formatted)
            TextColumn::make('formatted_file_size')
                ->label('حجم الملف')
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('file_size', $direction)),

            // Recording Started At
            TextColumn::make('started_at')
                ->label('بدء التسجيل')
                ->dateTime('Y-m-d H:i')
                ->timezone(fn () => AcademyContextService::getTimezone())
                ->sortable(),

            // Completed At
            TextColumn::make('completed_at')
                ->label('اكتمل في')
                ->dateTime('Y-m-d H:i')
                ->timezone(fn () => AcademyContextService::getTimezone())
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            // Created At
            TextColumn::make('created_at')
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
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(RecordingStatus::options())
                ->searchable(),
        ];
    }

    // ========================================
    // Shared Infolist Definition (View Page)
    // ========================================

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('معلومات التسجيل')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('display_name')
                            ->label('اسم التسجيل'),
                        TextEntry::make('recording_id')
                            ->label('معرف التسجيل')
                            ->copyable(),
                        TextEntry::make('meeting_room')
                            ->label('غرفة الاجتماع'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->color(fn ($state): string => $state instanceof RecordingStatus
                                ? $state->color()
                                : (RecordingStatus::tryFrom($state)?->color() ?? 'gray'))
                            ->formatStateUsing(fn ($state): string => $state instanceof RecordingStatus
                                ? $state->label()
                                : (RecordingStatus::tryFrom($state)?->label() ?? $state)),
                        TextEntry::make('formatted_duration')
                            ->label('المدة'),
                        TextEntry::make('formatted_file_size')
                            ->label('حجم الملف'),
                    ]),
                ]),

            Section::make('معلومات الجلسة')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('session_title')
                            ->label('عنوان الجلسة')
                            ->getStateUsing(fn (SessionRecording $record): string => $record->recordable?->title ?? 'غير متوفر'),
                        TextEntry::make('course_title')
                            ->label('الدورة')
                            ->getStateUsing(fn (SessionRecording $record): string => $record->recordable?->course?->title ?? 'غير متوفر'),
                        TextEntry::make('teacher_name')
                            ->label('المعلم')
                            ->getStateUsing(fn (SessionRecording $record): string => $record->recordable?->course?->assignedTeacher?->user?->name ?? 'غير متوفر'),
                    ]),
                ]),

            Section::make('التواريخ')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('started_at')
                            ->label('بدء التسجيل')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                        TextEntry::make('ended_at')
                            ->label('انتهاء التسجيل')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                        TextEntry::make('processed_at')
                            ->label('تاريخ المعالجة')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                        TextEntry::make('completed_at')
                            ->label('تاريخ الاكتمال')
                            ->dateTime('Y-m-d H:i')
                            ->timezone(fn () => AcademyContextService::getTimezone()),
                    ]),
                ])
                ->collapsible(),

            Section::make('معلومات الملف')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('file_path')
                            ->label('مسار الملف')
                            ->copyable(),
                        TextEntry::make('file_name')
                            ->label('اسم الملف'),
                        TextEntry::make('file_format')
                            ->label('صيغة الملف')
                            ->badge(),
                    ]),
                ])
                ->visible(fn (SessionRecording $record): bool => $record->isCompleted())
                ->collapsible(),

            Section::make('خطأ المعالجة')
                ->schema([
                    TextEntry::make('processing_error')
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
    protected static function makeDownloadAction(): Action
    {
        return Action::make('download')
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
    protected static function makeStreamAction(): Action
    {
        return Action::make('stream')
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
    protected static function makeDeleteAction(): Action
    {
        return Action::make('delete_recording')
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
        // Use direct query instead of parent::getEloquentQuery() because
        // SessionRecording has no academy relationship for tenant scoping.
        // Access control is handled by scopeEloquentQuery() in each child resource.
        $query = static::getModel()::query()
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
