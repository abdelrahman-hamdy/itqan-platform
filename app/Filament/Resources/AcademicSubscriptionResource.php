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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

class AcademicSubscriptionResource extends Resource
{
    protected static ?string $model = AcademicSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'الاشتراكات الأكاديمية';
    
    protected static ?string $modelLabel = 'اشتراك أكاديمي';
    
    protected static ?string $pluralModelLabel = 'الاشتراكات الأكاديمية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
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
                        
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('السعر بالساعة')
                            ->numeric()
                            ->prefix('ر.س')
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
                                SubscriptionStatus::ACTIVE->value => 'نشط',
                                SubscriptionStatus::PAUSED->value => 'معلق',
                                'suspended' => 'موقوف',
                                SessionStatus::CANCELLED->value => 'ملغي',
                                SubscriptionStatus::EXPIRED->value => 'منتهي',
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
                    ->label('الحالة')
                    ->options([
                        SubscriptionStatus::ACTIVE->value => 'نشط',
                        SubscriptionStatus::PAUSED->value => 'معلق',
                        'suspended' => 'موقوف',
                        SessionStatus::CANCELLED->value => 'ملغي',
                        SubscriptionStatus::EXPIRED->value => 'منتهي',
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'current' => 'محدث',
                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                        'overdue' => 'متأخر',
                        'failed' => 'فشل',
                    ]),
                
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('المادة')
                    ->relationship('subject', 'name')
                    ->searchable(),
                
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->relationship('teacher.user', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'view' => Pages\ViewAcademicSubscription::route('/{record}'),
            'edit' => Pages\EditAcademicSubscription::route('/{record}/edit'),
        ];
    }
}