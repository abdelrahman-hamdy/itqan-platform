<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\TrialRequestStatus;
use App\Filament\Shared\Resources\BaseQuranTrialRequestResource;
use App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages;
use App\Models\QuranTrialRequest;
use App\Services\AcademyContextService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Quran Trial Request Resource for Teacher Panel
 *
 * Teachers can view and manage their own trial requests only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseQuranTrialRequestResource for shared form/table definitions.
 */
class QuranTrialRequestResource extends BaseQuranTrialRequestResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 5;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter to current teacher's requests only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->where('academy_id', $user->academy_id);
    }

    /**
     * Get form schema for Teacher - limited fields.
     */
    protected static function getFormSchema(): array
    {
        return [
            static::getRequestInfoFormSection()
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
                        ]),
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
                        ]),
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
                        ]),
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
                                ->formatStateUsing(function ($state) {
                                    if (! $state) {
                                        return 'لم يتم الجدولة';
                                    }
                                    $timezone = \App\Services\AcademyContextService::getTimezone();

                                    return \Carbon\Carbon::parse($state)->setTimezone($timezone)->format('Y-m-d h:i A');
                                }),
                        ]),

                    Textarea::make('feedback')
                        ->label('ملاحظات الجلسة')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
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
                        // Rename field to match base class expectation
                        $data['teacher_response'] = $data['teacher_message'] ?? null;
                        static::executeScheduleAction($record, $data);
                    })
                    ->successNotificationTitle('تم جدولة الجلسة بنجاح')
                    ->successNotification(fn ($record) => \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('تم جدولة الجلسة التجريبية')
                        ->body("تم إنشاء غرفة اجتماع LiveKit للطالب {$record->student_name}")
                    ),
            ]),
        ];
    }

    /**
     * Bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
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

    // ========================================
    // Table Columns Override (Teacher-specific)
    // ========================================

    /**
     * Table columns with student details.
     */
    protected static function getTableColumns(): array
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
                ->formatStateUsing(fn (?int $state): string => $state ? $state.' سنة' : 'غير محدد'),

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
                ->dateTime('d/m/Y h:i A')
                ->timezone(fn ($record) => $record->academy?->timezone?->value ?? \App\Services\AcademyContextService::getTimezone())
                ->sortable()
                ->placeholder('غير مجدول'),

            TextColumn::make('created_at')
                ->label('تاريخ الطلب')
                ->dateTime('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override (Teacher-specific)
    // ========================================

    /**
     * Teacher-specific filters.
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('حالة الطلب')
                ->options(TrialRequestStatus::options()),

            Filter::make('needs_action')
                ->label('يتطلب إجراء')
                ->query(fn (Builder $query): Builder => $query->where('status', TrialRequestStatus::PENDING->value)
                    ->whereDoesntHave('trialSession')
                ),

            Filter::make('scheduled_today')
                ->label('مجدول اليوم')
                ->query(fn (Builder $query): Builder => $query->whereHas('trialSession', fn ($q) => $q->whereDate('scheduled_at', now()->toDateString())
                )
                ),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers cannot create new trial requests.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return false;
        }

        return $record->teacher_id === $user->quranTeacherProfile->id;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return false;
        }

        return $record->teacher_id === $user->quranTeacherProfile->id &&
               in_array($record->status, [TrialRequestStatus::PENDING->value, TrialRequestStatus::SCHEDULED->value]);
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTrialRequests::route('/'),
            'view' => Pages\ViewQuranTrialRequest::route('/{record}'),
            'edit' => Pages\EditQuranTrialRequest::route('/{record}/edit'),
        ];
    }
}
