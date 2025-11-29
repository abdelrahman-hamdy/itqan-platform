<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\CertificateTemplateStyle;
use App\Filament\Teacher\Resources\QuranSubscriptionResource\Pages;
use App\Filament\Teacher\Resources\QuranSessionResource;
use App\Models\QuranSubscription;
use App\Services\CertificateService;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Teacher\Resources\BaseTeacherResource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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

class QuranSubscriptionResource extends BaseTeacherResource
{
    protected static ?string $model = QuranSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'اشتراكات طلابي';

    protected static ?string $modelLabel = 'اشتراك طالب';

    protected static ?string $pluralModelLabel = 'اشتراكات الطلاب';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 2;

    /**
     * Check if current user can view this record
     * Teachers can only view subscriptions assigned to them
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return false;
        }

        // Allow viewing if subscription belongs to current teacher
        return $record->quran_teacher_id === $user->quranTeacherProfile->id;
    }

    /**
     * Check if current user can edit this record
     * Teachers have limited editing capabilities
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return false;
        }

        // Allow editing if subscription belongs to current teacher
        // but restrict certain fields in the form
        return $record->quran_teacher_id === $user->quranTeacherProfile->id;
    }

    /**
     * Get the Eloquent query with teacher-specific filtering
     * Only show subscriptions for the current teacher
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query
            ->where('quran_teacher_id', $user->quranTeacherProfile->id)
            ->where('academy_id', $user->academy_id);
    }

    /**
     * Teachers cannot create new subscriptions
     * This is managed by admin or academy staff
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
     * Teachers have restricted editing capabilities
     */
    protected static function getTeacherFormSchema(): array
    {
        return [
            Section::make('معلومات الاشتراك')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('subscription_code')
                                ->label('رمز الاشتراك')
                                ->disabled()
                                ->dehydrated(false), // Don't save this field
                            
                            Select::make('subscription_status')
                                ->label('حالة الاشتراك')
                                ->options([
                                    'active' => 'نشط',
                                    'paused' => 'متوقف مؤقت',
                                    'cancelled' => 'ملغي',
                                    'expired' => 'منتهي الصلاحية',
                                ])
                                ->required()
                                ->helperText('يمكن للمعلم تعديل حالة الاشتراك'),
                            
                            TextInput::make('total_sessions')
                                ->label('إجمالي الجلسات')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('sessions_used')
                                ->label('الجلسات المستخدمة')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('sessions_remaining')
                                ->label('الجلسات المتبقية')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('progress_percentage')
                                ->label('نسبة التقدم')
                                ->suffix('%')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),
            
            Section::make('تفاصيل الطالب والمعلم')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('student.first_name')
                                ->label('اسم الطالب')
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('student.last_name')
                                ->label('姓名 الطالب')
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('quran_teacher.first_name')
                                ->label('اسم المعلم')
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('quran_package.name')
                                ->label('اسم الباقة')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),
            
            Section::make('التواريخ والمدة')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            DateTimePicker::make('start_date')
                                ->label('تاريخ البداية')
                                ->disabled()
                                ->dehydrated(false),
                            
                            DateTimePicker::make('end_date')
                                ->label('تاريخ النهاية')
                                ->disabled()
                                ->dehydrated(false),
                            
                            DateTimePicker::make('next_session_date')
                                ->label('تاريخ الجلسة القادمة')
                                ->helperText('يمكن للمعلم تحديد موعد الجلسة القادمة')
                                ->native(false),
                            
                            TextInput::make('notes')
                                ->label('ملاحظات')
                                ->helperText('ملاحظات المعلم حول هذا الاشتراك')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
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
            TextColumn::make('student.first_name')
                ->label('اسم الطالب')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('subscription_code')
                ->label('رمز الاشتراك')
                ->searchable()
                ->copyable(),
            
            BadgeColumn::make('subscription_status')
                ->label('الحالة')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'active' => 'نشط',
                    'paused' => 'متوقف',
                    'cancelled' => 'ملغي',
                    'expired' => 'منتهي',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'paused' => 'warning',
                    'cancelled' => 'danger',
                    'expired' => 'gray',
                    default => 'gray',
                }),
            
            TextColumn::make('sessions_remaining')
                ->label('الجلسات المتبقية')
                ->formatStateUsing(fn (?int $state): string => $state ? (string) $state : 'غير محدد'),
            
            TextColumn::make('progress_percentage')
                ->label('نسبة التقدم')
                ->suffix('%')
                ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) : '0'),
            
            TextColumn::make('next_session_date')
                ->label('الجلسة القادمة')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
            
            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
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
            SelectFilter::make('subscription_status')
                ->label('حالة الاشتراك')
                ->options([
                    'active' => 'نشط',
                    'paused' => 'متوقف',
                    'cancelled' => 'ملغي',
                    'expired' => 'منتهي',
                ]),
            
            Filter::make('has_sessions_remaining')
                ->label('لديه جلسات متبقية')
                ->query(fn (Builder $query): Builder => 
                    $query->where('sessions_remaining', '>', 0)
                ),
            
            Filter::make('needs_attention')
                ->label('يحتاج متابعة')
                ->query(fn (Builder $query): Builder => 
                    $query->where('subscription_status', 'active')
                          ->where('sessions_remaining', '<=', 3)
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
                            'subscription_status' => $data['subscription_status'] ?? null,
                            'next_session_date' => $data['next_session_date'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ];
                    }),

                Tables\Actions\Action::make('view_sessions')
                    ->label('عرض الجلسات')
                    ->icon('heroicon-m-calendar')
                    ->url(fn (QuranSubscription $record): string =>
                        QuranSessionResource::getUrl('index', ['tableFilters[subscription][value]' => $record->id])
                    ),

                Tables\Actions\Action::make('issue_certificate')
                    ->label('إصدار شهادة')
                    ->icon('heroicon-o-academic-cap')
                    ->color('warning')
                    ->visible(fn (QuranSubscription $record): bool =>
                        !$record->certificate_issued && $record->subscription_status === 'active'
                    )
                    ->form([
                        Select::make('template_style')
                            ->label('تصميم الشهادة')
                            ->options(CertificateTemplateStyle::options())
                            ->default('modern')
                            ->required()
                            ->helperText('اختر التصميم المناسب للشهادة'),

                        Textarea::make('achievement_text')
                            ->label('نص الإنجاز')
                            ->required()
                            ->rows(4)
                            ->minLength(10)
                            ->maxLength(1000)
                            ->placeholder('مثال: لإتمامه حفظ جزء عم بإتقان، وتميزه في أحكام التلاوة والتجويد...')
                            ->helperText('اكتب وصفاً للإنجازات التي حققها الطالب'),
                    ])
                    ->modalHeading('إصدار شهادة للطالب')
                    ->modalDescription(fn (QuranSubscription $record): string =>
                        "سيتم إصدار شهادة للطالب: {$record->student->name}"
                    )
                    ->modalSubmitActionLabel('إصدار الشهادة')
                    ->action(function (QuranSubscription $record, array $data): void {
                        try {
                            $certificateService = app(CertificateService::class);
                            $certificate = $certificateService->issueManualCertificate(
                                $record,
                                $data['achievement_text'],
                                $data['template_style'],
                                Auth::id(),
                                Auth::user()->quranTeacherProfile?->user_id
                            );

                            Notification::make()
                                ->success()
                                ->title('تم إصدار الشهادة بنجاح')
                                ->body("رقم الشهادة: {$certificate->certificate_number}")
                                ->persistent()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ في إصدار الشهادة')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('view_certificate')
                    ->label('عرض الشهادة')
                    ->icon('heroicon-o-document')
                    ->color('success')
                    ->visible(fn (QuranSubscription $record): bool => $record->certificate_issued)
                    ->url(fn (QuranSubscription $record): ?string =>
                        $record->certificate ? route('student.certificate.view', [
                            'subdomain' => $record->certificate->academy?->subdomain ?? 'itqan-academy',
                            'certificate' => $record->certificate->id,
                        ]) : null
                    )
                    ->openUrlInNewTab(),
            ]),
        ];
    }

    /**
     * Get bulk actions for teachers
     */
    protected static function getTeacherBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('update_status')
                ->label('تحديث الحالة')
                ->icon('heroicon-m-pencil-square')
                ->form([
                    Select::make('subscription_status')
                        ->label('الحالة الجديدة')
                        ->options([
                            'active' => 'نشط',
                            'paused' => 'متوقف مؤقت',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, $records) {
                    foreach ($records as $record) {
                        $record->update([
                            'subscription_status' => $data['subscription_status'],
                        ]);
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
            'index' => Pages\ListQuranSubscriptions::route('/'),
            'view' => Pages\ViewQuranSubscription::route('/{record}'),
            'edit' => Pages\EditQuranSubscription::route('/{record}/edit'),
        ];
    }
}