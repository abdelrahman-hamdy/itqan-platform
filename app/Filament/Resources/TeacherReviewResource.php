<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\TeacherReviewResource\Pages;
use App\Filament\Shared\Resources\BaseTeacherReviewResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Teacher Review Resource for SuperAdmin Panel
 *
 * Full CRUD access with approval workflow and soft delete support.
 * Extends BaseTeacherReviewResource for shared form/table definitions.
 */
class TeacherReviewResource extends BaseTeacherReviewResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'إعدادات المعلمين';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all reviews, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    /**
     * Full table actions for SuperAdmin with approval workflow.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('approve')
                ->label('اعتماد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => ! $record->is_approved && ! $record->trashed())
                ->action(fn ($record) => $record->approve()),

            Tables\Actions\Action::make('reject')
                ->label('رفض')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => $record->is_approved && ! $record->trashed())
                ->action(fn ($record) => $record->reject()),

            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make()
                ->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('اعتماد المحدد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($records) => $records->each->approve()),

                Tables\Actions\BulkAction::make('reject_selected')
                    ->label('رفض المحدد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($records) => $records->each->reject()),

                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    protected static function canApproveReviews(): bool
    {
        return true;
    }

    // ========================================
    // Navigation Badge
    // ========================================

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_approved', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ========================================
    // Form Override (SuperAdmin-specific)
    // ========================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التقييم')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('reviewable_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => static::formatTeacherType($state))
                            ->disabled(),

                        Forms\Components\TextInput::make('rating')
                            ->label('التقييم')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->required()
                            ->suffix('/ 5'),

                        Forms\Components\Textarea::make('comment')
                            ->label('التعليق')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('حالة الموافقة')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('معتمد')
                            ->helperText('تفعيل هذا الخيار سينشر التقييم ليكون مرئياً للجميع'),
                    ]),
            ]);
    }

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('reviewable.full_name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            TextColumn::make('reviewable_type')
                ->label('نوع المعلم')
                ->formatStateUsing(fn ($state) => static::formatTeacherTypeShort($state))
                ->badge()
                ->color(fn ($state) => static::getTeacherTypeColor($state)),

            TextColumn::make('rating')
                ->label('التقييم')
                ->formatStateUsing(fn ($state) => number_format($state, 1).'/5')
                ->color('warning'),

            TextColumn::make('comment')
                ->label('التعليق')
                ->limit(50)
                ->tooltip(fn ($record) => $record->comment),

            TextColumn::make('status')
                ->label('الحالة')
                ->badge()
                ->formatStateUsing(fn ($record) => $record->status->label())
                ->color(fn ($record) => $record->status->color())
                ->icon(fn ($record) => $record->status->icon()),

            TextColumn::make('created_at')
                ->label('تاريخ التقييم')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
        ];
    }

    // ========================================
    // Table Filters Override (with TrashedFilter)
    // ========================================

    protected static function getTableFilters(): array
    {
        // Filters panel removed - using tabs only for filtering
        return [];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherReviews::route('/'),
            'create' => Pages\CreateTeacherReview::route('/create'),
            'view' => Pages\ViewTeacherReview::route('/{record}'),
            'edit' => Pages\EditTeacherReview::route('/{record}/edit'),
        ];
    }
}
