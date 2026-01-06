<?php

namespace App\Filament\Resources\HomeworkSubmissionsResource\Pages;

use App\Enums\HomeworkSubmissionStatus;
use App\Filament\Resources\HomeworkSubmissionsResource;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListHomeworkSubmissions extends ListRecords
{
    protected static string $resource = HomeworkSubmissionsResource::class;

    public function getTabs(): array
    {
        // Only count 'submitted' status (pending review), not 'late'
        $academicPending = AcademicHomeworkSubmission::where('submission_status', 'submitted')->count();
        $interactivePending = InteractiveCourseHomeworkSubmission::where('submission_status', 'submitted')->count();
        $totalPending = $academicPending + $interactivePending;

        return [
            'all' => Tab::make('الكل')
                ->icon('heroicon-o-inbox-stack')
                ->badge($totalPending > 0 ? $totalPending : null)
                ->badgeColor('warning'),
            'academic' => Tab::make('الأكاديمية')
                ->icon('heroicon-o-academic-cap')
                ->badge($academicPending > 0 ? $academicPending : null)
                ->badgeColor('warning'),
            'interactive' => Tab::make('الدورات التفاعلية')
                ->icon('heroicon-o-play-circle')
                ->badge($interactivePending > 0 ? $interactivePending : null)
                ->badgeColor('warning'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'all';
    }

    protected function getTableQuery(): Builder
    {
        $activeTab = $this->activeTab ?? 'all';

        if ($activeTab === 'interactive') {
            return InteractiveCourseHomeworkSubmission::query()->submitted();
        }

        if ($activeTab === 'academic') {
            return AcademicHomeworkSubmission::query()->submitted();
        }

        // 'all' tab - use a subquery approach to combine both tables
        // This avoids MySQL's limitation with ORDER BY on UNION queries
        $academyId = (int) (AcademyContextService::getCurrentAcademyId() ?? 1);

        // Build raw SQL with embedded academy_id to avoid binding issues with filters
        // Shows ALL submissions (submitted, late, graded) - badge handles pending count separately
        $unionSql = "
            SELECT id, academy_id, student_id, submission_status, submitted_at,
                   score, max_score, is_late, teacher_feedback, created_at, updated_at,
                   'academic' as submission_type
            FROM academic_homework_submissions
            WHERE submission_status IN ('submitted', 'late', 'graded')
              AND deleted_at IS NULL
              AND academy_id = {$academyId}
            UNION ALL
            SELECT id, academy_id, student_id, submission_status, submitted_at,
                   score, max_score, is_late, teacher_feedback, created_at, updated_at,
                   'interactive' as submission_type
            FROM interactive_course_homework_submissions
            WHERE submission_status IN ('submitted', 'late', 'graded')
              AND deleted_at IS NULL
              AND academy_id = {$academyId}
        ";

        // Use from with raw subquery - filters will apply to the outer query
        return AcademicHomeworkSubmission::query()
            ->withoutGlobalScopes()
            ->from(DB::raw("({$unionSql}) as submissions"));
    }

    public function table(Table $table): Table
    {
        $activeTab = $this->activeTab ?? 'all';

        if ($activeTab === 'interactive') {
            return $this->getInteractiveTable($table);
        }

        if ($activeTab === 'academic') {
            return $this->getAcademicTable($table);
        }

        // 'all' tab - show combined view
        return $this->getAllTable($table);
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
                    ->badge(),

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
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'تفاصيل التسليم - '.$record->student?->name)
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

                Tables\Columns\TextColumn::make('homework.session.course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('homework.title')
                    ->label('الواجب')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('تاريخ التسليم')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submission_status')
                    ->label('الحالة')
                    ->badge(),

                Tables\Columns\TextColumn::make('score')
                    ->label('الدرجة')
                    ->formatStateUsing(fn ($state, $record) => $state !== null
                        ? "{$state}/".($record->max_score ?? 10)
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
                    ->options(HomeworkSubmissionStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'تفاصيل التسليم - '.$record->student?->name)
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

    protected function getAllTable(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submission_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'academic' => 'أكاديمي',
                        'interactive' => 'تفاعلي',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'academic' => 'info',
                        'interactive' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        // Load student relationship based on type
                        if ($record->submission_type === 'interactive') {
                            $submission = InteractiveCourseHomeworkSubmission::find($record->id);

                            return $submission?->student?->name ?? '-';
                        }

                        return $record->student?->name ?? '-';
                    }),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('تاريخ التسليم')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submission_status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof HomeworkSubmissionStatus) {
                            return $state->getLabel();
                        }

                        return HomeworkSubmissionStatus::tryFrom($state)?->getLabel() ?? $state;
                    })
                    ->color(function ($state) {
                        if ($state instanceof HomeworkSubmissionStatus) {
                            return $state->getColor();
                        }

                        return HomeworkSubmissionStatus::tryFrom($state)?->getColor() ?? 'gray';
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('الدرجة')
                    ->formatStateUsing(fn ($state, $record) => $state !== null
                        ? "{$state}/".($record->max_score ?? 10)
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
                Tables\Filters\SelectFilter::make('submission_type')
                    ->label('النوع')
                    ->options([
                        'academic' => 'أكاديمي',
                        'interactive' => 'تفاعلي',
                    ])
                    ->attribute('submission_type'),

                Tables\Filters\SelectFilter::make('submission_status')
                    ->label('الحالة')
                    ->options([
                        'submitted' => 'تم التسليم',
                        'late' => 'متأخر',
                        'graded' => 'تم التصحيح',
                    ])
                    ->attribute('submission_status'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'تفاصيل التسليم')
                    ->modalContent(function ($record) {
                        // Load the actual model based on type
                        if ($record->submission_type === 'interactive') {
                            $submission = InteractiveCourseHomeworkSubmission::with(['student', 'homework.session.course'])->find($record->id);

                            return view('filament.resources.homework-submissions.interactive-view', ['record' => $submission]);
                        }
                        $submission = AcademicHomeworkSubmission::with(['student', 'homework', 'session'])->find($record->id);

                        return view('filament.resources.homework-submissions.academic-view', ['record' => $submission]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق'),

                Tables\Actions\Action::make('grade')
                    ->label('تصحيح')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(function ($record) {
                        $status = $record->submission_status;
                        if ($status instanceof HomeworkSubmissionStatus) {
                            return $status->needsReview();
                        }

                        return in_array($status, ['submitted', 'late']);
                    })
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
                        // Load the actual model based on type and grade it
                        if ($record->submission_type === 'interactive') {
                            $submission = InteractiveCourseHomeworkSubmission::find($record->id);
                            $submission?->grade(
                                score: $data['score'],
                                feedback: $data['teacher_feedback'] ?? null,
                                gradedBy: auth()->id(),
                            );
                        } else {
                            $submission = AcademicHomeworkSubmission::find($record->id);
                            $submission?->grade(
                                score: $data['score'],
                                feedback: $data['teacher_feedback'] ?? null,
                            );
                        }
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
