<?php

namespace App\Filament\Resources\InteractiveCourseResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Actions\CreateAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Enums\EnrollmentStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\InteractiveCourseEnrollment;
use App\Models\StudentProfile;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'الطلاب المسجلون';

    protected static ?string $modelLabel = 'تسجيل';

    protected static ?string $pluralModelLabel = 'التسجيلات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات التسجيل')
                    ->schema([
                        Select::make('student_id')
                            ->label('الطالب')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                // StudentProfile doesn't have academy_id directly
                                // Filter via gradeLevel relationship or user relationship
                                return StudentProfile::query()
                                    ->whereHas('gradeLevel', fn ($q) => $q->where('academy_id', $academyId))
                                    ->orWhereHas('user', fn ($q) => $q->where('academy_id', $academyId))
                                    ->get()
                                    ->mapWithKeys(fn ($student) => [
                                        $student->id => $student->full_name.' ('.($student->student_code ?? 'N/A').')',
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabledOn('edit'),

                        DatePicker::make('enrollment_date')
                            ->label('تاريخ التسجيل')
                            ->default(now())
                            ->required(),

                        Select::make('enrollment_status')
                            ->label('حالة التسجيل')
                            ->options(EnrollmentStatus::options())
                            ->default(EnrollmentStatus::PENDING->value)
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('معلومات الدفع')
                    ->schema([
                        Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->options(SubscriptionPaymentStatus::options())
                            ->default(SubscriptionPaymentStatus::PENDING->value)
                            ->required(),

                        TextInput::make('payment_amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix(getCurrencyCode())
                            ->default(fn () => $this->getOwnerRecord()->student_price ?? 0),

                        TextInput::make('discount_applied')
                            ->label('الخصم')
                            ->numeric()
                            ->prefix(getCurrencyCode())
                            ->default(0),
                    ])
                    ->columns(3),

                Section::make('التقدم الأكاديمي')
                    ->schema([
                        TextInput::make('attendance_count')
                            ->label('الحضور')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        TextInput::make('total_possible_attendance')
                            ->label('إجمالي الجلسات')
                            ->numeric()
                            ->default(fn () => $this->getOwnerRecord()->total_sessions ?? 0)
                            ->disabled(),

                        TextInput::make('completion_percentage')
                            ->label('نسبة الإتمام')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100),

                        TextInput::make('final_grade')
                            ->label('الدرجة النهائية')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn ($get) => $get('enrollment_status') === EnrollmentStatus::COMPLETED->value),
                    ])
                    ->columns(4),

                Section::make('الشهادات والملاحظات')
                    ->schema([
                        Toggle::make('certificate_issued')
                            ->label('تم إصدار الشهادة')
                            ->default(false),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'enrolledBy']))
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.student_code')
                    ->label('رمز الطالب')
                    ->fontFamily('mono')
                    ->searchable(),

                TextColumn::make('enrollment_date')
                    ->label('تاريخ التسجيل')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('enrollment_status')
                    ->badge()
                    ->label('حالة التسجيل')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof EnrollmentStatus) {
                            return $state->label();
                        }
                        $status = EnrollmentStatus::tryFrom($state);

                        return $status?->label() ?? $state;
                    })
                    ->colors([
                        'warning' => fn ($state) => $state === EnrollmentStatus::PENDING || $state === EnrollmentStatus::PENDING->value,
                        'success' => fn ($state) => $state === EnrollmentStatus::ENROLLED || $state === EnrollmentStatus::ENROLLED->value,
                        'primary' => fn ($state) => $state === EnrollmentStatus::COMPLETED || $state === EnrollmentStatus::COMPLETED->value,
                        'danger' => fn ($state) => $state === EnrollmentStatus::CANCELLED || $state === EnrollmentStatus::CANCELLED->value,
                    ]),

                TextColumn::make('payment_status')
                    ->badge()
                    ->label('حالة الدفع')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof SubscriptionPaymentStatus) {
                            return $state->label();
                        }
                        $status = SubscriptionPaymentStatus::tryFrom($state);

                        return $status?->label() ?? $state;
                    })
                    ->colors([
                        'warning' => fn ($state) => $state === SubscriptionPaymentStatus::PENDING || $state === SubscriptionPaymentStatus::PENDING->value,
                        'success' => fn ($state) => $state === SubscriptionPaymentStatus::PAID || $state === SubscriptionPaymentStatus::PAID->value,
                        'danger' => fn ($state) => $state === SubscriptionPaymentStatus::FAILED || $state === SubscriptionPaymentStatus::FAILED->value,
                    ]),

                TextColumn::make('payment_amount')
                    ->label('المبلغ')
                    ->money(fn ($record) => $record->course?->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                    ->sortable(),

                TextColumn::make('attendance_display')
                    ->label('الحضور')
                    ->getStateUsing(fn ($record) => $record->attendance_count.'/'.$record->total_possible_attendance),

                TextColumn::make('completion_percentage')
                    ->label('الإتمام')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('final_grade')
                    ->label('الدرجة')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('certificate_issued')
                    ->label('الشهادة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('enrolledBy.name')
                    ->label('مسجل بواسطة')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('enrollment_status')
                    ->label('حالة التسجيل')
                    ->options(EnrollmentStatus::options()),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(SubscriptionPaymentStatus::options()),

                TernaryFilter::make('certificate_issued')
                    ->label('الشهادة')
                    ->placeholder('الكل')
                    ->trueLabel('صدرت')
                    ->falseLabel('لم تصدر'),
            ])
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->headerActions([
                CreateAction::make()
                    ->label('تسجيل طالب')
                    ->mutateDataUsing(function (array $data): array {
                        $data['academy_id'] = AcademyContextService::getCurrentAcademyId();
                        $data['enrolled_by'] = auth()->id();
                        $data['total_possible_attendance'] = $this->getOwnerRecord()->total_sessions ?? 0;

                        return $data;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('تعديل'),

                    Action::make('activate')
                        ->label('تفعيل التسجيل')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تفعيل التسجيل')
                        ->modalDescription('هل أنت متأكد من تفعيل تسجيل هذا الطالب؟')
                        ->action(fn (InteractiveCourseEnrollment $record) => $record->update([
                            'enrollment_status' => EnrollmentStatus::ENROLLED,
                        ]))
                        ->visible(fn (InteractiveCourseEnrollment $record): bool => $record->enrollment_status === EnrollmentStatus::PENDING &&
                            $record->payment_status === SubscriptionPaymentStatus::PAID
                        ),

                    Action::make('mark_paid')
                        ->label('تأكيد الدفع')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد الدفع')
                        ->modalDescription('هل أنت متأكد من تأكيد دفع هذا الطالب؟')
                        ->action(fn (InteractiveCourseEnrollment $record) => $record->update([
                            'payment_status' => SubscriptionPaymentStatus::PAID,
                        ]))
                        ->visible(fn (InteractiveCourseEnrollment $record): bool => $record->payment_status !== SubscriptionPaymentStatus::PAID
                        ),

                    Action::make('complete')
                        ->label('إتمام الدورة')
                        ->icon('heroicon-o-trophy')
                        ->color('primary')
                        ->schema([
                            TextInput::make('final_grade')
                                ->label('الدرجة النهائية')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->required(),
                        ])
                        ->action(function (InteractiveCourseEnrollment $record, array $data) {
                            $record->update([
                                'enrollment_status' => EnrollmentStatus::COMPLETED,
                                'final_grade' => $data['final_grade'],
                                'completion_percentage' => 100,
                            ]);
                        })
                        ->visible(fn (InteractiveCourseEnrollment $record): bool => $record->enrollment_status === EnrollmentStatus::ENROLLED
                        ),

                    Action::make('issue_certificate')
                        ->label('إصدار شهادة')
                        ->icon('heroicon-o-document-check')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('إصدار شهادة')
                        ->modalDescription('هل أنت متأكد من إصدار شهادة لهذا الطالب؟')
                        ->action(fn (InteractiveCourseEnrollment $record) => $record->update([
                            'certificate_issued' => true,
                        ]))
                        ->visible(fn (InteractiveCourseEnrollment $record): bool => $record->enrollment_status === EnrollmentStatus::COMPLETED &&
                            $record->final_grade !== null &&
                            ! $record->certificate_issued
                        ),

                    Action::make('cancel')
                        ->label('إلغاء التسجيل')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('إلغاء التسجيل')
                        ->modalDescription('هل أنت متأكد من إلغاء تسجيل هذا الطالب؟ لا يمكن التراجع عن هذا الإجراء.')
                        ->action(fn (InteractiveCourseEnrollment $record) => $record->update([
                            'enrollment_status' => EnrollmentStatus::CANCELLED,
                        ]))
                        ->visible(fn (InteractiveCourseEnrollment $record): bool => $record->enrollment_status->canCancel()
                        ),

                    DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('enrollment_date', 'desc');
    }
}
