<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages;
use App\Models\QuranTrialRequest;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Teacher\Resources\BaseTeacherResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use App\Services\AcademyContextService;

class QuranTrialRequestResource extends BaseTeacherResource
{
    protected static ?string $model = QuranTrialRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $modelLabel = 'طلب جلسة تجريبية';

    protected static ?string $pluralModelLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 5;

    /**
     * Check if current user can view this record
     * Teachers can only view trial requests assigned to them
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return false;
        }

        // Allow viewing if trial request belongs to current teacher
        return $record->teacher_id === $user->quranTeacherProfile->id;
    }

    /**
     * Check if current user can edit this record
     * Teachers have limited editing capabilities for trial requests
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return false;
        }

        // Allow editing if trial request belongs to current teacher
        // Teachers can update status and schedule trial sessions
        return $record->teacher_id === $user->quranTeacherProfile->id &&
               in_array($record->status, [TrialRequestStatus::PENDING->value, TrialRequestStatus::SCHEDULED->value]);
    }

    /**
     * Get the Eloquent query with teacher-specific filtering
     * Only show trial requests for the current teacher
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->where('academy_id', $user->academy_id);
    }

    /**
     * Teachers cannot create new trial requests
     * This is managed by students/parents
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getTeacherFormSchema());
    }

    /**
     * Get form schema customized for teachers
     * Teachers can update status and add notes
     */
    protected static function getTeacherFormSchema(): array
    {
        return [
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
                                ->native(false)
                                ->helperText('يمكن للمعلم تحديث حالة الطلب'),
                        ])
                ]),

            Section::make('معلومات الطالب')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('student_name')
                                ->label('اسم الطالب')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('student_age')
                                ->label('عمر الطالب')
                                ->disabled()
                                ->dehydrated(false)
                                ->suffix(' سنة'),

                            TextInput::make('phone')
                                ->label('رقم هاتف الطالب')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                ]),

            Section::make('تفاصيل التعلم')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('current_level')
                                ->label('المستوى الحالي')
                                ->options(QuranTrialRequest::LEVELS)
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('preferred_time')
                                ->label('الوقت المفضل')
                                ->options(QuranTrialRequest::TIMES)
                                ->disabled()
                                ->dehydrated(false),
                        ])
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(4)
                        ->helperText('ملاحظات حول الطالب والجلسة التجريبية')
                        ->columnSpanFull(),
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

                            TextInput::make('trialSession.scheduled_at')
                                ->label('موعد الجلسة')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d H:i') : 'لم يتم الجدولة'),
                        ]),

                    Textarea::make('feedback')
                        ->label('ملاحظات الجلسة')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTeacherTableColumns())
            ->filters(static::getTeacherTableFilters())
            ->actions(static::getTeacherTableActions())
            ->bulkActions(static::supportsBulkActions() ? static::getTeacherBulkActions() : []);
    }

    /**
     * Get table columns customized for teachers
     */
    protected static function getTeacherTableColumns(): array
    {
        return [
            TextColumn::make('request_code')
                ->label('رقم الطلب')
                ->searchable()
                ->copyable()
                ->weight(FontWeight::Bold),

            TextColumn::make('student_name')
                ->label('اسم الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('student_age')
                ->label('العمر')
                ->formatStateUsing(fn (?int $state): string => $state ? $state . ' سنة' : 'غير محدد'),

            TextColumn::make('phone')
                ->label('الهاتف')
                ->searchable()
                ->copyable(),

            BadgeColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn (TrialRequestStatus $state): string => $state->label())
                ->color(fn (TrialRequestStatus $state): string => $state->color()),

            TextColumn::make('trialSession.scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime('d/m/Y H:i')
                ->timezone(fn ($record) => $record->academy?->timezone?->value ?? 'Asia/Riyadh')
                ->sortable()
                ->placeholder('غير مجدول'),

            TextColumn::make('created_at')
                ->label('تاريخ الطلب')
                ->dateTime('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get table filters for teachers
     */
    protected static function getTeacherTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('حالة الطلب')
                ->options(TrialRequestStatus::options()),

            Filter::make('needs_action')
                ->label('يتطلب إجراء')
                ->query(fn (Builder $query): Builder =>
                    $query->where('status', TrialRequestStatus::PENDING->value)
                          ->whereDoesntHave('trialSession')
                ),

            Filter::make('scheduled_today')
                ->label('مجدول اليوم')
                ->query(fn (Builder $query): Builder =>
                    $query->whereHas('trialSession', fn ($q) =>
                        $q->whereDate('scheduled_at', now()->toDateString())
                    )
                ),
        ];
    }

    /**
     * Get table actions for teachers
     */
    protected static function getTeacherTableActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-m-eye'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-m-pencil')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Only allow teachers to update specific fields
                        return [
                            'status' => $data['status'] ?? null,
                            'scheduled_at' => $data['scheduled_at'] ?? null,
                            'suitability_assessment' => $data['suitability_assessment'] ?? null,
                            'teacher_notes' => $data['teacher_notes'] ?? null,
                        ];
                    }),

                Tables\Actions\Action::make('schedule_session')
                    ->label('جدولة الجلسة')
                    ->icon('heroicon-m-calendar')
                    ->color('info')
                    ->visible(fn (QuranTrialRequest $record): bool => $record->status === TrialRequestStatus::PENDING)
                    ->form([
                        DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة التجريبية')
                            ->required()
                            ->native(false)
                            ->timezone(AcademyContextService::getTimezone())
                            ->minDate(now())
                            ->helperText('سيتم إنشاء غرفة اجتماع LiveKit تلقائياً'),

                        Textarea::make('teacher_message')
                            ->label('رسالة للطالب (اختياري)')
                            ->rows(3)
                            ->placeholder('اكتب رسالة ترحيبية أو تعليمات للطالب...'),
                    ])
                    ->action(function (QuranTrialRequest $record, array $data) {
                        try {
                            // Parse the datetime in academy timezone and let Laravel convert to UTC for storage
                            $scheduledAt = \Carbon\Carbon::parse($data['scheduled_at'], AcademyContextService::getTimezone());
                            $teacherMessage = $data['teacher_message'] ?? 'تم جدولة الجلسة التجريبية';

                            // Generate unique session code
                            $sessionCode = 'TR-' . str_pad($record->teacher_id, 3, '0', STR_PAD_LEFT) . '-' . $scheduledAt->format('Ymd-Hi');

                            \Log::info('Creating trial session from Teacher Panel', [
                                'trial_request_id' => $record->id,
                                'teacher_id' => $record->teacher->user_id,
                                'student_id' => $record->student_id,
                                'session_code' => $sessionCode,
                            ]);

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
                                'status' => \App\Enums\SessionStatus::SCHEDULED,
                                'title' => "جلسة تجريبية - {$record->student_name}",
                                'description' => $teacherMessage,
                                'location_type' => 'online',
                                'created_by' => auth()->id(),
                                'scheduled_by' => auth()->id(),
                            ]);

                            \Log::info('Trial session created from Teacher Panel', [
                                'session_id' => $session->id,
                                'session_code' => $session->session_code,
                            ]);

                            // Generate LiveKit meeting room
                            $session->generateMeetingLink();

                            \Log::info('LiveKit meeting generated from Teacher Panel', [
                                'session_id' => $session->id,
                            ]);

                            // Status sync happens automatically via QuranSessionObserver
                        } catch (\Exception $e) {
                            \Log::error('Trial session creation failed in Teacher Panel', [
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
            ]),
        ];
    }

    /**
     * Get bulk actions for teachers
     */
    protected static function getTeacherBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('bulk_cancel')
                ->label('إلغاء جماعي')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function ($records) {
                    foreach ($records as $record) {
                        if ($record->status === TrialRequestStatus::PENDING->value) {
                            $record->cancel();
                        }
                    }
                }),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTrialRequests::route('/'),
            'view' => Pages\ViewQuranTrialRequest::route('/{record}'),
            'edit' => Pages\EditQuranTrialRequest::route('/{record}/edit'),
        ];
    }
}