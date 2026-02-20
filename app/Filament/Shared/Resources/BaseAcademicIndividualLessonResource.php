<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use App\Models\AcademicIndividualLesson;
use App\Services\AcademyContextService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Academic Individual Lesson Resource
 *
 * Shared functionality for SuperAdmin and AcademicTeacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseAcademicIndividualLessonResource extends Resource
{
    protected static ?string $model = AcademicIndividualLesson::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $modelLabel = 'درس فردي';

    protected static ?string $pluralModelLabel = 'الدروس الفردية';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     */
    abstract protected static function getTableBulkActions(): array;

    /**
     * Get the lesson info form section (teacher/student selection differs by panel).
     */
    abstract protected static function getLessonInfoFormSection(): Section;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Schema $form): Schema
    {
        $schema = [];

        // Add lesson info section (panel-specific)
        $schema[] = static::getLessonInfoFormSection();

        // Add session settings section
        $schema[] = static::getSessionSettingsFormSection();

        // Add learning objectives section
        $schema[] = static::getLearningObjectivesFormSection();

        // Add additional sections from child classes
        $schema = array_merge($schema, static::getAdditionalFormSections());

        return $form->components($schema);
    }

    /**
     * Session settings section - shared across panels.
     */
    protected static function getSessionSettingsFormSection(): Section
    {
        return Section::make('إعدادات الجلسات')
            ->schema([
                TextInput::make('total_sessions')
                    ->label('عدد الجلسات الكلي')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                TextInput::make('default_duration_minutes')
                    ->label('مدة الجلسة (بالدقائق)')
                    ->numeric()
                    ->default(60)
                    ->minValue(15)
                    ->maxValue(180)
                    ->required(),
            ])
            ->columns(2);
    }

    /**
     * Learning objectives section - shared across panels.
     */
    protected static function getLearningObjectivesFormSection(): Section
    {
        return Section::make('أهداف التعلم والمواد')
            ->schema([
                Repeater::make('learning_objectives')
                    ->label('أهداف التعلم')
                    ->schema([
                        TextInput::make('objective')
                            ->label('الهدف')
                            ->required(),
                    ])
                    ->addActionLabel('إضافة هدف')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Get additional form sections - override in child classes.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [];
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(static::getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions())
            ->emptyStateHeading('لا توجد دروس فردية')
            ->emptyStateDescription('لم يتم إنشاء أي دروس فردية بعد.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('lesson_code')
                ->label('رمز الدرس')
                ->searchable()
                ->sortable()
                ->copyable(),

            TextColumn::make('name')
                ->label('اسم الدرس')
                ->searchable()
                ->limit(25),

            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable()
                ->toggleable(),

            TextColumn::make('academicSubject.name')
                ->label('المادة')
                ->searchable()
                ->toggleable(),

            TextColumn::make('academicGradeLevel.name')
                ->label('المستوى')
                ->searchable()
                ->toggleable(),

            TextColumn::make('sessions_completed')
                ->label('الجلسات')
                ->suffix(fn (AcademicIndividualLesson $record): string => " / {$record->total_sessions}")
                ->sortable()
                ->toggleable(),

            TextColumn::make('progress_percentage')
                ->label('التقدم')
                ->suffix('%')
                ->sortable()
                ->color(fn ($state): string => match (true) {
                    (float) $state >= 80 => 'success',
                    (float) $state >= 50 => 'warning',
                    default => 'danger',
                })
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get the table filters - shared across panels.
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('academic_subject_id')
                ->label('المادة')
                ->relationship('academicSubject', 'name')
                ->searchable()
                ->preload(),

            SelectFilter::make('academic_grade_level_id')
                ->label('المستوى الدراسي')
                ->relationship('academicGradeLevel', 'name')
                ->searchable()
                ->preload(),
        ];
    }

    // ========================================
    // Academy Context Methods
    // ========================================

    protected static function isViewingAllAcademies(): bool
    {
        if (Filament::getTenant() !== null) {
            return false;
        }

        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() === null;
    }

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    protected static function getAcademyColumn(): TextColumn
    {
        $academyPath = static::getAcademyRelationshipPath();

        return TextColumn::make($academyPath.'.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(static::isViewingAllAcademies())
            ->placeholder('غير محدد');
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'student',
                'academicSubject',
                'academicGradeLevel',
                'academy',
                'academicTeacher.user',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
