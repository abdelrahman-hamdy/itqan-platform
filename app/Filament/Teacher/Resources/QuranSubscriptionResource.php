<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuranSubscriptionResource\Pages;
use App\Filament\Teacher\Resources\QuranSessionResource;
use App\Models\QuranSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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

class QuranSubscriptionResource extends Resource
{
    protected static ?string $model = QuranSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'اشتراكات طلابي';

    protected static ?string $modelLabel = 'اشتراك طالب';

    protected static ?string $pluralModelLabel = 'اشتراكات الطلاب';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 2;

    // Scope to only the current teacher's subscriptions
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->where('quran_teacher_id', $user->quranTeacherProfile->id)
            ->where('academy_id', $user->academy_id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الاشتراك')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('subscription_code')
                                    ->label('رمز الاشتراك')
                                    ->disabled(),
                                
                                Select::make('subscription_status')
                                    ->label('حالة الاشتراك')
                                    ->options([
                                        'active' => 'نشط',
                                        'paused' => 'متوقف مؤقت',
                                        'cancelled' => 'ملغي',
                                        'expired' => 'منتهي الصلاحية',
                                    ])
                                    ->required(),
                                
                                TextInput::make('total_sessions')
                                    ->label('إجمالي الجلسات')
                                    ->numeric()
                                    ->disabled(),
                                
                                TextInput::make('sessions_used')
                                    ->label('الجلسات المستخدمة')
                                    ->numeric()
                                    ->disabled(),
                                
                                TextInput::make('sessions_remaining')
                                    ->label('الجلسات المتبقية')
                                    ->numeric()
                                    ->disabled(),
                                
                                TextInput::make('progress_percentage')
                                    ->label('نسبة التقدم')
                                    ->suffix('%')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                            ]),
                    ]),

                Section::make('التقدم في الحفظ')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('current_surah')
                                    ->label('السورة الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(114),
                                
                                TextInput::make('current_verse')
                                    ->label('الآية الحالية')
                                    ->numeric()
                                    ->minValue(1),
                                
                                TextInput::make('verses_memorized')
                                    ->label('الآيات المحفوظة')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                            
                        Select::make('memorization_level')
                            ->label('مستوى الحفظ')
                            ->options([
                                'beginner' => 'مبتدئ',
                                'intermediate' => 'متوسط',
                                'advanced' => 'متقدم',
                                'hafez' => 'حافظ',
                            ]),
                    ]),

                Section::make('الملاحظات والتقييم')
                    ->schema([
                        Textarea::make('notes')
                            ->label('ملاحظات المعلم')
                            ->rows(4),
                            
                        Grid::make(2)
                            ->schema([
                                Select::make('rating')
                                    ->label('تقييم الطالب')
                                    ->options([
                                        1 => '1 - ضعيف',
                                        2 => '2 - مقبول',
                                        3 => '3 - جيد',
                                        4 => '4 - جيد جداً',
                                        5 => '5 - ممتاز',
                                    ]),
                                    
                                DateTimePicker::make('last_session_at')
                                    ->label('آخر جلسة')
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription_code')
                    ->label('رمز الاشتراك')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('student.name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('package.name_ar')
                    ->label('الباقة')
                    ->searchable(),
                    
                BadgeColumn::make('subscription_status')
                    ->label('حالة الاشتراك')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => 'cancelled',
                        'gray' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'paused' => 'متوقف مؤقت',
                        'cancelled' => 'ملغي',
                        'expired' => 'منتهي الصلاحية',
                        default => $state,
                    }),
                    
                BadgeColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->colors([
                        'success' => 'current',
                        'warning' => 'overdue',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'current' => 'مدفوع',
                        'overdue' => 'متأخر',
                        'failed' => 'فشل',
                        default => $state,
                    }),
                    
                TextColumn::make('sessions_used')
                    ->label('الجلسات المستخدمة')
                    ->sortable(),
                    
                TextColumn::make('sessions_remaining')
                    ->label('الجلسات المتبقية')
                    ->sortable()
                    ->color(fn ($record) => $record->sessions_remaining <= 3 ? 'danger' : 'primary'),
                    
                TextColumn::make('progress_percentage')
                    ->label('التقدم')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($record) => $record->progress_percentage >= 75 ? 'success' : 
                             ($record->progress_percentage >= 50 ? 'warning' : 'danger')),
                    
                TextColumn::make('current_surah')
                    ->label('السورة الحالية')
                    ->sortable(),
                    
                TextColumn::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'hafez' => 'حافظ',
                        null => 'غير محدد',
                        default => $state,
                    }),
                    
                TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                    
                TextColumn::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->date('Y-m-d')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : 'primary'),
                    
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_status')
                    ->label('حالة الاشتراك')
                    ->options([
                        'active' => 'نشط',
                        'paused' => 'متوقف مؤقت',
                        'cancelled' => 'ملغي',
                        'expired' => 'منتهي الصلاحية',
                    ]),
                    
                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'current' => 'مدفوع',
                        'overdue' => 'متأخر',
                        'failed' => 'فشل',
                    ]),
                    
                SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'hafez' => 'حافظ',
                    ]),
                    
                Filter::make('low_sessions')
                    ->label('جلسات قليلة (أقل من 5)')
                    ->query(fn (Builder $query): Builder => $query->where('sessions_remaining', '<', 5)),
                    
                Filter::make('expiring_soon')
                    ->label('تنتهي قريباً (خلال 30 يوم)')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<=', now()->addDays(30))),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('add_session')
                        ->label('إضافة جلسة')
                        ->icon('heroicon-o-plus')
                        ->color('success')
                        ->url(fn (QuranSubscription $record): string => 
                            QuranSessionResource::getUrl('create', [
                                'tenant' => filament()->getTenant(),
                                'quran_subscription_id' => $record->id,
                                'student_id' => $record->student_id,
                            ])
                        ),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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