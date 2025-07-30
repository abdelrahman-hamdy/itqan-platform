<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeLevelResource\Pages;
use App\Models\GradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ScopedToAcademy;
use App\Services\AcademyContextService;

class GradeLevelResource extends Resource
{
    use ScopedToAcademy;

    protected static ?string $model = GradeLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'الصفوف الدراسية';
    
    protected static ?string $navigationGroup = 'القسم الأكاديمي';
    
    protected static ?string $modelLabel = 'صف دراسي';
    
    protected static ?string $pluralModelLabel = 'الصفوف الدراسية';

    protected static ?int $navigationSort = 2;

    // Note: getEloquentQuery() is now handled by ScopedToAcademy trait

    public static function shouldRegisterNavigation(): bool
    {
        // For super admin, only show navigation when academy is selected
        if (AcademyContextService::isSuperAdmin()) {
            return AcademyContextService::hasAcademySelected();
        }
        
        // For regular users, always show if they have academy access
        return AcademyContextService::getCurrentAcademy() !== null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المرحلة الدراسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم المرحلة (عربي)')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('مثل: الابتدائية، الإعدادية، الثانوية'),

                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم المرحلة (إنجليزي)')
                                    ->maxLength(255)
                                    ->placeholder('Primary, Middle, High School'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف المرحلة')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('وصف تفصيلي للمرحلة الدراسية ومتطلباتها')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('تفاصيل المرحلة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('level')
                                    ->label('ترتيب المرحلة')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(20)
                                    ->required()
                                    ->helperText('1 للمرحلة الأولى، 2 للثانية، وهكذا'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('نشطة')
                                    ->default(true)
                                    ->helperText('هل هذه المرحلة متاحة للتسجيل؟'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المرحلة')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('level')
                    ->label('الترتيب')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('حالة النشاط')
                    ->placeholder('الكل')
                    ->trueLabel('نشطة')
                    ->falseLabel('غير نشطة'),
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
            ->defaultSort('level', 'asc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGradeLevels::route('/'),
            'create' => Pages\CreateGradeLevel::route('/create'),
            'view' => Pages\ViewGradeLevel::route('/{record}'),
            'edit' => Pages\EditGradeLevel::route('/{record}/edit'),
        ];
    }
}
