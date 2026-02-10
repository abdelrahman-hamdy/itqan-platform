<?php

namespace App\Filament\Resources;

use App\Enums\SessionDuration;
use App\Filament\Resources\AcademicPackageResource\Pages;
use App\Models\AcademicPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicPackageResource extends BaseResource
{
    protected static ?string $model = AcademicPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'باقات أكاديمية';

    protected static ?string $modelLabel = 'باقة أكاديمية';

    protected static ?string $pluralModelLabel = 'باقات أكاديمية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الباقة الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الباقة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الباقة')
                            ->rows(3)
                            ->columnSpanFull(),

                    ]),

                Forms\Components\Section::make('إعدادات الحصص')
                    ->description('تكوين عدد ومدة الحصص الشهرية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sessions_per_month')
                                    ->label('عدد الحصص في الشهر')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(30)
                                    ->default(8),

                                Forms\Components\Select::make('session_duration_minutes')
                                    ->label('مدة الحصة (دقيقة)')
                                    ->options(SessionDuration::options())
                                    ->default(60)
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
                                    ->prefix(getCurrencySymbol()),

                                Forms\Components\TextInput::make('quarterly_price')
                                    ->label('السعر ربع السنوي (3 أشهر)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix(getCurrencySymbol()),

                                Forms\Components\TextInput::make('yearly_price')
                                    ->label('السعر السنوي')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix(getCurrencySymbol()),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sessions_per_month')
                    ->label('عدد الحصص/شهر')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('session_duration_minutes')
                    ->label('مدة الحصة')
                    ->formatStateUsing(fn (string $state): string => $state.' دقيقة')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('monthly_price')
                    ->label('السعر الشهري')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('quarterly_price')
                    ->label('السعر ربع السنوي')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('yearly_price')
                    ->label('السعر السنوي')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
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
                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
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
                        Components\TextEntry::make('name')
                            ->label('اسم الباقة'),

                        Components\TextEntry::make('description')
                            ->label('الوصف')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('تفاصيل الحصص')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('sessions_per_month')
                                    ->label('عدد الحصص في الشهر'),

                                Components\TextEntry::make('session_duration_minutes')
                                    ->label('مدة الحصة')
                                    ->formatStateUsing(fn (string $state): string => $state.' دقيقة'),
                            ]),
                    ]),

                Components\Section::make('الأسعار')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('monthly_price')
                                    ->label('السعر الشهري')
                                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR')),

                                Components\TextEntry::make('quarterly_price')
                                    ->label('السعر ربع السنوي')
                                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR')),

                                Components\TextEntry::make('yearly_price')
                                    ->label('السعر السنوي')
                                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR')),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicPackages::route('/'),
            'create' => Pages\CreateAcademicPackage::route('/create'),
            'view' => Pages\ViewAcademicPackage::route('/{record}'),
            'edit' => Pages\EditAcademicPackage::route('/{record}/edit'),
        ];
    }
}
