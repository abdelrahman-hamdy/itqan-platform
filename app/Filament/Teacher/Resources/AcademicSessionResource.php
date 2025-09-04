<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\AcademicSessionResource\Pages;
use App\Filament\Teacher\Resources\AcademicSessionResource\RelationManagers;
use App\Models\AcademicSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSessionResource extends Resource
{
    protected static ?string $model = AcademicSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->required(),
                Forms\Components\Select::make('academic_teacher_id')
                    ->relationship('academicTeacher', 'id')
                    ->required(),
                Forms\Components\Select::make('academic_subscription_id')
                    ->relationship('academicSubscription', 'id'),
                Forms\Components\Select::make('academic_individual_lesson_id')
                    ->relationship('academicIndividualLesson', 'name'),
                Forms\Components\Select::make('interactive_course_session_id')
                    ->relationship('interactiveCourseSession', 'title'),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name'),
                Forms\Components\TextInput::make('session_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('session_sequence')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('session_type')
                    ->required(),
                Forms\Components\Toggle::make('is_template')
                    ->required(),
                Forms\Components\Toggle::make('is_generated')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('is_scheduled')
                    ->required(),
                Forms\Components\DateTimePicker::make('teacher_scheduled_at'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('lesson_objectives'),
                Forms\Components\DateTimePicker::make('scheduled_at'),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('ended_at'),
                Forms\Components\TextInput::make('duration_minutes')
                    ->required()
                    ->numeric()
                    ->default(60),
                Forms\Components\TextInput::make('actual_duration_minutes')
                    ->numeric(),
                Forms\Components\TextInput::make('location_type')
                    ->required(),
                Forms\Components\Textarea::make('location_details')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('meeting_link')
                    ->maxLength(255),
                Forms\Components\TextInput::make('meeting_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('meeting_password')
                    ->password()
                    ->maxLength(255),
                Forms\Components\TextInput::make('google_event_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('google_calendar_id')
                    ->maxLength(255),
                Forms\Components\Textarea::make('google_meet_url')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('google_meet_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('google_attendees'),
                Forms\Components\TextInput::make('meeting_source')
                    ->required(),
                Forms\Components\TextInput::make('meeting_platform')
                    ->maxLength(255),
                Forms\Components\TextInput::make('meeting_data'),
                Forms\Components\TextInput::make('meeting_room_name')
                    ->maxLength(255),
                Forms\Components\Toggle::make('meeting_auto_generated')
                    ->required(),
                Forms\Components\DateTimePicker::make('meeting_expires_at'),
                Forms\Components\TextInput::make('attendance_status')
                    ->required(),
                Forms\Components\TextInput::make('participants_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('attendance_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('attendance_log'),
                Forms\Components\DateTimePicker::make('attendance_marked_at'),
                Forms\Components\TextInput::make('attendance_marked_by')
                    ->numeric(),
                Forms\Components\Textarea::make('session_topics_covered')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('lesson_content')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('learning_outcomes'),
                Forms\Components\Textarea::make('homework_description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('homework_file')
                    ->maxLength(255),
                Forms\Components\TextInput::make('session_grade')
                    ->numeric(),
                Forms\Components\Textarea::make('session_notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('teacher_feedback')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('student_feedback')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('parent_feedback')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('overall_rating')
                    ->numeric(),
                Forms\Components\Textarea::make('technical_issues')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('makeup_session_for')
                    ->numeric(),
                Forms\Components\Toggle::make('is_makeup_session')
                    ->required(),
                Forms\Components\Toggle::make('is_auto_generated')
                    ->required(),
                Forms\Components\Textarea::make('cancellation_reason')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cancellation_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cancelled_by')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('cancelled_at'),
                Forms\Components\Textarea::make('reschedule_reason')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('rescheduled_from'),
                Forms\Components\DateTimePicker::make('rescheduled_to'),
                Forms\Components\Textarea::make('rescheduling_note')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('materials_used'),
                Forms\Components\TextInput::make('assessment_results'),
                Forms\Components\Toggle::make('follow_up_required')
                    ->required(),
                Forms\Components\Textarea::make('follow_up_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('notification_log'),
                Forms\Components\DateTimePicker::make('reminder_sent_at'),
                Forms\Components\Textarea::make('meeting_creation_error')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('last_error_at'),
                Forms\Components\TextInput::make('retry_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('created_by')
                    ->numeric(),
                Forms\Components\TextInput::make('updated_by')
                    ->numeric(),
                Forms\Components\TextInput::make('scheduled_by')
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
                Tables\Columns\TextColumn::make('academicTeacher.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicSubscription.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicIndividualLesson.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('interactiveCourseSession.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('session_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('session_sequence')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('session_type'),
                Tables\Columns\IconColumn::make('is_template')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_generated')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\IconColumn::make('is_scheduled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('teacher_scheduled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location_type'),
                Tables\Columns\TextColumn::make('meeting_link')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meeting_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('google_event_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('google_calendar_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('google_meet_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meeting_source'),
                Tables\Columns\TextColumn::make('meeting_platform')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meeting_room_name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('meeting_auto_generated')
                    ->boolean(),
                Tables\Columns\TextColumn::make('meeting_expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_status'),
                Tables\Columns\TextColumn::make('participants_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_marked_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_marked_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('homework_file')
                    ->searchable(),
                Tables\Columns\TextColumn::make('session_grade')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('overall_rating')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('makeup_session_for')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_makeup_session')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_auto_generated')
                    ->boolean(),
                Tables\Columns\TextColumn::make('cancellation_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cancelled_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rescheduled_from')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rescheduled_to')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('follow_up_required')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reminder_sent_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_error_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retry_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_by')
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
            'index' => Pages\ListAcademicSessions::route('/'),
            'create' => Pages\CreateAcademicSession::route('/create'),
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
