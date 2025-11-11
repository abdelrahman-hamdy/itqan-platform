<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use App\Models\Academy;
use App\Traits\ScopedToAcademy;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use App\Services\AcademyContextService;

class SubjectResource extends BaseResource
{
    use ScopedToAcademy;

    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'المواد الدراسية';
    
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'مادة';
    
    protected static ?string $pluralModelLabel = 'المواد الدراسية';

    // Note: getEloquentQuery() is now handled by ScopedToAcademy trait

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المادة الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم المادة (عربي)')
                                    ->required()
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم المادة (إنجليزي)')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('subject_code')
                                    ->label('رمز المادة')
                                    ->maxLength(10)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('مثل: MATH101'),
                            ]),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المادة')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('تفاصيل التدريس')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->maxLength(1000)
                            ->placeholder('ملاحظات إدارية خاصة بهذه المادة...')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشطة')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المادة')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('subject_code')
                    ->label('رمز المادة')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('courses_count')
                    ->label('عدد الدورات')
                    ->counts('courses')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
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
                        
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => true]))),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('إلغاء تفعيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => false]))),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label('اسم المادة (عربي)'),
                                Components\TextEntry::make('name_en')
                                    ->label('اسم المادة (إنجليزي)')
                                    ->placeholder('غير محدد'),
                                Components\TextEntry::make('subject_code')
                                    ->label('رمز المادة')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('غير محدد'),
                            ]),
                            
                        Components\TextEntry::make('description')
                            ->label('وصف المادة')
                            ->placeholder('لا يوجد وصف')
                            ->columnSpanFull(),
                    ]),
                    
                Components\Section::make('تفاصيل التدريس')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->placeholder('لا توجد ملاحظات')
                                    ->columnSpanFull(),

                                Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية')
                                    ->badge()
                                    ->color('primary'),
                            ]),
                    ]),
                    
                Components\Section::make('الحالة والإحصائيات')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('is_active')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'غير نشطة'),
                                    
                                Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية')
                                    ->badge()
                                    ->color('primary'),
                                    
                                Components\TextEntry::make('courses_count')
                                    ->label('عدد الدورات')
                                    ->badge()
                                    ->color('info'),
                                    
                                Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),
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
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'view' => Pages\ViewSubject::route('/{record}'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        return $academyId ? static::getModel()::forAcademy($academyId)->count() : '0';
    }


} 