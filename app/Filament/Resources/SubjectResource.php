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
    
    protected static ?string $navigationGroup = 'القسم الأكاديمي';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'مادة';
    
    protected static ?string $pluralModelLabel = 'المواد الدراسية';

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
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('difficulty_level')
                                    ->label('مستوى الصعوبة')
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                    ])
                                    ->default('beginner')
                                    ->required(),

                                Forms\Components\TextInput::make('hours_per_week')
                                    ->label('عدد الساعات أسبوعياً')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(20)
                                    ->default(2)
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->maxLength(500)
                            ->placeholder('مثل: إتمام الرياضيات الأساسية، معرفة اللغة الإنجليزية...')
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
                    
                Tables\Columns\TextColumn::make('subject_code')
                    ->label('رمز المادة')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),
                    
                Tables\Columns\BadgeColumn::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('hours_per_week')
                    ->label('ساعات/أسبوع')
                    ->sortable()
                    ->alignCenter()
                    ->suffix(' ساعة'),
                    
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
                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ]),
                    
                Tables\Filters\Filter::make('hours_per_week')
                    ->label('عدد الساعات')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('hours_from')
                                    ->label('من')
                                    ->numeric(),
                                Forms\Components\TextInput::make('hours_to')
                                    ->label('إلى')
                                    ->numeric(),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['hours_from'], fn (Builder $query, $hours): Builder => $query->where('hours_per_week', '>=', $hours))
                            ->when($data['hours_to'], fn (Builder $query, $hours): Builder => $query->where('hours_per_week', '<=', $hours));
                    }),
                    
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
                                Components\TextEntry::make('difficulty_level')
                                    ->label('مستوى الصعوبة')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'beginner' => 'success',
                                        'intermediate' => 'warning',
                                        'advanced' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        default => $state,
                                    }),
                                    
                                Components\TextEntry::make('hours_per_week')
                                    ->label('ساعات أسبوعياً')
                                    ->badge()
                                    ->color('info')
                                    ->suffix(' ساعة'),
                            ]),

                        Components\TextEntry::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->placeholder('لا توجد متطلبات')
                            ->columnSpanFull(),
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
        return static::getModel()::count();
    }
} 