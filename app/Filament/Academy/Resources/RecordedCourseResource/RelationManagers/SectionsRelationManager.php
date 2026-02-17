<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'أقسام الدورة';

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('عنوان القسم')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('مثال: المقدمة')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('وصف القسم')
                    ->rows(3)
                    ->placeholder('وصف مختصر لمحتوى هذا القسم')
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        TextInput::make('order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(1),

                        TextInput::make('duration_minutes')
                            ->label('المدة (دقيقة)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('المدة الإجمالية لجميع دروس هذا القسم'),

                        TextInput::make('lessons_count')
                            ->label('عدد الدروس')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('سيتم تحديثه تلقائياً عند إضافة الدروس'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('منشور')
                            ->default(true)
                            ->helperText('القسم مرئي للطلاب'),

                        Toggle::make('is_free_preview')
                            ->label('معاينة مجانية')
                            ->default(false)
                            ->helperText('يمكن للطلاب مشاهدة هذا القسم مجاناً'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable()
                    ->width(80),

                TextColumn::make('title')
                    ->label('عنوان القسم')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),

                TextColumn::make('lessons_count')
                    ->label('عدد الدروس')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->formatStateUsing(fn (int $state): string => $state >= 60
                            ? floor($state / 60).' ساعة '.($state % 60).' دقيقة'
                            : $state.' دقيقة'
                    )
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                IconColumn::make('is_free_preview')
                    ->label('معاينة مجانية')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                TernaryFilter::make('is_free_preview')
                    ->label('معاينة مجانية')
                    ->placeholder('الكل')
                    ->trueLabel('معاينة مجانية')
                    ->falseLabel('مدفوع'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة قسم جديد'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }
}
