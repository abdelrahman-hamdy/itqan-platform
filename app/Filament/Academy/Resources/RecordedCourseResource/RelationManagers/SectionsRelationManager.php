<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'أقسام الدورة';

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان القسم')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: المقدمة'),

                        Forms\Components\TextInput::make('title_en')
                            ->label('Section Title (English)')
                            ->maxLength(255)
                            ->placeholder('Example: Introduction'),
                    ]),

                Forms\Components\Textarea::make('description')
                    ->label('وصف القسم')
                    ->rows(3)
                    ->placeholder('وصف مختصر لمحتوى هذا القسم'),

                Forms\Components\Textarea::make('description_en')
                    ->label('Section Description (English)')
                    ->rows(3)
                    ->placeholder('Brief description of this section content'),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('الترتيب')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('المدة (دقيقة)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('المدة الإجمالية لجميع دروس هذا القسم'),

                        Forms\Components\TextInput::make('lessons_count')
                            ->label('عدد الدروس')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('سيتم تحديثه تلقائياً عند إضافة الدروس'),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('منشور')
                            ->default(true)
                            ->helperText('القسم مرئي للطلاب'),

                        Forms\Components\Toggle::make('is_free_preview')
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
                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable()
                    ->width(80),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان القسم')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),

                Tables\Columns\TextColumn::make('lessons_count')
                    ->label('عدد الدروس')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->formatStateUsing(fn (int $state): string => 
                        $state >= 60 
                            ? floor($state / 60) . ' ساعة ' . ($state % 60) . ' دقيقة'
                            : $state . ' دقيقة'
                    )
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_free_preview')
                    ->label('معاينة مجانية')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                Tables\Filters\TernaryFilter::make('is_free_preview')
                    ->label('معاينة مجانية')
                    ->placeholder('الكل')
                    ->trueLabel('معاينة مجانية')
                    ->falseLabel('مدفوع'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة قسم جديد')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['updated_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['updated_by'] = auth()->id();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }
} 