<?php

namespace App\Filament\Resources\ParentProfileResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\AttachAction;
use Filament\Actions\EditAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use App\Enums\RelationshipType;
use App\Models\StudentProfile;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'الأبناء';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('relationship_type')
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
                ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->full_name)),
                TextColumn::make('student_code')
                    ->label('رمز الطالب')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('gradeLevel.name')
                    ->label('المرحلة الدراسية')
                    ->badge()
                    ->color('info'),
                TextColumn::make('relationship_type')
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
                SelectFilter::make('relationship_type')
                    ->label('نوع العلاقة')
                    ->options(RelationshipType::labels()),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('ربط طالب')
                    ->modalHeading('ربط طالب بولي الأمر')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['student_code', 'first_name', 'last_name', 'email'])
                    ->recordTitle(fn (StudentProfile $record): string => "{$record->full_name} ({$record->student_code})")
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('relationship_type')
                            ->label('نوع العلاقة')
                            ->options(RelationshipType::labels())
                            ->required()
                            ->default(RelationshipType::FATHER->value),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل نوع العلاقة'),
                DetachAction::make()
                    ->label('فك الربط'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('فك ربط المحددين'),
                ]),
            ]);
    }
}
