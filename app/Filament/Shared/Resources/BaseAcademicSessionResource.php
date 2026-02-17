<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\Action;
use App\Enums\AttendanceStatus;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Academic Session Resource
 *
 * Shared functionality for SuperAdmin and AcademicTeacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseAcademicSessionResource extends BaseResource
{
    protected static ?string $model = AcademicSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'جلسة أكاديمية';

    protected static ?string $pluralModelLabel = 'الجلسات الأكاديمية';

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
     * Get the session info form section (teacher/student selection differs by panel).
     */
    abstract protected static function getSessionInfoFormSection(): Section;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Schema $form): Schema
    {
        $schema = [];

        // Add session info section (panel-specific)
        $schema[] = static::getSessionInfoFormSection();

        // Add session details section
        $schema[] = static::getSessionDetailsFormSection();

        // Add timing section
        $schema[] = static::getTimingFormSection();

        // Add homework section
        $schema[] = static::getHomeworkFormSection();

        // Add additional sections from child classes
        $schema = array_merge($schema, static::getAdditionalFormSections());

        return $form->components($schema);
    }

    /**
     * Session details section - shared across panels.
     */
    protected static function getSessionDetailsFormSection(): Section
    {
        return Section::make('تفاصيل الجلسة')
            ->schema([
                TextInput::make('title')
                    ->label('عنوان الجلسة')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('وصف الجلسة')
                    ->helperText('أهداف ومحتوى الجلسة')
                    ->rows(3),

                Textarea::make('lesson_content')
                    ->label('محتوى الدرس')
                    ->rows(4),
            ]);
    }

    /**
     * Timing section - shared across panels.
     */
    protected static function getTimingFormSection(): Section
    {
        return Section::make('التوقيت والحالة')
            ->schema([
                Grid::make(2)
                    ->schema([
                        DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->timezone(AcademyContextService::getTimezone())
                            ->displayFormat('Y-m-d H:i')
                            ->helperText(function () {
                                $tz = AcademyContextService::getTimezone();
                                $label = match ($tz) {
                                    'Asia/Riyadh' => 'توقيت السعودية (GMT+3)',
                                    'Africa/Cairo' => 'توقيت مصر (GMT+2)',
                                    'Asia/Dubai' => 'توقيت الإمارات (GMT+4)',
                                    default => $tz,
                                };

                                return "⏰ الأوقات بـ {$label}";
                            }),

                        Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(60)
                            ->required(),

                        Select::make('status')
                            ->label('حالة الجلسة')
                            ->options(SessionStatus::options())
                            ->default(SessionStatus::SCHEDULED->value)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Homework section - shared across panels.
     */
    protected static function getHomeworkFormSection(): Section
    {
        return Section::make('الواجبات')
            ->schema([
                Toggle::make('homework_assigned')
                    ->label('يوجد واجب منزلي')
                    ->default(false)
                    ->live(),

                Textarea::make('homework_description')
                    ->label('وصف الواجب')
                    ->rows(3)
                    ->visible(fn ($get) => $get('homework_assigned')),

                FileUpload::make('homework_file')
                    ->label('ملف الواجب')
                    ->directory('academic-homework')
                    ->acceptedFileTypes(['pdf', 'doc', 'docx', 'jpg', 'png'])
                    ->visible(fn ($get) => $get('homework_assigned')),
            ]);
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
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions())
            ->modifyQueryUsing(fn ($query) => $query->with(['student', 'academy', 'academicTeacher', 'academicTeacher.user']));
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

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->limit(30),

            TextColumn::make('student.id')
                ->label('الطالب')
                ->formatStateUsing(fn ($record) => trim(($record->student?->first_name ?? '').' '.($record->student?->last_name ?? '')) ?: 'طالب #'.($record->student_id ?? '-')
                )
                ->searchable(),

            TextColumn::make('scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime()
                ->timezone(fn ($record) => $record->academy?->timezone?->value ?? AcademyContextService::getTimezone())
                ->sortable(),

            TextColumn::make('duration_minutes')
                ->label('المدة')
                ->suffix(' دقيقة')
                ->sortable(),

            TextColumn::make('status')
                ->badge()
                ->label('الحالة')
                ->colors(SessionStatus::colorOptions())
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof SessionStatus) {
                        return $state->label();
                    }
                    $status = SessionStatus::tryFrom($state);

                    return $status?->label() ?? (string) $state;
                }),

            TextColumn::make('attendance_status')
                ->badge()
                ->label('الحضور')
                ->colors(array_merge(
                    ['secondary' => SessionStatus::SCHEDULED->value],
                    AttendanceStatus::colorOptions()
                ))
                ->formatStateUsing(function (string $state): string {
                    if ($state === SessionStatus::SCHEDULED->value) {
                        return SessionStatus::SCHEDULED->label();
                    }
                    $status = AttendanceStatus::tryFrom($state);

                    return $status?->label() ?? $state;
                }),

            IconColumn::make('hasHomework')
                ->label('واجب')
                ->boolean()
                ->getStateUsing(fn ($record) => ! empty($record->homework_description) || ! empty($record->homework_file)),

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

            SelectFilter::make('attendance_status')
                ->label('حالة الحضور')
                ->options(array_merge(
                    [SessionStatus::SCHEDULED->value => SessionStatus::SCHEDULED->label()],
                    AttendanceStatus::options()
                )),

            Filter::make('scheduled_today')
                ->label('جلسات اليوم')
                ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

            Filter::make('scheduled_this_week')
                ->label('جلسات هذا الأسبوع')
                ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
        ];
    }

    // ========================================
    // Shared Session Control Actions
    // ========================================

    /**
     * Create the start session action - shared logic.
     */
    protected static function makeStartSessionAction(): Action
    {
        return Action::make('start_session')
            ->label('بدء الجلسة')
            ->icon('heroicon-o-play')
            ->color('success')
            ->visible(fn (AcademicSession $record): bool => $record->status instanceof SessionStatus
                    ? $record->status === SessionStatus::SCHEDULED
                    : $record->status === SessionStatus::SCHEDULED->value)
            ->action(function (AcademicSession $record) {
                $record->update([
                    'status' => SessionStatus::ONGOING->value,
                    'started_at' => now(),
                ]);
            });
    }

    /**
     * Create the complete session action - shared logic.
     */
    protected static function makeCompleteSessionAction(): Action
    {
        return Action::make('complete_session')
            ->label('إنهاء الجلسة')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (AcademicSession $record): bool => $record->status instanceof SessionStatus
                    ? $record->status === SessionStatus::ONGOING
                    : $record->status === SessionStatus::ONGOING->value)
            ->action(function (AcademicSession $record) {
                $record->update([
                    'status' => SessionStatus::COMPLETED->value,
                    'ended_at' => now(),
                    'actual_duration_minutes' => now()->diffInMinutes($record->started_at),
                    'attendance_status' => AttendanceStatus::ATTENDED->value,
                ]);
                // Update subscription usage
                $record->updateSubscriptionUsage();
            });
    }

    /**
     * Create the join meeting action - shared logic.
     */
    protected static function makeJoinMeetingAction(): Action
    {
        return Action::make('join_meeting')
            ->label('دخول الاجتماع')
            ->icon('heroicon-o-video-camera')
            ->url(fn (AcademicSession $record): string => $record->meeting_link ?? '#')
            ->openUrlInNewTab()
            ->visible(fn (AcademicSession $record): bool => ! empty($record->meeting_link));
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'academy',
                'academicTeacher.user',
                'academicSubscription',
                'academicIndividualLesson.academicSubject',
                'student',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
