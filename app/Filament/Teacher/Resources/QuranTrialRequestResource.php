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
use App\Enums\SubscriptionStatus;

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
               in_array($record->status, ['pending', 'approved', 'scheduled']);
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
     * Teachers can update status, schedule, and add notes
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
                                ->options([
                                    SubscriptionStatus::PENDING->value => 'في الانتظار',
                                    'approved' => 'موافق عليه',
                                    SessionStatus::SCHEDULED->value => 'مجدول',
                                    SessionStatus::COMPLETED->value => 'مكتمل',
                                    'rejected' => 'مرفوض',
                                    SessionStatus::CANCELLED->value => 'ملغي',
                                    'no_show' => 'لم يحضر',
                                ])
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

                            TextInput::make('student_phone')
                                ->label('رقم هاتف الطالب')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('student_email')
                                ->label('البريد الإلكتروني')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                ]),

            Section::make('معلومات الحفل والبرنامج')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('preferred_package_id')
                                ->label('الباقة المفضلة')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('preferred_time_slot')
                                ->label('الوقت المفضل')
                                ->options([
                                    'morning' => 'الفترة الصباحية (8-12)',
                                    'afternoon' => 'الفترة المسائية (12-17)',
                                    'evening' => 'الفترة المسائية (17-21)',
                                ])
                                ->disabled()
                                ->dehydrated(false),

                            DateTimePicker::make('scheduled_at')
                                ->label('موعد الجلسة التجريبية')
                                ->native(false)
                                ->helperText('يمكن للمعلم تحديد موعد الجلسة التجريبية'),

                            DateTimePicker::make('duration_minutes')
                                ->label('مدة الجلسة')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('بالدقائق'),
                        ])
                ]),

            Section::make('ملاحظات وتقييم')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('interest_level')
                                ->label('مستوى اهتمام الطالب')
                                ->options([
                                    'high' => 'عالي',
                                    'medium' => 'متوسط',
                                    'low' => 'منخفض',
                                ])
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('suitability_assessment')
                                ->label('تقييم الملائمة')
                                ->options([
                                    'excellent' => 'ممتاز',
                                    'good' => 'جيد',
                                    'acceptable' => 'مقبول',
                                    'poor' => 'ضعيف',
                                ])
                                ->helperText('تقييم المعلم لملائمة الطالب للبرنامج'),
                        ])
                        ->columnSpanFull(),

                    Textarea::make('teacher_notes')
                        ->label('ملاحظات المعلم')
                        ->rows(4)
                        ->helperText('ملاحظات المعلم حول الطالب والجلسة التجريبية')
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

            TextColumn::make('student_phone')
                ->label('الهاتف')
                ->searchable()
                ->copyable(),

            BadgeColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    SubscriptionStatus::PENDING->value => 'في الانتظار',
                    'approved' => 'موافق عليه',
                    SessionStatus::SCHEDULED->value => 'مجدول',
                    SessionStatus::COMPLETED->value => 'مكتمل',
                    'rejected' => 'مرفوض',
                    SessionStatus::CANCELLED->value => 'ملغي',
                    'no_show' => 'لم يحضر',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    SubscriptionStatus::PENDING->value => 'warning',
                    'approved' => 'success',
                    SessionStatus::SCHEDULED->value => 'info',
                    SessionStatus::COMPLETED->value => 'primary',
                    'rejected' => 'danger',
                    SessionStatus::CANCELLED->value => 'gray',
                    'no_show' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime('d/m/Y H:i')
                ->timezone(fn ($record) => $record->academy->timezone->value)
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
                ->options([
                    SubscriptionStatus::PENDING->value => 'في الانتظار',
                    'approved' => 'موافق عليه',
                    SessionStatus::SCHEDULED->value => 'مجدول',
                    SessionStatus::COMPLETED->value => 'مكتمل',
                    'rejected' => 'مرفوض',
                    SessionStatus::CANCELLED->value => 'ملغي',
                    'no_show' => 'لم يحضر',
                ]),

            Filter::make('needs_action')
                ->label('يتطلب إجراء')
                ->query(fn (Builder $query): Builder => 
                    $query->whereIn('status', ['pending', 'approved'])
                          ->whereNull('scheduled_at')
                ),

            Filter::make('scheduled_today')
                ->label('مجدول اليوم')
                ->query(fn (Builder $query): Builder => 
                    $query->whereDate('scheduled_at', now()->toDateString())
                ),

            Filter::make('overdue')
                ->label('متأخر')
                ->query(fn (Builder $query): Builder => 
                    $query->where('status', 'approved')
                          ->where('scheduled_at', '<', now())
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

                Tables\Actions\Action::make('quick_approve')
                    ->label('موافقة سريعة')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (QuranTrialRequest $record): bool => $record->status === 'pending')
                    ->action(function (QuranTrialRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'teacher_notes' => $record->teacher_notes . "\n\n[تم الموافقة السريع من قبل المعلم في " . now()->toDateTimeString() . "]",
                        ]);
                    }),

                Tables\Actions\Action::make('schedule_session')
                    ->label('جدولة الجلسة')
                    ->icon('heroicon-m-calendar')
                    ->color('info')
                    ->visible(fn (QuranTrialRequest $record): bool => in_array($record->status, ['pending', 'approved']))
                    ->form([
                        DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة التجريبية')
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (QuranTrialRequest $record, array $data) {
                        $record->update([
                            'status' => 'scheduled',
                            'scheduled_at' => $data['scheduled_at'],
                        ]);
                    }),
            ]),
        ];
    }

    /**
     * Get bulk actions for teachers
     */
    protected static function getTeacherBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('bulk_approve')
                ->label('موافقة جماعية')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->action(function ($records) {
                    foreach ($records as $record) {
                        if ($record->status === 'pending') {
                            $record->update([
                                'status' => 'approved',
                                'teacher_notes' => ($record->teacher_notes ?? '') . "\n\n[تمت الموافقة الجماعية في " . now()->toDateTimeString() . "]",
                            ]);
                        }
                    }
                }),

            Tables\Actions\BulkAction::make('bulk_schedule')
                ->label('جدولة جماعية')
                ->icon('heroicon-m-calendar')
                ->color('info')
                ->form([
                    DateTimePicker::make('scheduled_at')
                        ->label('موعد موحد للجلسات')
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data, $records) {
                    foreach ($records as $record) {
                        if (in_array($record->status, ['pending', 'approved'])) {
                            $record->update([
                                'status' => 'scheduled',
                                'scheduled_at' => $data['scheduled_at'],
                            ]);
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