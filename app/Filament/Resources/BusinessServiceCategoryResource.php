<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessServiceCategoryResource\Pages;
use App\Models\BusinessServiceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusinessServiceCategoryResource extends Resource
{
    protected static ?string $model = BusinessServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'خدمات الأعمال';

    protected static ?string $navigationLabel = 'تصنيفات الخدمات';

    protected static ?string $modelLabel = 'تصنيف خدمة';

    protected static ?string $pluralModelLabel = 'تصنيفات الخدمات';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\Section::make('معلومات التصنيف')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم التصنيف')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: تصميم شعارات'),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف التصنيف')
                            ->maxLength(500)
                            ->placeholder('وصف مختصر للخدمات المقدمة في هذا التصنيف'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('لون التصنيف')
                            ->default('#3B82F6'),

                        Forms\Components\TextInput::make('icon')
                            ->label('أيقونة التصنيف')
                            ->placeholder('مثال: heroicon-o-paint-brush')
                            ->helperText('يمكنك استخدام أيقونات Heroicons أو أي أيقونة أخرى'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('إظهار هذا التصنيف في الواجهة الأمامية'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم التصنيف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('اللون'),

                Tables\Columns\IconColumn::make('icon')
                    ->label('الأيقونة'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('serviceRequests_count')
                    ->label('عدد الطلبات')
                    ->counts('serviceRequests')
                    ->sortable(),

                Tables\Columns\TextColumn::make('portfolioItems_count')
                    ->label('عدد أعمال البورتفوليو')
                    ->counts('portfolioItems')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('جميع التصنيفات')
                    ->trueLabel('التصنيفات النشطة فقط')
                    ->falseLabel('التصنيفات غير النشطة فقط'),
            ])
            ->actions([
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
            'index' => Pages\ListBusinessServiceCategories::route('/'),
            'create' => Pages\CreateBusinessServiceCategory::route('/create'),
            'edit' => Pages\EditBusinessServiceCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
