<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortfolioItemResource\Pages;
use App\Models\PortfolioItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PortfolioItemResource extends Resource
{
    protected static ?string $model = PortfolioItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'خدمات الأعمال';

    protected static ?string $navigationLabel = 'البورتفوليو';

    protected static ?string $modelLabel = 'عمل البورتفوليو';

    protected static ?string $pluralModelLabel = 'أعمال البورتفوليو';

    protected static ?int $navigationSort = 3;

    /**
     * Check if the current user can access this resource
     */
    public static function canAccess(): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can create records
     */
    public static function canCreate(): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can edit records
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can delete records
     */
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can view records
     */
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Services\AcademyContextService::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المشروع')
                    ->schema([
                        Forms\Components\TextInput::make('project_name')
                            ->label('اسم المشروع')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: تصميم شعار شركة ABC'),

                        Forms\Components\Textarea::make('project_description')
                            ->label('وصف المشروع')
                            ->required()
                            ->rows(4)
                            ->placeholder('وصف تفصيلي للمشروع والخدمات المقدمة...'),

                        Forms\Components\Select::make('service_category_id')
                            ->label('تصنيف الخدمة')
                            ->relationship('serviceCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\FileUpload::make('project_image')
                            ->label('صورة المشروع')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('450')
                            ->directory('portfolio')
                            ->helperText('يفضل استخدام صور بأبعاد 16:9'),

                        Forms\Components\Repeater::make('project_features')
                            ->label('مميزات المشروع')
                            ->schema([
                                Forms\Components\TextInput::make('feature')
                                    ->label('الميزة')
                                    ->required()
                                    ->placeholder('اكتب ميزة المشروع...'),
                            ])
                            ->defaultItems(3)
                            ->minItems(1)
                            ->maxItems(10)
                            ->addActionLabel('إضافة ميزة')
                            ->reorderable()
                            ->collapsible()
                            ->helperText('أضف المميزات الرئيسية للمشروع'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0)
                            ->helperText('الأرقام الأقل تظهر أولاً'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('إظهار هذا العمل في الواجهة الأمامية'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('project_image')
                    ->label('الصورة')
                    ->circular()
                    ->size(60),

                Tables\Columns\TextColumn::make('project_name')
                    ->label('اسم المشروع')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('serviceCategory.name')
                    ->label('تصنيف الخدمة')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('project_description')
                    ->label('الوصف')
                    ->limit(80)
                    ->searchable(),

                Tables\Columns\TextColumn::make('project_features')
                    ->label('عدد المميزات')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? count($state) : '0')
                    ->badge(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_category_id')
                    ->label('تصنيف الخدمة')
                    ->relationship('serviceCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع الأعمال')
                    ->trueLabel('الأعمال النشطة فقط')
                    ->falseLabel('الأعمال غير النشطة فقط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
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
            'index' => Pages\ListPortfolioItems::route('/'),
            'create' => Pages\CreatePortfolioItem::route('/create'),
            'view' => Pages\ViewPortfolioItem::route('/{record}'),
            'edit' => Pages\EditPortfolioItem::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
