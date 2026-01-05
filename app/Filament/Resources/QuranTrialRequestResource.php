<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranTrialRequestResource\Pages;
use App\Models\QuranTrialRequest;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Services\AcademyContextService;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;

class QuranTrialRequestResource extends BaseResource
{
    
    protected static ?string $model = QuranTrialRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $modelLabel = 'طلب جلسة تجريبية';

    protected static ?string $pluralModelLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 8;

    /**
     * Get the navigation badge showing pending trial requests count
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', TrialRequestStatus::PENDING->value)->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', TrialRequestStatus::PENDING->value)->count() > 0 ? 'warning' : null;
    }

    /**
     * Get the navigation badge tooltip
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tabs.pending_requests');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['trialSession', 'student', 'teacher', 'academy'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الطلب')
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
                            ])
                    ]),

                Section::make('تفاصيل الطلب')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return User::where('user_type', 'student')
                                            ->where('academy_id', $academyId)
                                            ->get()
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('teacher_id')
                                    ->label('المعلم')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();

                                        $teachers = \App\Models\QuranTeacherProfile::where('academy_id', $academyId)
                                            ->where('is_active', true)
                                            ->get();

                                        if ($teachers->isEmpty()) {
                                            return [];
                                        }

                                        return $teachers->mapWithKeys(function ($teacher) {
                                            $displayName = $teacher->display_name
                                                ?? ($teacher->full_name ?? 'معلم غير محدد') . ' (' . ($teacher->teacher_code ?? 'N/A') . ')';
                                            return [$teacher->id => $displayName];
                                        })->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),

                Section::make('تفاصيل التعلم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('current_level')
                                    ->label('المستوى الحالي')
                                    ->options(QuranTrialRequest::LEVELS)
                                    ->required()
                                    ->native(false),

                                Select::make('preferred_time')
                                    ->label('الوقت المفضل')
                                    ->options(QuranTrialRequest::TIMES)
                                    ->native(false),
                            ]),

                        Textarea::make('notes')
                            ->label('ملاحظات الطالب')
                            ->rows(3),
                    ]),


                Section::make('تقييم الجلسة')
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
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_code')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.full_name')
                    ->label('المعلم')
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
                        if (!$state) return '-';
                        return "{$state}/10";
                    })
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 8 => 'success',
                        $state >= 5 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(TrialRequestStatus::options()),

                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $academyId = AcademyContextService::getCurrentAcademyId();
                        return \App\Models\QuranTeacherProfile::where('academy_id', $academyId)
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(function ($teacher) {
                                return [$teacher->id => $teacher->display_name];
                            });
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('current_level')
                    ->label('المستوى')
                    ->options(QuranTrialRequest::LEVELS),

                Filter::make('scheduled_date')
                    ->form([
                        DatePicker::make('scheduled_from')
                            ->label('من تاريخ'),
                        DatePicker::make('scheduled_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn (Builder $query, $date): Builder => $query->whereHas('trialSession', fn ($q) =>
                                    $q->whereDate('scheduled_at', '>=', $date)
                                ),
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('trialSession', fn ($q) =>
                                    $q->whereDate('scheduled_at', '<=', $date)
                                ),
                            );
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('schedule')
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
                            try {
                                // Parse the datetime in academy timezone and let Laravel convert to UTC for storage
                                $scheduledAt = \Carbon\Carbon::parse($data['scheduled_at'], AcademyContextService::getTimezone());
                                $teacherResponse = $data['teacher_response'] ?? 'تم جدولة الجلسة التجريبية';

                                // Generate unique session code
                                $sessionCode = 'TR-' . str_pad($record->teacher_id, 3, '0', STR_PAD_LEFT) . '-' . $scheduledAt->format('Ymd-Hi');

                                // Create QuranSession with LiveKit integration
                                $session = \App\Models\QuranSession::create([
                                    'academy_id' => $record->academy_id,
                                    'session_code' => $sessionCode,
                                    'session_type' => 'trial',
                                    'quran_teacher_id' => $record->teacher->user_id,
                                    'student_id' => $record->student_id,
                                    'trial_request_id' => $record->id,
                                    'scheduled_at' => $scheduledAt,
                                    'duration_minutes' => 30,
                                    'status' => \App\Enums\SessionStatus::SCHEDULED, // Use enum
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
                                \Log::error('Trial session creation failed in Filament', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'trial_request_id' => $record->id,
                                ]);

                                throw $e;
                            }
                        })
                        ->successNotificationTitle('تم جدولة الجلسة بنجاح')
                        ->successNotification(fn ($record) =>
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('تم جدولة الجلسة التجريبية')
                                ->body("تم إنشاء غرفة اجتماع LiveKit للطالب {$record->student_name}")
                        ),

                    Tables\Actions\Action::make('cancel')
                        ->label('إلغاء الطلب')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (QuranTrialRequest $record) => $record->isPending())
                        ->requiresConfirmation()
                        ->modalHeading('إلغاء طلب الجلسة التجريبية')
                        ->modalDescription('هل أنت متأكد من إلغاء هذا الطلب؟')
                        ->action(fn (QuranTrialRequest $record) => $record->cancel())
                        ->successNotificationTitle('تم إلغاء الطلب'),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make()
                        ->label(__('filament.actions.restore')),
                    Tables\Actions\ForceDeleteAction::make()
                        ->label(__('filament.actions.force_delete')),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

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
                            ])
                    ]),

                Infolists\Components\Section::make('معلومات الطالب والمعلم')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),

                                Infolists\Components\TextEntry::make('teacher.full_name')
                                    ->label('المعلم'),
                            ])
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
                                    ->formatStateUsing(fn (string $state): string => QuranTrialRequest::TIMES[$state] ?? $state),
                            ]),

                        Infolists\Components\TextEntry::make('learning_goals')
                            ->label('أهداف التعلم')
                            ->listWithLineBreaks()
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state)) return '-';
                                $goals = [
                                    'reading' => 'تعلم القراءة الصحيحة',
                                    'tajweed' => 'تعلم أحكام التجويد',
                                    'memorization' => 'حفظ القرآن الكريم',
                                    'improvement' => 'تحسين الأداء والإتقان'
                                ];
                                return collect($state)->map(fn ($goal) => $goals[$goal] ?? $goal)->toArray();
                            }),

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
                                        if (!$state) return '-';
                                        return "{$state}/10";
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTrialRequests::route('/'),
            'create' => Pages\CreateQuranTrialRequest::route('/create'),
            'view' => Pages\ViewQuranTrialRequest::route('/{record}'),
            'edit' => Pages\EditQuranTrialRequest::route('/{record}/edit'),
        ];
    }

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }
}