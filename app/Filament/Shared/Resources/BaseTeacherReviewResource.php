<?php

namespace App\Filament\Shared\Resources;

use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Teacher Review Resource
 *
 * Shared functionality for SuperAdmin and Supervisor panels.
 * Child classes must implement query scoping and authorization methods.
 *
 * Pattern: Shared form/table definitions with abstract methods for panel-specific behavior.
 */
abstract class BaseTeacherReviewResource extends Resource
{
    protected static ?string $model = TeacherReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'تقييمات المعلمين';

    protected static ?string $modelLabel = 'تقييم معلم';

    protected static ?string $pluralModelLabel = 'تقييمات المعلمين';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     * SuperAdmin: may include all reviews or filter by academy
     * Supervisor: filters by assigned teacher profiles
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     * SuperAdmin: approve, reject, edit, delete actions
     * Supervisor: view-only action
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     * SuperAdmin: bulk approve, reject, delete
     * Supervisor: no bulk actions
     */
    abstract protected static function getTableBulkActions(): array;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    /**
     * Can create new reviews.
     * Default: false - reviews typically come from students.
     * Override in child class if needed.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Can edit reviews.
     * Default: false - override in child class (e.g., SuperAdmin).
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Can delete reviews.
     * Default: false - override in child class (e.g., SuperAdmin).
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التقييم')
                    ->schema([
                        Forms\Components\TextInput::make('reviewable.user.name')
                            ->label('المعلم')
                            ->disabled(),

                        Forms\Components\TextInput::make('reviewable_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => static::formatTeacherType($state))
                            ->disabled(),

                        Forms\Components\TextInput::make('student.full_name')
                            ->label('الطالب')
                            ->disabled(),

                        Forms\Components\TextInput::make('rating')
                            ->label('التقييم')
                            ->suffix('/ 5')
                            ->disabled(),

                        Forms\Components\Toggle::make('is_approved')
                            ->label('تم الموافقة')
                            ->disabled(fn () => ! static::canApproveReviews()),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('تاريخ الموافقة')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('نص التقييم')
                    ->schema([
                        Forms\Components\Textarea::make('comment')
                            ->label('التعليق')
                            ->rows(4)
                            ->disabled(fn () => ! static::canApproveReviews()),
                    ]),
            ]);
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
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('reviewable.user.name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            TextColumn::make('reviewable_type')
                ->label('نوع المعلم')
                ->formatStateUsing(fn ($state) => static::formatTeacherTypeShort($state))
                ->badge()
                ->color(fn ($state) => static::getTeacherTypeColor($state)),

            TextColumn::make('student.full_name')
                ->label('الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('rating')
                ->label('التقييم')
                ->formatStateUsing(function ($state) {
                    $rating = max(0, min(5, (int) round($state)));

                    return str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
                })
                ->sortable(),

            TextColumn::make('comment')
                ->label('التعليق')
                ->limit(50)
                ->wrap(),

            Tables\Columns\IconColumn::make('is_approved')
                ->label('موافق عليه')
                ->boolean(),

            TextColumn::make('created_at')
                ->label('التاريخ')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
        ];
    }

    /**
     * Get the table filters - shared across panels.
     */
    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('reviewable_type')
                ->label('نوع المعلم')
                ->options([
                    QuranTeacherProfile::class => 'معلم قرآن',
                    AcademicTeacherProfile::class => 'معلم أكاديمي',
                ]),

            Tables\Filters\SelectFilter::make('rating')
                ->label('التقييم')
                ->options([
                    '5' => '5 نجوم',
                    '4' => '4 نجوم',
                    '3' => '3 نجوم',
                    '2' => '2 نجوم',
                    '1' => '1 نجمة',
                ]),

            Tables\Filters\TernaryFilter::make('is_approved')
                ->label('الموافقة')
                ->trueLabel('موافق عليه')
                ->falseLabel('في الانتظار'),
        ];
    }

    // ========================================
    // Utility Methods
    // ========================================

    /**
     * Format teacher type for display (full label).
     */
    protected static function formatTeacherType(?string $type): string
    {
        return match ($type) {
            QuranTeacherProfile::class => 'معلم قرآن',
            AcademicTeacherProfile::class => 'معلم أكاديمي',
            default => $type ?? '-',
        };
    }

    /**
     * Format teacher type for table display (short label).
     */
    protected static function formatTeacherTypeShort(?string $type): string
    {
        return match ($type) {
            QuranTeacherProfile::class => 'قرآن',
            AcademicTeacherProfile::class => 'أكاديمي',
            default => $type ?? '-',
        };
    }

    /**
     * Get color for teacher type badge.
     */
    protected static function getTeacherTypeColor(?string $type): string
    {
        return match ($type) {
            QuranTeacherProfile::class => 'success',
            AcademicTeacherProfile::class => 'warning',
            default => 'gray',
        };
    }

    /**
     * Can this panel approve/reject reviews.
     * Override in child class if needed.
     */
    protected static function canApproveReviews(): bool
    {
        return false; // Default: no approval. SuperAdmin can override.
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['reviewable.user', 'student']);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
