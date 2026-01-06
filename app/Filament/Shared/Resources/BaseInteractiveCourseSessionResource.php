<?php

namespace App\Filament\Shared\Resources;

use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Models\InteractiveCourseSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Interactive Course Session Resource
 *
 * Shared functionality for SuperAdmin and AcademicTeacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseInteractiveCourseSessionResource extends Resource
{
    protected static ?string $model = InteractiveCourseSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $modelLabel = 'جلسة دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'جلسات الدورات التفاعلية';

    protected static ?string $navigationLabel = 'جلسات الدورات التفاعلية';

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

    /**
     * Get the session info form section (course selection differs by panel).
     */
    abstract protected static function getSessionInfoFormSection(): Section;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Form $form): Form
    {
        $schema = [];

        // Add session info section (panel-specific)
        $schema[] = static::getSessionInfoFormSection();

        // Add timing section
        $schema[] = static::getTimingFormSection();

        // Add content section
        $schema[] = static::getContentFormSection();

        // Add homework section
        $schema[] = static::getHomeworkFormSection();

        // Add additional sections from child classes
        $schema = array_merge($schema, static::getAdditionalFormSections());

        return $form->schema($schema);
    }

    /**
     * Timing section - shared across panels.
     */
    protected static function getTimingFormSection(): Section
    {
        return Section::make('التوقيت والحالة')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->timezone(fn () => AcademyContextService::getTimezone())
                            ->displayFormat('Y-m-d H:i'),

                        Forms\Components\Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(60)
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options(SessionStatus::options())
                            ->default(SessionStatus::SCHEDULED->value)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Content section - shared across panels.
     */
    protected static function getContentFormSection(): Section
    {
        return Section::make('محتوى الجلسة')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان الجلسة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('وصف الجلسة')
                    ->helperText('أهداف ومحتوى الجلسة')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('lesson_content')
                    ->label('محتوى الدرس')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Homework section - shared across panels.
     */
    protected static function getHomeworkFormSection(): Section
    {
        return Section::make('الواجبات')
            ->schema([
                Forms\Components\Toggle::make('homework_assigned')
                    ->label('يوجد واجب منزلي')
                    ->default(false)
                    ->live(),

                Forms\Components\Textarea::make('homework_description')
                    ->label('وصف الواجب')
                    ->rows(3)
                    ->visible(fn ($get) => $get('homework_assigned'))
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('homework_file')
                    ->label('ملف الواجب')
                    ->directory('interactive-course-homework')
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'])
                    ->visible(fn ($get) => $get('homework_assigned')),
            ])
            ->collapsible();
    }

    /**
     * Get additional form sections - override in child classes.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [];
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions())
            ->emptyStateHeading('لا توجد جلسات')
            ->emptyStateDescription('لم يتم جدولة أي جلسات بعد.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('session_code')
                ->label('رمز الجلسة')
                ->searchable()
                ->sortable(),

            TextColumn::make('course.title')
                ->label('الدورة')
                ->searchable()
                ->limit(30),

            TextColumn::make('session_number')
                ->label('رقم الجلسة')
                ->numeric()
                ->sortable(),

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->limit(30),

            TextColumn::make('scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime()
                ->timezone(fn () => AcademyContextService::getTimezone())
                ->sortable(),

            TextColumn::make('duration_minutes')
                ->label('المدة')
                ->suffix(' دقيقة')
                ->sortable(),

            Tables\Columns\BadgeColumn::make('status')
                ->label('الحالة')
                ->colors(SessionStatus::colorOptions())
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof SessionStatus) {
                        return $state->label();
                    }
                    $statusEnum = SessionStatus::tryFrom($state);

                    return $statusEnum?->label() ?? $state;
                }),

            Tables\Columns\IconColumn::make('homework_assigned')
                ->label('واجب')
                ->boolean(),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
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
                ->options(SessionStatus::options()),

            Tables\Filters\Filter::make('scheduled_today')
                ->label('جلسات اليوم')
                ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

            Tables\Filters\Filter::make('scheduled_this_week')
                ->label('جلسات هذا الأسبوع')
                ->query(fn (Builder $query): Builder => $query->thisWeek()),

            Tables\Filters\TernaryFilter::make('homework_assigned')
                ->label('الواجبات')
                ->placeholder('الكل')
                ->trueLabel('بها واجبات')
                ->falseLabel('بدون واجبات'),
        ];
    }

    // ========================================
    // Shared Session Control Actions
    // ========================================

    /**
     * Create start session action.
     */
    protected static function makeStartSessionAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('start_session')
            ->label('بدء الجلسة')
            ->icon('heroicon-o-play')
            ->color('success')
            ->visible(fn (InteractiveCourseSession $record): bool => in_array(
                $record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status),
                [SessionStatus::SCHEDULED, SessionStatus::READY]
            ))
            ->action(fn (InteractiveCourseSession $record) => $record->markAsOngoing());
    }

    /**
     * Create complete session action.
     */
    protected static function makeCompleteSessionAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('complete_session')
            ->label('إنهاء الجلسة')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (InteractiveCourseSession $record): bool => ($record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status)) === SessionStatus::ONGOING
            )
            ->action(fn (InteractiveCourseSession $record) => $record->markAsCompleted());
    }

    /**
     * Create cancel session action.
     */
    protected static function makeCancelSessionAction(string $cancelledBy = 'admin'): Tables\Actions\Action
    {
        $label = $cancelledBy === 'teacher' ? 'ألغيت بواسطة المعلم' : 'ألغيت بواسطة المدير';

        return Tables\Actions\Action::make('cancel_session')
            ->label('إلغاء الجلسة')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (InteractiveCourseSession $record): bool => in_array(
                $record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status),
                [SessionStatus::SCHEDULED, SessionStatus::READY]
            ))
            ->requiresConfirmation()
            ->action(fn (InteractiveCourseSession $record) => $record->markAsCancelled($label, auth()->user(), $cancelledBy));
    }

    /**
     * Create join meeting action.
     */
    protected static function makeJoinMeetingAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('join_meeting')
            ->label('دخول الاجتماع')
            ->icon('heroicon-o-video-camera')
            ->url(fn (InteractiveCourseSession $record): string => $record->meeting_link ?? '#')
            ->openUrlInNewTab()
            ->visible(fn (InteractiveCourseSession $record): bool => ! empty($record->meeting_link));
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'course',
                'course.assignedTeacher',
                'course.assignedTeacher.user',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
