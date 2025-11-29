<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\CertificateTemplateStyle;
use App\Filament\AcademicTeacher\Resources\AcademicSubscriptionResource\Pages;
use App\Filament\AcademicTeacher\Resources\AcademicSubscriptionResource\RelationManagers;
use App\Models\AcademicSubscription;
use App\Services\CertificateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AcademicSubscriptionResource extends Resource
{
    protected static ?string $model = AcademicSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'الاشتراك الأكاديمي';
    
    protected static ?string $modelLabel = 'اشتراك أكاديمي';
    
    protected static ?string $pluralModelLabel = 'الاشتراكات الأكاديمية';

    protected static ?string $navigationGroup = 'دروسي الفردية';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->required(),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->required(),
                Forms\Components\Select::make('teacher_id')
                    ->relationship('teacher', 'id')
                    ->required(),
                Forms\Components\Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->required(),
                Forms\Components\Select::make('grade_level_id')
                    ->relationship('gradeLevel', 'name')
                    ->required(),
                Forms\Components\Select::make('session_request_id')
                    ->relationship('sessionRequest', 'id'),
                Forms\Components\Select::make('academic_package_id')
                    ->relationship('academicPackage', 'id'),
                Forms\Components\TextInput::make('subscription_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('subscription_type')
                    ->required(),

                Forms\Components\TextInput::make('session_duration_minutes')
                    ->required()
                    ->numeric()
                    ->default(60),
                Forms\Components\TextInput::make('hourly_rate')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('sessions_per_month')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('monthly_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('final_monthly_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('SAR'),
                Forms\Components\TextInput::make('billing_cycle')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date'),
                Forms\Components\DatePicker::make('next_billing_date')
                    ->required(),
                Forms\Components\DatePicker::make('last_payment_date'),
                Forms\Components\TextInput::make('last_payment_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('weekly_schedule')
                    ->required(),
                Forms\Components\TextInput::make('timezone')
                    ->required()
                    ->maxLength(50)
                    ->default('Asia/Riyadh'),
                Forms\Components\Toggle::make('auto_create_google_meet')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('payment_status')
                    ->required(),
                Forms\Components\Toggle::make('has_trial_session')
                    ->required(),
                Forms\Components\Toggle::make('trial_session_used')
                    ->required(),
                Forms\Components\DateTimePicker::make('trial_session_date'),
                Forms\Components\TextInput::make('trial_session_status'),
                Forms\Components\DateTimePicker::make('paused_at'),
                Forms\Components\DateTimePicker::make('resume_date'),
                Forms\Components\Textarea::make('pause_reason')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('pause_days_remaining')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('auto_renewal')
                    ->required(),
                Forms\Components\TextInput::make('renewal_reminder_days')
                    ->required()
                    ->numeric()
                    ->default(7),
                Forms\Components\DateTimePicker::make('last_reminder_sent'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('student_notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('teacher_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_sessions_scheduled')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_sessions_completed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_sessions_missed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('completion_rate')
                    ->required()
                    ->numeric()
                    ->default(0.00),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academy.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessionRequest.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicPackage.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscription_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subscription_type'),

                Tables\Columns\TextColumn::make('session_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_per_month')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_monthly_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('billing_cycle'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_billing_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_payment_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->searchable(),
                Tables\Columns\IconColumn::make('auto_create_google_meet')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('payment_status'),
                Tables\Columns\IconColumn::make('has_trial_session')
                    ->boolean(),
                Tables\Columns\IconColumn::make('trial_session_used')
                    ->boolean(),
                Tables\Columns\TextColumn::make('trial_session_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trial_session_status'),
                Tables\Columns\TextColumn::make('paused_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resume_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pause_days_remaining')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('auto_renewal')
                    ->boolean(),
                Tables\Columns\TextColumn::make('renewal_reminder_days')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_reminder_sent')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_sessions_scheduled')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_sessions_completed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_sessions_missed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل'),

                Tables\Actions\Action::make('issue_certificate')
                    ->label('إصدار شهادة')
                    ->icon('heroicon-o-academic-cap')
                    ->color('warning')
                    ->visible(fn (AcademicSubscription $record): bool =>
                        !$record->certificate_issued && $record->status === 'active'
                    )
                    ->form([
                        Forms\Components\Select::make('template_style')
                            ->label('تصميم الشهادة')
                            ->options(CertificateTemplateStyle::options())
                            ->default('modern')
                            ->required()
                            ->helperText('اختر التصميم المناسب للشهادة'),

                        Forms\Components\Textarea::make('achievement_text')
                            ->label('نص الإنجاز')
                            ->required()
                            ->rows(4)
                            ->minLength(10)
                            ->maxLength(1000)
                            ->placeholder('مثال: لتفوقه في دراسة مادة الرياضيات وإتمامه جميع الدروس بامتياز...')
                            ->helperText('اكتب وصفاً للإنجازات التي حققها الطالب'),
                    ])
                    ->modalHeading('إصدار شهادة للطالب')
                    ->modalDescription(fn (AcademicSubscription $record): string =>
                        "سيتم إصدار شهادة للطالب: {$record->student->name}"
                    )
                    ->modalSubmitActionLabel('إصدار الشهادة')
                    ->action(function (AcademicSubscription $record, array $data): void {
                        try {
                            $certificateService = app(CertificateService::class);
                            $certificate = $certificateService->issueManualCertificate(
                                $record,
                                $data['achievement_text'],
                                $data['template_style'],
                                Auth::id(),
                                $record->teacher?->user_id
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
                    ->visible(fn (AcademicSubscription $record): bool => $record->certificate_issued)
                    ->url(fn (AcademicSubscription $record): ?string =>
                        $record->certificate ? route('student.certificate.view', [
                            'subdomain' => $record->certificate->academy?->subdomain ?? 'itqan-academy',
                            'certificate' => $record->certificate->id,
                        ]) : null
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListAcademicSubscriptions::route('/'),
            'create' => Pages\CreateAcademicSubscription::route('/create'),
            'edit' => Pages\EditAcademicSubscription::route('/{record}/edit'),
        ];
    }
}
