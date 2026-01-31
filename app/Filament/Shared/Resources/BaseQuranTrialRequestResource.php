<?php

namespace App\Filament\Shared\Resources;

use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use App\Models\QuranTrialRequest;
use App\Services\AcademyContextService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Quran Trial Request Resource
 *
 * Shared functionality for SuperAdmin and Teacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseQuranTrialRequestResource extends Resource
{
    protected static ?string $model = QuranTrialRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $modelLabel = 'طلب جلسة تجريبية';

    protected static ?string $pluralModelLabel = 'طلبات الجلسات التجريبية';

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
     * Get the form schema for this panel.
     */
    abstract protected static function getFormSchema(): array;

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
        return false;
    }

    // ========================================
    // Navigation Badge
    // ========================================

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', TrialRequestStatus::PENDING->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getEloquentQuery()->where('status', TrialRequestStatus::PENDING->value)->count();

        return $count > 0 ? 'warning' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tabs.pending_requests');
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Form $form): Form
    {
        return $form->schema(static::getFormSchema());
    }

    /**
     * Request info section - shared across panels.
     */
    protected static function getRequestInfoFormSection(): Section
    {
        return Section::make('معلومات الطلب')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('request_code')
                            ->label('رقم الطلب')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status')
                            ->label('حالة الطلب')
                            ->options(TrialRequestStatus::options())
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    /**
     * Session evaluation section - shared across panels.
     */
    protected static function getSessionEvaluationFormSection(): Section
    {
        return Section::make('تقييم الجلسة')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('rating')
                            ->label('التقييم')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->step(1)
                            ->suffix('/ 10')
                            ->helperText('أدخل تقييمًا من 1 إلى 10'),

                        DateTimePicker::make('completed_at')
                            ->label('تاريخ اكتمال الجلسة')
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Textarea::make('feedback')
                    ->label('ملاحظات الجلسة')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    /**
     * Get the table columns - shared across panels.
     * Override in child classes for panel-specific columns.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('request_code')
                ->label('رقم الطلب')
                ->searchable()
                ->sortable()
                ->copyable()
                ->weight(FontWeight::Bold),

            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable(),

            BadgeColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn (TrialRequestStatus $state): string => $state->label())
                ->colors(TrialRequestStatus::colorOptions()),

            TextColumn::make('current_level')
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => QuranTrialRequest::LEVELS[$state] ?? $state)
                ->badge()
                ->color('info'),

            TextColumn::make('trialSession.scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime()
                ->timezone(fn ($record) => $record->academy?->timezone?->value ?? 'Asia/Riyadh')
                ->sortable()
                ->placeholder('لم يتم الجدولة'),

            TextColumn::make('rating')
                ->label('التقييم')
                ->formatStateUsing(function ($state) {
                    if (! $state) {
                        return '-';
                    }

                    return "{$state}/10";
                })
                ->badge()
                ->color(fn ($state) => match (true) {
                    $state >= 8 => 'success',
                    $state >= 5 => 'warning',
                    default => 'danger',
                }),

            TextColumn::make('created_at')
                ->label('تاريخ الطلب')
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
                ->options(TrialRequestStatus::options()),

            SelectFilter::make('current_level')
                ->label('المستوى')
                ->options(QuranTrialRequest::LEVELS),
        ];
    }

    // ========================================
    // Shared Schedule Action Logic
    // ========================================

    /**
     * Create the schedule trial session action - shared logic.
     */
    protected static function makeScheduleAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('schedule')
            ->label('جدولة')
            ->icon('heroicon-o-calendar')
            ->color('warning')
            ->visible(fn (QuranTrialRequest $record) => $record->canBeScheduled())
            ->form([
                DateTimePicker::make('scheduled_at')
                    ->label('موعد الجلسة')
                    ->required()
                    ->native(false)
                    ->timezone(AcademyContextService::getTimezone())
                    ->minDate(now())
                    ->helperText('سيتم إنشاء غرفة اجتماع LiveKit تلقائياً'),

                Textarea::make('teacher_response')
                    ->label('رسالة للطالب (اختياري)')
                    ->rows(3)
                    ->placeholder('اكتب رسالة ترحيبية أو تعليمات للطالب...'),
            ])
            ->action(function (QuranTrialRequest $record, array $data) {
                static::executeScheduleAction($record, $data);
            })
            ->successNotificationTitle('تم جدولة الجلسة بنجاح')
            ->successNotification(fn ($record) => \Filament\Notifications\Notification::make()
                ->success()
                ->title('تم جدولة الجلسة التجريبية')
                ->body("تم إنشاء غرفة اجتماع LiveKit للطالب {$record->student_name}")
            );
    }

    /**
     * Execute the schedule action - shared logic.
     */
    protected static function executeScheduleAction(QuranTrialRequest $record, array $data): void
    {
        try {
            // Parse the datetime in academy timezone
            $scheduledAt = \Carbon\Carbon::parse($data['scheduled_at'], AcademyContextService::getTimezone());
            $teacherResponse = $data['teacher_response'] ?? 'تم جدولة الجلسة التجريبية';

            // Convert to UTC for storage - Laravel's Eloquent does NOT auto-convert!
            $scheduledAtUtc = AcademyContextService::toUtcForStorage($scheduledAt);

            // Generate unique session code (use original timezone for display in code)
            $sessionCode = 'TR-'.str_pad($record->teacher_id, 3, '0', STR_PAD_LEFT).'-'.$scheduledAt->format('Ymd-Hi');

            // Create QuranSession with LiveKit integration
            $session = \App\Models\QuranSession::create([
                'academy_id' => $record->academy_id,
                'session_code' => $sessionCode,
                'session_type' => 'trial',
                'quran_teacher_id' => $record->teacher->user_id,
                'student_id' => $record->student_id,
                'trial_request_id' => $record->id,
                'scheduled_at' => $scheduledAtUtc,
                'duration_minutes' => 30,
                'status' => SessionStatus::SCHEDULED,
                'title' => "جلسة تجريبية - {$record->student_name}",
                'description' => $teacherResponse,
                'location_type' => 'online',
                'created_by' => auth()->id(),
                'scheduled_by' => auth()->id(),
            ]);

            // Generate LiveKit meeting room
            $session->generateMeetingLink();

            // Status sync happens automatically via QuranSessionObserver
        } catch (\Exception $e) {
            \Log::error('Trial session creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'trial_request_id' => $record->id,
            ]);

            throw $e;
        }
    }

    // ========================================
    // Shared Infolist Definition
    // ========================================

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('request_code')
                                    ->label('رقم الطلب')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->formatStateUsing(fn (TrialRequestStatus $state): string => $state->label())
                                    ->badge()
                                    ->color(fn (TrialRequestStatus $state): string => $state->color()),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الطلب')
                                    ->dateTime(),
                            ]),
                    ]),

                Infolists\Components\Section::make('معلومات الطالب والمعلم')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),

                                Infolists\Components\TextEntry::make('teacher.full_name')
                                    ->label('المعلم'),
                            ]),
                    ]),

                Infolists\Components\Section::make('تفاصيل التعلم')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('current_level')
                                    ->label('المستوى الحالي')
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::LEVELS[$state] ?? $state),

                                Infolists\Components\TextEntry::make('preferred_time')
                                    ->label('الوقت المفضل')
                                    ->formatStateUsing(fn (?string $state): string => $state ? (QuranTrialRequest::TIMES[$state] ?? $state) : '-'),
                            ]),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات الطالب')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('تفاصيل الجلسة')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('trialSession.scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->dateTime()
                                    ->placeholder('لم يتم تحديد موعد'),

                                Infolists\Components\TextEntry::make('trialSession.meeting.room_name')
                                    ->label('غرفة الاجتماع')
                                    ->placeholder('لم يتم إنشاء غرفة')
                                    ->formatStateUsing(fn ($state) => $state ? "LiveKit: {$state}" : '-'),

                                Infolists\Components\TextEntry::make('trialSession.status')
                                    ->label('حالة الجلسة')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-')
                                    ->color(fn ($state) => $state?->color() ?? 'gray'),

                                Infolists\Components\TextEntry::make('rating')
                                    ->label('التقييم')
                                    ->formatStateUsing(function ($state) {
                                        if (! $state) {
                                            return '-';
                                        }

                                        return "{$state}/10";
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        $state >= 8 => 'success',
                                        $state >= 5 => 'warning',
                                        default => 'danger',
                                    }),
                            ]),

                        Infolists\Components\TextEntry::make('feedback')
                            ->label('ملاحظات الجلسة')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'trialSession',
                'student',
                'teacher',
                'academy',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
