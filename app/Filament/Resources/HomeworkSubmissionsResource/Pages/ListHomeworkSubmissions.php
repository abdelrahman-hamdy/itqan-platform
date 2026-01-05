<?php

namespace App\Filament\Resources\HomeworkSubmissionsResource\Pages;

use App\Enums\HomeworkSubmissionStatus;
use App\Filament\Resources\HomeworkSubmissionsResource;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomework;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListHomeworkSubmissions extends ListRecords
{
    protected static string $resource = HomeworkSubmissionsResource::class;

    public function getTabs(): array
    {
        return [
            'academic' => Tab::make('الأكاديمية')
                ->icon('heroicon-o-academic-cap')
                ->badge(AcademicHomeworkSubmission::submitted()->count()),
            'interactive' => Tab::make('الدورات التفاعلية')
                ->icon('heroicon-o-play-circle')
                ->badge(InteractiveCourseHomework::submitted()->count()),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'academic';
    }

    protected function getTableQuery(): Builder
    {
        $activeTab = $this->activeTab ?? 'academic';

        if ($activeTab === 'interactive') {
            return InteractiveCourseHomework::query()->submitted();
        }

        return AcademicHomeworkSubmission::query()->submitted();
    }

    public function table(Table $table): Table
    {
        $activeTab = $this->activeTab ?? 'academic';

        if ($activeTab === 'interactive') {
            return $this->getInteractiveTable($table);
        }

        return $this->getAcademicTable($table);
    }

    protected function getAcademicTable(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('homework.title')
                    ->label('الواجب')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('session.session_code')
                    ->label('الجلسة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('تاريخ التسليم')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submission_status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state?->value ?? $state) {
                        'pending' => 'بانتظار التسليم',
                        'submitted' => 'تم التسليم',
                        'late' => 'متأخر',
                        'graded' => 'تم التصحيح',
                        default => $state?->value ?? $state,
                    })
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'pending' => 'gray',
                        'submitted' => 'info',
                        'late' => 'danger',
                        'graded' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('الدرجة')
                    ->formatStateUsing(fn ($state, $record) => $state !== null ? "{$state}/{$record->max_score}" : '-'),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('متأخر')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('submission_status')
                    ->label('الحالة')
                    ->options(HomeworkSubmissionStatus::class),

                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('متأخر'),

                Tables\Filters\Filter::make('pending_grading')
                    ->label('بانتظار التصحيح')
                    ->query(fn (Builder $query): Builder => $query->pendingGrading()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'تفاصيل التسليم - ' . $record->student?->name)
                    ->modalContent(fn ($record) => view('filament.resources.homework-submissions.academic-view', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),

                Tables\Actions\Action::make('grade')
                    ->label('تصحيح')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->submission_status?->value ?? $record->submission_status, ['submitted', 'late']))
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->label('الدرجة (من 10)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->default(10)
                            ->suffix('/ 10'),
                        Forms\Components\Textarea::make('teacher_feedback')
                            ->label('ملاحظات المعلم')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->grade(
                            score: $data['score'],
                            feedback: $data['teacher_feedback'] ?? null,
                        );
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    protected function getInteractiveTable(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('session.course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('session.session_code')
                    ->label('الجلسة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('تاريخ التسليم')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submission_status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state?->value ?? $state) {
                        'pending' => 'بانتظار التسليم',
                        'submitted' => 'تم التسليم',
                        'late' => 'متأخر',
                        'graded' => 'تم التصحيح',
                        default => $state?->value ?? $state,
                    })
                    ->color(fn ($state) => match ($state?->value ?? $state) {
                        'pending' => 'gray',
                        'submitted' => 'info',
                        'late' => 'danger',
                        'graded' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('الدرجة')
                    ->formatStateUsing(fn ($state, $record) => $state !== null
                        ? "{$state}/" . ($record->max_score ?? 10)
                        : '-'),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('متأخر')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('submission_status')
                    ->label('الحالة')
                    ->options(HomeworkSubmissionStatus::options()),

                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('متأخر'),

                Tables\Filters\Filter::make('pending_grading')
                    ->label('بانتظار التصحيح')
                    ->query(fn (Builder $query): Builder => $query->pendingGrading()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'تفاصيل التسليم - ' . $record->student?->name)
                    ->modalContent(fn ($record) => view('filament.resources.homework-submissions.interactive-view', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),

                Tables\Actions\Action::make('grade')
                    ->label('تصحيح')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->submission_status?->value ?? $record->submission_status, ['submitted', 'late']))
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->label('الدرجة (من 10)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->default(10)
                            ->suffix('/ 10'),
                        Forms\Components\Textarea::make('teacher_feedback')
                            ->label('ملاحظات المعلم')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->grade(
                            score: $data['score'],
                            feedback: $data['teacher_feedback'] ?? null,
                            gradedBy: auth()->id(),
                        );
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
