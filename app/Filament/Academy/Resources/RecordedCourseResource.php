<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\RecordedCourseResource\Pages;
use App\Filament\Academy\Resources\RecordedCourseResource\Pages\CreateRecordedCourse;
use App\Filament\Academy\Resources\RecordedCourseResource\Pages\EditRecordedCourse;
use App\Filament\Academy\Resources\RecordedCourseResource\Pages\ListRecordedCourses;
use App\Filament\Academy\Resources\RecordedCourseResource\Pages\ViewRecordedCourse;
use App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers\LessonsRelationManager;
use App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers\SectionsRelationManager;
use App\Filament\Shared\Resources\Courses\BaseRecordedCourseResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\RecordedCourse;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecordedCourseResource extends BaseRecordedCourseResource
{
    protected static ?int $navigationSort = 1;

    // ========================================
    // Panel-Specific Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Filter by current user's academy
        if (Auth::user()->academy_id) {
            $query->where('academy_id', Auth::user()->academy_id);
        }

        // If user is a teacher, only show their courses
        if (Auth::user()->isAcademicTeacher()) {
            $query->where('instructor_id', Auth::user()->academicTeacher->id);
        }

        return $query;
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                Action::make('publish')
                    ->label('نشر')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (RecordedCourse $record) {
                        $record->update(['is_published' => true]);
                    })
                    ->visible(fn (RecordedCourse $record): bool => ! $record->is_published),
                Action::make('unpublish')
                    ->label('إلغاء النشر')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (RecordedCourse $record) {
                        $record->update(['is_published' => false]);
                    })
                    ->visible(fn (RecordedCourse $record): bool => $record->is_published),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                BulkAction::make('publish')
                    ->label('نشر المحدد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_published' => true]);
                        });
                    }),

                BulkAction::make('unpublish')
                    ->label('إلغاء نشر المحدد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_published' => false]);
                        });
                    }),
            ]),
        ];
    }

    protected static function getAcademyFormField(): ?Select
    {
        // Academy panel doesn't show academy field - auto-scoped by tenant
        return null;
    }

    protected static function getInstructorFormField(): ?Select
    {
        return Select::make('instructor_id')
            ->label('المدرب')
            ->options(function () {
                $academyId = Auth::user()->academy_id;

                return AcademicTeacherProfile::where('academy_id', $academyId)
                    ->whereHas('user', fn ($q) => $q->where('active_status', true))
                    ->pluck('full_name', 'id');
            })
            ->searchable()
            ->required()
            ->placeholder('اختر المدرب');
    }

    protected static function getPanelSpecificFormFields(): array
    {
        return [
            static::getInstructorFormField(),

            // Academy-specific fields
            Section::make('ملاحظات')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Textarea::make('admin_notes')
                                ->label('ملاحظات الإدارة')
                                ->rows(3)
                                ->maxLength(1000)
                                ->helperText('ملاحظات داخلية للإدارة'),

                            Textarea::make('supervisor_notes')
                                ->label('ملاحظات المشرف')
                                ->rows(3)
                                ->maxLength(2000)
                                ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                        ]),
                ]),
        ];
    }

    protected static function getGradeLevelOptions(Get $get): array
    {
        $academyId = Auth::user()->academy_id;

        if (! $academyId) {
            return [];
        }

        return AcademicGradeLevel::where('academy_id', $academyId)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function getSubjectOptions(): array
    {
        $academyId = Auth::user()->academy_id;

        return AcademicSubject::where('academy_id', $academyId)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add instructor column for academy panel
        array_splice($columns, 2, 0, [
            TextColumn::make('instructor.user.name')
                ->label('المدرب')
                ->searchable()
                ->sortable(),
        ]);

        // Add published status badge
        $columns[] = IconColumn::make('is_published')
            ->label('منشور')
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle');

        return $columns;
    }

    // ========================================
    // Resource Relations & Pages
    // ========================================

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
            LessonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecordedCourses::route('/'),
            'create' => CreateRecordedCourse::route('/create'),
            'edit' => EditRecordedCourse::route('/{record}/edit'),
            'view' => ViewRecordedCourse::route('/{record}'),
        ];
    }
}
