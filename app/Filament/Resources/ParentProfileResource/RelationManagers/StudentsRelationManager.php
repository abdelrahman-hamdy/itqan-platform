<?php

namespace App\Filament\Resources\ParentProfileResource\RelationManagers;

use App\Enums\RelationshipType;
use App\Models\StudentProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'الأبناء';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('relationship_type')
                    ->label('نوع العلاقة')
                    ->options(RelationshipType::labels())
                    ->required()
                    ->default(RelationshipType::FATHER->value),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->full_name)),
                Tables\Columns\TextColumn::make('student_code')
                    ->label('رمز الطالب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المرحلة الدراسية')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('relationship_type')
                    ->label('نوع العلاقة')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-')
                    ->badge()
                    ->color(fn ($state) => match ($state?->value) {
                        'father' => 'primary',
                        'mother' => 'success',
                        'other' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('relationship_type')
                    ->label('نوع العلاقة')
                    ->options(RelationshipType::labels()),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('ربط طالب')
                    ->modalHeading('ربط طالب بولي الأمر')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['student_code', 'first_name', 'last_name', 'email'])
                    ->recordTitle(fn (StudentProfile $record): string => "{$record->full_name} ({$record->student_code})")
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('relationship_type')
                            ->label('نوع العلاقة')
                            ->options(RelationshipType::labels())
                            ->required()
                            ->default(RelationshipType::FATHER->value),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل نوع العلاقة'),
                Tables\Actions\DetachAction::make()
                    ->label('فك الربط'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('فك ربط المحددين'),
                ]),
            ]);
    }
}
