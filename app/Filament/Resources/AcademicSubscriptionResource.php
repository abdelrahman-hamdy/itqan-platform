<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSubscriptionResource\Pages;
use App\Filament\Resources\AcademicSubscriptionResource\RelationManagers;
use App\Models\AcademicSubscription;
use App\Models\AcademicPackage;
use App\Models\AcademicTeacherProfile;
use App\Models\AcademicGradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\SubscriptionStatus;

class AcademicSubscriptionResource extends BaseResource
{
    protected static ?string $model = AcademicSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'الاشتراكات الأكاديمية';
    
    protected static ?string $modelLabel = 'اشتراك أكاديمي';
    
    protected static ?string $pluralModelLabel = 'الاشتراكات الأكاديمية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 2;

    /**
     * Get the navigation badge showing pending subscriptions count
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', SubscriptionStatus::PENDING->value)->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', SubscriptionStatus::PENDING->value)->count() > 0 ? 'warning' : null;
    }

    /**
     * Get the navigation badge tooltip
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tabs.pending');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student', 'teacher.user', 'subject', 'gradeLevel', 'academy']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الاشتراك الأساسية')
                    ->schema([
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->label('الأكاديمية')
                            ->required()
                            ->disabled()
                            ->default(fn () => auth()->user()->academy_id),
                        
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('teacher_id')
                            ->relationship('teacher', 'user_id')
                            ->label('المعلم')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        
                        Forms\Components\Select::make('subject_id')
                            ->label('المادة الدراسية')
                            ->required()
                            ->searchable()
                            ->options(function (callable $get) {
                                $teacherId = $get('teacher_id');
                                if (!$teacherId) {
                                    return [];
                                }
                                
                                $teacher = \App\Models\AcademicTeacherProfile::find($teacherId);
                                if (!$teacher) {
                                    return [];
                                }
                                
                                return $teacher->subjects()->pluck('name', 'id')->toArray();
                            })
                            ->reactive(),
                        
                        Forms\Components\Select::make('grade_level_id')
                            ->label('المرحلة الدراسية')
                            ->required()
                            ->searchable()
                            ->options(function (callable $get) {
                                $teacherId = $get('teacher_id');
                                if (!$teacherId) {
                                    return [];
                                }
                                
                                $teacher = \App\Models\AcademicTeacherProfile::find($teacherId);
                                if (!$teacher) {
                                    return [];
                                }
                                
                                return $teacher->gradeLevels()->pluck('name', 'id')->toArray();
                            })
                            ->reactive(),
                        
                        Forms\Components\Select::make('academic_package_id')
                            ->relationship('academicPackage', 'name_ar')
                            ->label('الباقة الأكاديمية')
                            ->searchable(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الاشتراك')
                    ->schema([
                        Forms\Components\TextInput::make('subscription_code')
                            ->label('رمز الاشتراك')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Select::make('subscription_type')
                            ->label('نوع الاشتراك')
                            ->options([
                                'private' => 'خصوصي',
                                'group' => 'مجموعة',
                            ])
                            ->default('private')
                            ->required(),
                        

                        
                        Forms\Components\TextInput::make('session_duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(120)
                            ->default(60)
                            ->required(),

                        Forms\Components\Select::make('billing_cycle')
                            ->label('دورة الفوترة')
                            ->options([
                                'monthly' => 'شهرياً',
                                'quarterly' => 'كل 3 شهور',
                                'yearly' => 'سنوياً',
                            ])
                            ->default('monthly')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('التواريخ والدفع')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required(),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ الانتهاء'),
                        
                        Forms\Components\DatePicker::make('next_billing_date')
                            ->label('تاريخ الفوترة التالي'),
                        
                        Forms\Components\Select::make('status')
                            ->label('حالة الاشتراك')
                            ->options([
                                SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                                SubscriptionStatus::TRIAL->value => 'تجريبي',
                                SubscriptionStatus::ACTIVE->value => 'نشط',
                                SubscriptionStatus::PAUSED->value => 'معلق',
                                SubscriptionStatus::SUSPENDED->value => 'موقوف',
                                SubscriptionStatus::EXPIRED->value => 'منتهي',
                                SubscriptionStatus::CANCELLED->value => 'ملغي',
                                SubscriptionStatus::COMPLETED->value => 'مكتمل',
                            ])
                            ->default(SubscriptionStatus::ACTIVE->value)
                            ->required(),
                        
                        Forms\Components\Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->options([
                                'current' => 'محدث',
                                SubscriptionStatus::PENDING->value => 'في الانتظار',
                                'overdue' => 'متأخر',
                                'failed' => 'فشل',
                            ])
                            ->default(SubscriptionStatus::PENDING->value)
                            ->required(),
                        
                        Forms\Components\Toggle::make('auto_renewal')
                            ->label('التجديد التلقائي')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->rows(3),
                        
                        Forms\Components\Textarea::make('student_notes')
                            ->label('ملاحظات الطالب')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(),

                Tables\Columns\TextColumn::make('subscription_code')
                    ->label('رمز الاشتراك')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('teacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المرحلة')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('final_monthly_amount')
                    ->label('المبلغ الشهري')
                    ->money('SAR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SubscriptionStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof \App\Enums\SubscriptionStatus ? $state->color() : 'secondary'),
                
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->color() : 'secondary'),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('next_billing_date')
                    ->label('الفوترة التالية')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.status'))
                    ->options(SubscriptionStatus::options()),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('filament.payment_status'))
                    ->options([
                        'current' => __('filament.subscription.payment_current'),
                        SubscriptionStatus::PENDING->value => __('filament.tabs.pending'),
                        'overdue' => __('filament.subscription.payment_overdue'),
                        'failed' => __('filament.tabs.failed'),
                    ]),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('filament.course.subject'))
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label(__('filament.teacher'))
                    ->relationship('teacher.user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filament.filters.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filament.filters.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('filament.filters.from_date') . ': ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('filament.filters.to_date') . ': ' . $data['until'];
                        }
                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
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
            'view' => Pages\ViewAcademicSubscription::route('/{record}'),
            'edit' => Pages\EditAcademicSubscription::route('/{record}/edit'),
        ];
    }
}