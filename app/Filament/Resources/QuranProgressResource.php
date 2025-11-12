<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranProgressResource\Pages;
use App\Filament\Resources\QuranProgressResource\RelationManagers;
use App\Models\QuranProgress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranProgressResource extends Resource
{
    protected static ?string $model = QuranProgress::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'تقدم القرآن';

    protected static ?string $modelLabel = 'تقدم قرآني';

    protected static ?string $pluralModelLabel = 'تقدم القرآن';

    protected static ?string $navigationGroup = 'متابعة التقدم';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->required(),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->required(),
                Forms\Components\Select::make('quran_teacher_id')
                    ->relationship('quranTeacher', 'id'),
                Forms\Components\TextInput::make('quran_subscription_id')
                    ->numeric(),
                Forms\Components\Select::make('circle_id')
                    ->relationship('circle', 'id'),
                Forms\Components\Select::make('session_id')
                    ->relationship('session', 'title'),
                Forms\Components\TextInput::make('progress_code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\DatePicker::make('progress_date')
                    ->required(),
                Forms\Components\TextInput::make('progress_type')
                    ->required(),
                Forms\Components\TextInput::make('current_surah')
                    ->numeric(),
                Forms\Components\TextInput::make('current_verse')
                    ->numeric(),
                Forms\Components\TextInput::make('current_page')
                    ->numeric(),
                Forms\Components\TextInput::make('current_face')
                    ->numeric(),
                Forms\Components\TextInput::make('target_surah')
                    ->numeric(),
                Forms\Components\TextInput::make('target_verse')
                    ->numeric(),
                Forms\Components\TextInput::make('target_page')
                    ->numeric(),
                Forms\Components\TextInput::make('target_face')
                    ->numeric(),
                Forms\Components\TextInput::make('verses_memorized')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('papers_memorized')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('verses_reviewed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('papers_reviewed')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('verses_perfect')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('papers_perfect')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('verses_need_work')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('papers_need_work')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_verses_memorized')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_papers_memorized')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_pages_memorized')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_surahs_completed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('memorization_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('recitation_quality')
                    ->numeric(),
                Forms\Components\TextInput::make('tajweed_accuracy')
                    ->numeric(),
                Forms\Components\TextInput::make('fluency_level')
                    ->numeric(),
                Forms\Components\TextInput::make('confidence_level')
                    ->numeric(),
                Forms\Components\TextInput::make('retention_rate')
                    ->numeric(),
                Forms\Components\TextInput::make('common_mistakes'),
                Forms\Components\TextInput::make('improvement_areas'),
                Forms\Components\TextInput::make('strengths'),
                Forms\Components\TextInput::make('weekly_goal')
                    ->numeric(),
                Forms\Components\TextInput::make('weekly_goal_papers')
                    ->numeric(),
                Forms\Components\TextInput::make('monthly_goal')
                    ->numeric(),
                Forms\Components\TextInput::make('monthly_goal_papers')
                    ->numeric(),
                Forms\Components\TextInput::make('goal_progress')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('difficulty_level'),
                Forms\Components\TextInput::make('study_hours_this_week')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('average_daily_study')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\DatePicker::make('last_review_date'),
                Forms\Components\DatePicker::make('next_review_date'),
                Forms\Components\TextInput::make('repetition_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('mastery_level')
                    ->required(),
                Forms\Components\Toggle::make('certificate_eligible')
                    ->required(),
                Forms\Components\TextInput::make('milestones_achieved'),
                Forms\Components\TextInput::make('performance_trends'),
                Forms\Components\TextInput::make('learning_pace')
                    ->required(),
                Forms\Components\TextInput::make('consistency_score')
                    ->numeric(),
                Forms\Components\TextInput::make('attendance_impact')
                    ->numeric(),
                Forms\Components\TextInput::make('homework_completion_rate')
                    ->numeric(),
                Forms\Components\TextInput::make('quiz_average_score')
                    ->numeric(),
                Forms\Components\TextInput::make('parent_involvement_level')
                    ->numeric(),
                Forms\Components\TextInput::make('motivation_level')
                    ->numeric(),
                Forms\Components\TextInput::make('challenges_faced'),
                Forms\Components\TextInput::make('support_needed'),
                Forms\Components\TextInput::make('recommendations'),
                Forms\Components\TextInput::make('next_steps'),
                Forms\Components\Textarea::make('teacher_notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('parent_notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('student_feedback')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('assessment_date'),
                Forms\Components\TextInput::make('overall_rating')
                    ->numeric(),
                Forms\Components\TextInput::make('progress_status')
                    ->required(),
                Forms\Components\TextInput::make('created_by')
                    ->numeric(),
                Forms\Components\TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academy.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quranTeacher.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quran_subscription_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('session.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('progress_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_type'),
                Tables\Columns\TextColumn::make('current_surah')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_verse')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_page')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_face')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_surah')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_verse')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_page')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_face')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verses_memorized')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('papers_memorized')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verses_reviewed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('papers_reviewed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verses_perfect')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('papers_perfect')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verses_need_work')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('papers_need_work')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_verses_memorized')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_papers_memorized')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_pages_memorized')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_surahs_completed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('memorization_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recitation_quality')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tajweed_accuracy')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fluency_level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('confidence_level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retention_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weekly_goal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weekly_goal_papers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_goal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_goal_papers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('goal_progress')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('difficulty_level'),
                Tables\Columns\TextColumn::make('study_hours_this_week')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_daily_study')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_review_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_review_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('repetition_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mastery_level'),
                Tables\Columns\IconColumn::make('certificate_eligible')
                    ->boolean(),
                Tables\Columns\TextColumn::make('learning_pace'),
                Tables\Columns\TextColumn::make('consistency_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_impact')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('homework_completion_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quiz_average_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent_involvement_level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('motivation_level')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assessment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('overall_rating')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_status'),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListQuranProgress::route('/'),
            'create' => Pages\CreateQuranProgress::route('/create'),
            'edit' => Pages\EditQuranProgress::route('/{record}/edit'),
        ];
    }
}
