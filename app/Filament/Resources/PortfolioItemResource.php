<?php

namespace App\Filament\Resources;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PortfolioItemResource\Pages\ListPortfolioItems;
use App\Filament\Resources\PortfolioItemResource\Pages\CreatePortfolioItem;
use App\Filament\Resources\PortfolioItemResource\Pages\ViewPortfolioItem;
use App\Filament\Resources\PortfolioItemResource\Pages\EditPortfolioItem;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\PortfolioItemResource\Pages;
use App\Models\PortfolioItem;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PortfolioItemResource extends Resource
{
    use TenantAwareFileUpload;

    protected static ?string $model = PortfolioItem::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';

    protected static string | \UnitEnum | null $navigationGroup = 'خدمات الأعمال';

    protected static ?string $navigationLabel = 'البورتفوليو';

    protected static ?string $modelLabel = 'عمل البورتفوليو';

    protected static ?string $pluralModelLabel = 'أعمال البورتفوليو';

    protected static ?int $navigationSort = 3;

    /**
     * Check if the current user can access this resource
     */
    public static function canAccess(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can create records
     */
    public static function canCreate(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can edit records
     */
    public static function canEdit(Model $record): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can delete records
     */
    public static function canDelete(Model $record): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    /**
     * Check if the current user can view records
     */
    public static function canView(Model $record): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['serviceCategory']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المشروع')
                    ->schema([
                        TextInput::make('project_name')
                            ->label('اسم المشروع')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: تصميم شعار شركة ABC'),

                        Textarea::make('project_description')
                            ->label('وصف المشروع')
                            ->required()
                            ->rows(4)
                            ->placeholder('وصف تفصيلي للمشروع والخدمات المقدمة...'),

                        Select::make('service_category_id')
                            ->label('تصنيف الخدمة')
                            ->relationship('serviceCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        FileUpload::make('project_image')
                            ->label('صورة المشروع')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('450')
                            ->directory(static::getTenantDirectoryLazy('portfolio'))
                            ->visibility('public')
                            ->helperText('يفضل استخدام صور بأبعاد 16:9'),

                        Repeater::make('project_features')
                            ->label('مميزات المشروع')
                            ->schema([
                                TextInput::make('feature')
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

                        TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0)
                            ->helperText('الأرقام الأقل تظهر أولاً'),

                        Toggle::make('is_active')
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
                ImageColumn::make('project_image')
                    ->label('الصورة')
                    ->size(80)
                    ->disk('public')
                    ->visibility('public')
                    ->defaultImageUrl(asset('images/portfolio-placeholder.jpg')),

                TextColumn::make('project_name')
                    ->label('اسم المشروع')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('serviceCategory.name')
                    ->label('تصنيف الخدمة')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('project_description')
                    ->label('الوصف')
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('service_category_id')
                    ->label('تصنيف الخدمة')
                    ->relationship('serviceCategory', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع الأعمال')
                    ->trueLabel('الأعمال النشطة فقط')
                    ->falseLabel('الأعمال غير النشطة فقط'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
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
            'index' => ListPortfolioItems::route('/'),
            'create' => CreatePortfolioItem::route('/create'),
            'view' => ViewPortfolioItem::route('/{record}'),
            'edit' => EditPortfolioItem::route('/{record}/edit'),
        ];
    }
}
