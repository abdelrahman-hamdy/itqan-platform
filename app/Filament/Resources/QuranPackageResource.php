<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranPackageResource\Pages;
use App\Models\QuranPackage;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use App\Services\AcademyContextService;
use App\Traits\ScopedToAcademy;

class QuranPackageResource extends BaseResource
{
    use ScopedToAcademy;
    protected static ?string $model = QuranPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'باقات القرآن';

    protected static ?string $modelLabel = 'باقة قرآن';

    protected static ?string $pluralModelLabel = 'باقات القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 1;
public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الباقة الأساسية')
                    ->description('معلومات الباقة باللغتين العربية والإنجليزية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم الباقة (عربي)')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم الباقة (إنجليزي)')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('description_ar')
                                    ->label('وصف الباقة (عربي)')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('description_en')
                                    ->label('وصف الباقة (إنجليزي)')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الحصص')
                    ->description('تكوين عدد ومدة الحصص الشهرية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('sessions_per_month')
                                    ->label('عدد الحصص في الشهر')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(30)
                                    ->default(8),

                                Forms\Components\TextInput::make('session_duration_minutes')
                                    ->label('مدة الحصة (دقيقة)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(15)
                                    ->maxValue(120)
                                    ->default(45),

                                Forms\Components\Select::make('currency')
                                    ->label('العملة')
                                    ->options([
                                        'SAR' => 'ريال سعودي (SAR)',
                                        'AED' => 'درهم إماراتي (AED)',
                                        'KWD' => 'دينار كويتي (KWD)',
                                        'QAR' => 'ريال قطري (QAR)',
                                        'BHD' => 'دينار بحريني (BHD)',
                                        'OMR' => 'ريال عماني (OMR)',
                                        'USD' => 'دولار أمريكي (USD)',
                                        'EUR' => 'يورو (EUR)',
                                    ])
                                    ->default('SAR')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('الأسعار')
                    ->description('أسعار الباقة لدورات الفوترة المختلفة')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('monthly_price')
                                    ->label('السعر الشهري')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('ر.س'),

                                Forms\Components\TextInput::make('quarterly_price')
                                    ->label('السعر ربع السنوي (3 أشهر)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('ر.س'),

                                Forms\Components\TextInput::make('yearly_price')
                                    ->label('السعر السنوي')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('ر.س'),
                            ]),
                    ]),

                Forms\Components\Section::make('مميزات الباقة')
                    ->description('المميزات والخدمات المتضمنة في الباقة')
                    ->schema([
                        Forms\Components\TagsInput::make('features')
                            ->label('مميزات الباقة')
                            ->placeholder('اضغط Enter لإضافة ميزة جديدة')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('إعدادات عامة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('الباقة مفعلة')
                                    ->default(true)
                                    ->helperText('يمكن للطلاب الاشتراك في الباقات المفعلة فقط'),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('ترتيب العرض')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('ترتيب ظهور الباقة في القائمة (0 = الأولى)'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sessions_per_month')
                    ->label('عدد الحصص/شهر')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('session_duration_minutes')
                    ->label('مدة الحصة')
                    ->formatStateUsing(fn (string $state): string => $state . ' دقيقة')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('monthly_price')
                    ->label('السعر الشهري')
                    ->money('SAR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('quarterly_price')
                    ->label('السعر ربع السنوي')
                    ->money('SAR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('yearly_price')
                    ->label('السعر السنوي')
                    ->money('SAR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('عدد المشتركين')
                    ->counts('subscriptions')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('حالة الباقة'),
                
                Tables\Filters\SelectFilter::make('currency')
                    ->label('العملة')
                    ->options([
                        'SAR' => 'ريال سعودي',
                        'AED' => 'درهم إماراتي',
                        'USD' => 'دولار أمريكي',
                    ]),
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
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('معلومات الباقة')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('name_ar')
                                    ->label('اسم الباقة (عربي)'),
                                    
                                Components\TextEntry::make('name_en')
                                    ->label('اسم الباقة (إنجليزي)'),
                            ]),

                        Components\TextEntry::make('description_ar')
                            ->label('الوصف (عربي)')
                            ->columnSpanFull(),

                        Components\TextEntry::make('description_en')
                            ->label('الوصف (إنجليزي)')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('تفاصيل الحصص')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('sessions_per_month')
                                    ->label('عدد الحصص في الشهر'),

                                Components\TextEntry::make('session_duration_minutes')
                                    ->label('مدة الحصة')
                                    ->formatStateUsing(fn (string $state): string => $state . ' دقيقة'),

                                Components\TextEntry::make('currency')
                                    ->label('العملة'),
                            ]),
                    ]),

                Components\Section::make('الأسعار')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('monthly_price')
                                    ->label('السعر الشهري')
                                    ->money('SAR'),

                                Components\TextEntry::make('quarterly_price')
                                    ->label('السعر ربع السنوي')
                                    ->money('SAR'),

                                Components\TextEntry::make('yearly_price')
                                    ->label('السعر السنوي')
                                    ->money('SAR'),
                            ]),
                    ]),

                Components\Section::make('مميزات الباقة')
                    ->schema([
                        Components\RepeatableEntry::make('features')
                            ->label('المميزات')
                            ->schema([
                                Components\TextEntry::make('')
                                    ->hiddenLabel(),
                            ]),
                    ]),

                Components\Section::make('إحصائيات')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('subscriptions_count')
                                    ->label('عدد المشتركين'),

                                Components\IconEntry::make('is_active')
                                    ->label('الحالة')
                                    ->boolean(),

                                Components\TextEntry::make('sort_order')
                                    ->label('ترتيب العرض'),
                            ]),
                    ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
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
            'index' => Pages\ListQuranPackages::route('/'),
            'create' => Pages\CreateQuranPackage::route('/create'),
            'view' => Pages\ViewQuranPackage::route('/{record}'),
            'edit' => Pages\EditQuranPackage::route('/{record}/edit'),
        ];
    }
} 