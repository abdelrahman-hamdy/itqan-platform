<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use App\Models\Academy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'المواد الدراسية';
    
    protected static ?string $navigationGroup = 'إدارة المحتوى';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'مادة';
    
    protected static ?string $pluralModelLabel = 'المواد الدراسية';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المادة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('اسم المادة (عربي)')
                                    ->required()
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم المادة (إنجليزي)')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->relationship('academy', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('التصنيف')
                                    ->options([
                                        'general' => 'عام',
                                        'science' => 'علوم',
                                        'language' => 'لغات',
                                        'arts' => 'فنون',
                                        'mathematics' => 'رياضيات',
                                        'social' => 'اجتماعيات',
                                        'quran' => 'قرآن كريم',
                                        'islamic' => 'تربية إسلامية',
                                    ])
                                    ->default('general')
                                    ->required(),
                                    
                                Forms\Components\Toggle::make('is_academic')
                                    ->label('مادة أكاديمية')
                                    ->helperText('إلغاء التحديد للمواد القرآنية')
                                    ->default(true),
                            ]),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المادة')
                            ->maxLength(1000)
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
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المادة')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('name_en')
                    ->label('الاسم الإنجليزي')
                    ->searchable()
                    ->placeholder('غير محدد'),
                    
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('category')
                    ->label('التصنيف')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'science',
                        'warning' => 'language',
                        'info' => 'arts',
                        'danger' => 'mathematics',
                        'secondary' => 'social',
                        'purple' => 'quran',
                        'emerald' => 'islamic',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'عام',
                        'science' => 'علوم',
                        'language' => 'لغات',
                        'arts' => 'فنون',
                        'mathematics' => 'رياضيات',
                        'social' => 'اجتماعيات',
                        'quran' => 'قرآن كريم',
                        'islamic' => 'تربية إسلامية',
                        default => $state,
                    }),
                    
                Tables\Columns\IconColumn::make('is_academic')
                    ->label('أكاديمية')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-book-open')
                    ->trueColor('success')
                    ->falseColor('warning'),
                    
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
                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'general' => 'عام',
                        'science' => 'علوم',
                        'language' => 'لغات',
                        'arts' => 'فنون',
                        'mathematics' => 'رياضيات',
                        'social' => 'اجتماعيات',
                        'quran' => 'قرآن كريم',
                        'islamic' => 'تربية إسلامية',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_academic')
                    ->label('نوع المادة')
                    ->placeholder('الكل')
                    ->trueLabel('مواد أكاديمية')
                    ->falseLabel('مواد قرآنية'),
                    
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
                Components\Section::make('معلومات المادة')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label('اسم المادة (عربي)'),
                                Components\TextEntry::make('name_en')
                                    ->label('اسم المادة (إنجليزي)')
                                    ->placeholder('غير محدد'),
                                Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية'),
                                Components\TextEntry::make('category')
                                    ->label('التصنيف')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'general' => 'عام',
                                        'science' => 'علوم',
                                        'language' => 'لغات',
                                        'arts' => 'فنون',
                                        'mathematics' => 'رياضيات',
                                        'social' => 'اجتماعيات',
                                        'quran' => 'قرآن كريم',
                                        'islamic' => 'تربية إسلامية',
                                        default => $state,
                                    }),
                            ]),
                            
                        Components\TextEntry::make('description')
                            ->label('وصف المادة')
                            ->placeholder('لا يوجد وصف')
                            ->columnSpanFull(),
                    ]),
                    
                Components\Section::make('الحالة والإحصائيات')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('is_academic')
                                    ->label('نوع المادة')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'أكاديمية' : 'قرآنية'),
                                    
                                Components\TextEntry::make('is_active')
                                    ->label('الحالة')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'غير نشطة'),
                                    
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
        return static::getModel()::count();
    }
} 