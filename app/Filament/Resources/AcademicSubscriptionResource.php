<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSubscriptionResource\Pages;
use App\Filament\Resources\AcademicSubscriptionResource\RelationManagers;
use App\Models\AcademicSubscription;
use App\Models\AcademicPackage;
use App\Models\AcademicTeacherProfile;
use App\Models\Subject;
use App\Models\AcademicGradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSubscriptionResource extends Resource
{
    protected static ?string $model = AcademicSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'الاشتراكات الأكاديمية';
    
    protected static ?string $modelLabel = 'اشتراك أكاديمي';
    
    protected static ?string $pluralModelLabel = 'الاشتراكات الأكاديمية';
    
    protected static ?string $navigationGroup = 'الإدارة الأكاديمية';
    
    protected static ?int $navigationSort = 1;

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
                            ->min(30)
                            ->max(120)
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
                                'active' => 'نشط',
                                'paused' => 'معلق',
                                'suspended' => 'موقوف',
                                'cancelled' => 'ملغي',
                                'expired' => 'منتهي',
                            ])
                            ->default('active')
                            ->required(),
                        
                        Forms\Components\Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->options([
                                'current' => 'محدث',
                                'pending' => 'في الانتظار',
                                'overdue' => 'متأخر',
                                'failed' => 'فشل',
                            ])
                            ->default('pending')
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
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => ['cancelled', 'suspended'],
                        'secondary' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'paused' => 'معلق',
                        'suspended' => 'موقوف',
                        'cancelled' => 'ملغي',
                        'expired' => 'منتهي',
                        default => $state,
                    }),
                
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->colors([
                        'success' => 'current',
                        'warning' => 'pending',
                        'danger' => ['overdue', 'failed'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'current' => 'محدث',
                        'pending' => 'في الانتظار',
                        'overdue' => 'متأخر',
                        'failed' => 'فشل',
                        default => $state,
                    }),
                
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
                        'active' => 'نشط',
                        'paused' => 'معلق',
                        'suspended' => 'موقوف',
                        'cancelled' => 'ملغي',
                        'expired' => 'منتهي',
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'current' => 'محدث',
                        'pending' => 'في الانتظار',
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