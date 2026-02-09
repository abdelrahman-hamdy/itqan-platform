<?php

namespace App\Filament\Resources;

use App\Enums\UserType;
use App\Filament\Resources\AcademicIndividualLessonResource\Pages;
use App\Filament\Shared\Resources\BaseAcademicIndividualLessonResource;
use App\Models\AcademicIndividualLesson;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Academic Individual Lesson Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseAcademicIndividualLessonResource for shared form/table definitions.
 */
class AcademicIndividualLessonResource extends BaseAcademicIndividualLessonResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'الدروس الفردية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 5;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all lessons, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Full lesson info section with teacher and student selection.
     */
    protected static function getLessonInfoFormSection(): Section
    {
        return Section::make('معلومات الدرس الأساسية')
            ->schema([
                Forms\Components\TextInput::make('lesson_code')
                    ->label('رمز الدرس')
                    ->disabled(),

                Forms\Components\TextInput::make('name')
                    ->label('اسم الدرس')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('وصف الدرس')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('academic_teacher_id')
                    ->relationship('academicTeacher', 'id')
                    ->label('المعلم')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name)
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->options(function () {
                        return \App\Models\User::where('user_type', UserType::STUDENT->value)
                            ->with('studentProfile')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->studentProfile?->display_name ?? $user->name];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('academic_subject_id')
                    ->relationship('academicSubject', 'name')
                    ->label('المادة')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('academic_grade_level_id')
                    ->relationship('academicGradeLevel', 'name')
                    ->label('المستوى الدراسي')
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->columns(2);
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('view_sessions')
                    ->label('الجلسات')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (AcademicIndividualLesson $record): string => AcademicSessionResource::getUrl('index', [
                        'tableFilters[academic_individual_lesson_id][value]' => $record->id,
                    ])),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
                Tables\Actions\RestoreAction::make()
                    ->label('استعادة'),
                Tables\Actions\ForceDeleteAction::make()
                    ->label('حذف نهائي'),
            ]),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections (SuperAdmin-specific)
    // ========================================

    /**
     * Add timing and notes sections for SuperAdmin.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            static::getTimingFormSection(),
            static::getNotesFormSection(),
        ];
    }

    /**
     * Timing section - SuperAdmin only.
     */
    protected static function getTimingFormSection(): Section
    {
        return Section::make('التوقيت')
            ->schema([
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('تاريخ البدء')
                    ->timezone(AcademyContextService::getTimezone()),

                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('تاريخ الإكمال')
                    ->timezone(AcademyContextService::getTimezone()),

                Forms\Components\DateTimePicker::make('last_session_at')
                    ->label('آخر جلسة')
                    ->timezone(AcademyContextService::getTimezone())
                    ->disabled(),
            ])
            ->columns(3);
    }

    /**
     * Notes section - SuperAdmin only.
     */
    protected static function getNotesFormSection(): Section
    {
        return Section::make('ملاحظات')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('ملاحظات داخلية للإدارة'),
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(3)
                            ->maxLength(2000)
                            ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                    ]),
            ]);
    }

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Add teacher and academy columns for SuperAdmin.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add teacher column after lesson name
        $teacherColumn = TextColumn::make('academicTeacher.user.name')
            ->label('المعلم')
            ->searchable()
            ->sortable();

        // Add academy column
        $academyColumn = TextColumn::make('academy.name')
            ->label('الأكاديمية')
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        // Insert columns at appropriate positions
        $result = [];
        foreach ($columns as $column) {
            $result[] = $column;

            // Add teacher column after student
            if ($column->getName() === 'student.name') {
                array_splice($result, count($result) - 1, 0, [$teacherColumn]);
            }
        }

        // Add academy column at the end
        $result[] = $academyColumn;

        return $result;
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with teacher, academy, and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Tables\Filters\SelectFilter::make('academic_teacher_id')
                ->label('المعلم')
                ->relationship('academicTeacher.user', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('academy_id')
                ->label('الأكاديمية')
                ->relationship('academy', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicIndividualLessons::route('/'),
            'create' => Pages\CreateAcademicIndividualLesson::route('/create'),
            'view' => Pages\ViewAcademicIndividualLesson::route('/{record}'),
            'edit' => Pages\EditAcademicIndividualLesson::route('/{record}/edit'),
        ];
    }
}
