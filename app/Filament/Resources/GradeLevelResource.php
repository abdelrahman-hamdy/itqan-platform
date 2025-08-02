<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeLevelResource\Pages;
use App\Models\GradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ScopedToAcademy;
use App\Services\AcademyContextService;

class GradeLevelResource extends BaseResource
{
    use ScopedToAcademy;

    protected static ?string $model = GradeLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'الصفوف الدراسية';
    
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';
    
    protected static ?string $modelLabel = 'صف دراسي';
    
    protected static ?string $pluralModelLabel = 'الصفوف الدراسية';

    protected static ?int $navigationSort = 2;

    // Note: getEloquentQuery() is now handled by ScopedToAcademy trait
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
                static::getAcademyColumn(), // Add academy column when viewing all academies
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

                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),

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
        $academyId = AcademyContextService::getCurrentAcademyId();
        return $academyId ? static::getModel()::forAcademy($academyId)->count() : '0';
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
