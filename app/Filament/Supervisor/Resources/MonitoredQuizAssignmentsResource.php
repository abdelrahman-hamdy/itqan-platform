<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\Supervisor\Resources\MonitoredQuizAssignmentsResource\Pages;
use App\Models\QuizAssignment;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Quiz Assignments Resource for Supervisor Panel
 *
 * Read-only view of quiz assignments for supervised teachers' circles/lessons/courses.
 * Supervisors can view but not create/edit quiz assignments.
 */
class MonitoredQuizAssignmentsResource extends BaseSupervisorResource
{
    protected static ?string $model = QuizAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'تعيينات الاختبارات';

    protected static ?string $modelLabel = 'تعيين اختبار';

    protected static ?string $pluralModelLabel = 'تعيينات الاختبارات';

    protected static ?string $navigationGroup = 'الاختبارات';

    protected static ?int $navigationSort = 2;

    /**
     * Supervisors cannot create quiz assignments.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Supervisors cannot edit quiz assignments.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Supervisors cannot delete quiz assignments.
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quiz.title')
                    ->label('الاختبار')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('assignable_type')
                    ->label('نوع الجهة')
                    ->formatStateUsing(fn ($state) => QuizAssignableType::tryFrom($state)?->label() ?? $state)
                    ->icon(fn ($state) => QuizAssignableType::tryFrom($state)?->icon())
                    ->color(fn ($state) => QuizAssignableType::tryFrom($state)?->color()),

                Tables\Columns\TextColumn::make('assignable')
                    ->label('الجهة')
                    ->formatStateUsing(function ($record) {
                        $assignable = $record->assignable;
                        if (! $assignable) {
                            return '-';
                        }

                        return $assignable->title ?? $assignable->name ?? $assignable->id;
                    }),

                Tables\Columns\TextColumn::make('teacher')
                    ->label('المعلم')
                    ->getStateUsing(function ($record) {
                        $assignable = $record->assignable;
                        if (! $assignable) {
                            return '-';
                        }

                        // Get teacher based on assignable type
                        if (method_exists($assignable, 'quranTeacher') && $assignable->quranTeacher) {
                            return $assignable->quranTeacher->full_name ?? $assignable->quranTeacher->display_name ?? '-';
                        }
                        if (method_exists($assignable, 'academicTeacher') && $assignable->academicTeacher) {
                            return $assignable->academicTeacher->full_name ?? $assignable->academicTeacher->display_name ?? '-';
                        }
                        if (method_exists($assignable, 'assignedTeacher') && $assignable->assignedTeacher) {
                            return $assignable->assignedTeacher->full_name ?? $assignable->assignedTeacher->display_name ?? '-';
                        }

                        return '-';
                    })
                    ->searchable(false),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('مرئي')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_attempts')
                    ->label('المحاولات')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->label('التقديمات')
                    ->counts('attempts')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('available_from')
                    ->label('متاح من')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('فوري')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_until')
                    ->label('متاح حتى')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('دائم')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('assignable_type')
                    ->label('نوع الجهة')
                    ->options(QuizAssignableType::options()),

                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('الحالة')
                    ->trueLabel('مرئي')
                    ->falseLabel('مخفي'),

                Tables\Filters\Filter::make('has_deadline')
                    ->label('له موعد نهائي')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('available_until')),

                Tables\Filters\Filter::make('deadline_passed')
                    ->label('انتهى موعده')
                    ->query(fn (Builder $query): Builder => $query->where('available_until', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                // No bulk actions for supervisors
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الاختبار')
                    ->schema([
                        Infolists\Components\TextEntry::make('quiz.title')
                            ->label('عنوان الاختبار'),
                        Infolists\Components\TextEntry::make('quiz.description')
                            ->label('وصف الاختبار')
                            ->placeholder('لا يوجد وصف'),
                        Infolists\Components\TextEntry::make('quiz.duration_minutes')
                            ->label('مدة الاختبار')
                            ->formatStateUsing(fn ($state) => $state ? "{$state} دقيقة" : 'غير محدد'),
                        Infolists\Components\TextEntry::make('quiz.passing_score')
                            ->label('درجة النجاح')
                            ->formatStateUsing(fn ($state) => "{$state}%"),
                    ])->columns(2),

                Infolists\Components\Section::make('معلومات التعيين')
                    ->schema([
                        Infolists\Components\TextEntry::make('assignable_type')
                            ->label('نوع الجهة')
                            ->formatStateUsing(fn ($state) => QuizAssignableType::tryFrom($state)?->label() ?? $state),
                        Infolists\Components\TextEntry::make('assignable.title')
                            ->label('الجهة')
                            ->getStateUsing(function ($record) {
                                $assignable = $record->assignable;

                                return $assignable->title ?? $assignable->name ?? '-';
                            }),
                        Infolists\Components\IconEntry::make('is_visible')
                            ->label('مرئي للطلاب')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('max_attempts')
                            ->label('عدد المحاولات'),
                    ])->columns(2),

                Infolists\Components\Section::make('فترة الإتاحة')
                    ->schema([
                        Infolists\Components\TextEntry::make('available_from')
                            ->label('متاح من')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('فوري'),
                        Infolists\Components\TextEntry::make('available_until')
                            ->label('متاح حتى')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('دائم'),
                    ])->columns(2),

                Infolists\Components\Section::make('إحصائيات')
                    ->schema([
                        Infolists\Components\TextEntry::make('attempts_count')
                            ->label('عدد التقديمات')
                            ->getStateUsing(fn ($record) => $record->attempts()->count()),
                        Infolists\Components\TextEntry::make('passed_count')
                            ->label('عدد الناجحين')
                            ->getStateUsing(fn ($record) => $record->attempts()->where('passed', true)->count()),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y-m-d H:i'),
                    ])->columns(3),
            ]);
    }

    /**
     * Only show navigation if supervisor has assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedTeachers();
    }

    /**
     * Override query to filter quiz assignments by supervised teachers' resources.
     *
     * NOTE: quiz_assignments table does NOT have academy_id column,
     * so we skip parent::getEloquentQuery() and filter via assignable relationships.
     *
     * Quizzes can be assigned to:
     * - QuranCircle (has quran_teacher_id)
     * - QuranIndividualCircle (has quran_teacher_id)
     * - AcademicIndividualLesson (has academic_teacher_id)
     * - InteractiveCourse (has assigned_teacher_id)
     * - RecordedCourse (no direct teacher - skip for now)
     */
    public static function getEloquentQuery(): Builder
    {
        $quranTeacherIds = static::getAssignedQuranTeacherIds();
        $academicProfileIds = static::getAssignedAcademicTeacherProfileIds();
        $academy = static::getCurrentSupervisorAcademy();

        // Start fresh query - don't call parent because quiz_assignments has no academy_id
        $query = QuizAssignment::query()
            ->with(['quiz', 'assignable'])
            ->withCount('attempts');

        // Build a query that filters assignments by teacher ownership
        // Academy scoping is done through the assignable relationships
        $query->where(function (Builder $q) use ($quranTeacherIds, $academicProfileIds, $academy) {
            // Quran Circles - filter by quran_teacher_id and academy
            if (! empty($quranTeacherIds)) {
                $q->orWhere(function (Builder $sub) use ($quranTeacherIds, $academy) {
                    $sub->where('assignable_type', QuizAssignableType::QURAN_CIRCLE->value)
                        ->whereHasMorph('assignable', [\App\Models\QuranCircle::class], function (Builder $morph) use ($quranTeacherIds, $academy) {
                            $morph->whereIn('quran_teacher_id', $quranTeacherIds);
                            if ($academy) {
                                $morph->where('academy_id', $academy->id);
                            }
                        });
                });

                // Quran Individual Circles - filter by quran_teacher_id and academy
                $q->orWhere(function (Builder $sub) use ($quranTeacherIds, $academy) {
                    $sub->where('assignable_type', QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE->value)
                        ->whereHasMorph('assignable', [\App\Models\QuranIndividualCircle::class], function (Builder $morph) use ($quranTeacherIds, $academy) {
                            $morph->whereIn('quran_teacher_id', $quranTeacherIds);
                            if ($academy) {
                                $morph->where('academy_id', $academy->id);
                            }
                        });
                });
            }

            // Academic Individual Lessons - filter by academic_teacher_id (profile IDs) and academy
            if (! empty($academicProfileIds)) {
                $q->orWhere(function (Builder $sub) use ($academicProfileIds, $academy) {
                    $sub->where('assignable_type', QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON->value)
                        ->whereHasMorph('assignable', [\App\Models\AcademicIndividualLesson::class], function (Builder $morph) use ($academicProfileIds, $academy) {
                            $morph->whereIn('academic_teacher_id', $academicProfileIds);
                            if ($academy) {
                                $morph->where('academy_id', $academy->id);
                            }
                        });
                });

                // Interactive Courses - filter by assigned_teacher_id (profile IDs) and academy
                $q->orWhere(function (Builder $sub) use ($academicProfileIds, $academy) {
                    $sub->where('assignable_type', QuizAssignableType::INTERACTIVE_COURSE->value)
                        ->whereHasMorph('assignable', [\App\Models\InteractiveCourse::class], function (Builder $morph) use ($academicProfileIds, $academy) {
                            $morph->whereIn('assigned_teacher_id', $academicProfileIds);
                            if ($academy) {
                                $morph->where('academy_id', $academy->id);
                            }
                        });
                });
            }
        });

        // If no teachers assigned, return empty result
        if (empty($quranTeacherIds) && empty($academicProfileIds)) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoredQuizAssignments::route('/'),
            'view' => Pages\ViewMonitoredQuizAssignment::route('/{record}'),
        ];
    }
}
