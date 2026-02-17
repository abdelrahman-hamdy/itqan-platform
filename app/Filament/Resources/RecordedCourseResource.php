<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordedCourseResource\Pages;
use App\Filament\Shared\Resources\Courses\BaseRecordedCourseResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\RecordedCourse;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Get;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecordedCourseResource extends BaseRecordedCourseResource
{
    // ========================================
    // Panel-Specific Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Admin sees all academies, but can filter by academy context
        $query->withoutGlobalScopes([SoftDeletingScope::class]);

        if (AcademyContextService::hasAcademySelected()) {
            $query->where('academy_id', AcademyContextService::getCurrentAcademyId());
        }

        return $query;
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ReplicateAction::make()
                ->label('نسخ الدورة')
                ->form([
                    Forms\Components\Toggle::make('copy_sections')
                        ->label('نسخ الأقسام والدروس')
                        ->default(true)
                        ->helperText('نسخ جميع الأقسام والدروس مع الدورة'),
                ])
                ->beforeReplicaSaved(function (RecordedCourse $replica): void {
                    $replica->title = $replica->title.' (نسخة)';
                    $replica->is_published = false;
                    $replica->slug = $replica->slug.'-copy-'.time();
                })
                ->afterReplicaSaved(function (RecordedCourse $original, RecordedCourse $replica, array $data): void {
                    if ($data['copy_sections'] ?? true) {
                        foreach ($original->sections as $section) {
                            $newSection = $section->replicate(['recorded_course_id']);
                            $newSection->recorded_course_id = $replica->id;
                            $newSection->save();

                            foreach ($section->lessons as $lesson) {
                                $newLesson = $lesson->replicate(['course_section_id']);
                                $newLesson->course_section_id = $newSection->id;
                                $newLesson->save();
                            }
                        }
                    }
                })
                ->successNotificationTitle('تم نسخ الدورة بنجاح'),
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make()
                ->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

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

    protected static function getAcademyFormField(): ?Forms\Components\Select
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        return Forms\Components\Select::make('academy_id')
            ->label('الأكاديمية')
            ->options(Academy::pluck('name', 'id'))
            ->default($currentAcademy?->id)
            ->disabled($currentAcademy !== null)
            ->required()
            ->live();
    }

    protected static function getInstructorFormField(): ?Forms\Components\Select
    {
        // Admin panel doesn't require instructor field
        return null;
    }

    protected static function getPanelSpecificFormFields(): array
    {
        return [
            static::getAcademyFormField(),

            // Admin-specific fields
            Forms\Components\Section::make('الوسائط')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('thumbnail_url')
                        ->label('صورة مصغرة')
                        ->image()
                        ->collection('thumbnails')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(10240) // 10MB max size
                        ->helperText('أقصى حجم: 10 ميجابايت')
                        ->nullable(),

                    SpatieMediaLibraryFileUpload::make('materials')
                        ->label('مواد الكورس')
                        ->multiple()
                        ->collection('materials')
                        ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                        ->maxSize(51200) // 50MB max size
                        ->helperText('أقصى حجم: 50 ميجابايت لكل ملف'),
                ])->columns(2),

            Forms\Components\Section::make('ملاحظات')
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
                ]),
        ];
    }

    protected static function getGradeLevelOptions(Get $get): array
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        $academyId = $get('academy_id') ?? $currentAcademy?->id;

        if (! $academyId) {
            return [];
        }

        return AcademicGradeLevel::where('academy_id', $academyId)
            ->where('is_active', true)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function getSubjectOptions(): array
    {
        $academyId = AcademyContextService::getCurrentAcademyId();

        return $academyId
            ? AcademicSubject::where('academy_id', $academyId)->where('is_active', true)->pluck('name', 'id')->toArray()
            : [];
    }

    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add academy column when viewing all academies
        if (static::isViewingAllAcademies()) {
            array_splice($columns, 1, 0, [
                static::getAcademyColumn(),
            ]);
        }

        return $columns;
    }

    // ========================================
    // Resource Pages
    // ========================================

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordedCourses::route('/'),
            'create' => Pages\CreateRecordedCourse::route('/create'),
            'edit' => Pages\EditRecordedCourse::route('/{record}/edit'),
            'view' => Pages\ViewRecordedCourse::route('/{record}'),
        ];
    }
}
