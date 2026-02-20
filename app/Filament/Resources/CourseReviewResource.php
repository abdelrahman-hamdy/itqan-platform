<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\CourseReviewResource\Pages\CreateCourseReview;
use App\Filament\Resources\CourseReviewResource\Pages\EditCourseReview;
use App\Filament\Resources\CourseReviewResource\Pages\ListCourseReviews;
use App\Filament\Resources\CourseReviewResource\Pages\ViewCourseReview;
use App\Models\CourseReview;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseReviewResource extends BaseResource
{
    protected static ?string $model = CourseReview::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'تقييمات الدورات';

    protected static ?string $modelLabel = 'تقييم دورة';

    protected static ?string $pluralModelLabel = 'تقييمات الدورات';

    protected static string|\UnitEnum|null $navigationGroup = 'التقييمات والمراجعات';

    protected static ?int $navigationSort = 2;

    /**
     * Get the Eloquent query with soft deletes included
     */
    public static function getEloquentQuery(): Builder
    {
        // Include soft-deleted records for admin management
        return parent::getEloquentQuery()
            ->with(['user', 'reviewable'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Get the navigation badge showing pending reviews count
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_approved', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات التقييم')
                    ->schema([
                        Select::make('user_id')
                            ->label('الطالب')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        TextInput::make('reviewable_type')
                            ->label('نوع الدورة')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'App\\Models\\RecordedCourse' => 'دورة مسجلة',
                                'App\\Models\\InteractiveCourse' => 'دورة تفاعلية',
                                default => $state,
                            })
                            ->disabled(),

                        TextInput::make('rating')
                            ->label('التقييم')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->required()
                            ->suffix('/ 5'),

                        Textarea::make('review')
                            ->label('التعليق')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('حالة الموافقة')
                    ->schema([
                        Toggle::make('is_approved')
                            ->label('معتمد')
                            ->helperText('تفعيل هذا الخيار سينشر التقييم ليكون مرئياً للجميع'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reviewable.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status->label())
                    ->color(fn ($record) => $record->status->color())
                    ->icon(fn ($record) => $record->status->icon()),

                TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('reviewable_type')
                    ->label('نوع الدورة')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'App\\Models\\RecordedCourse' => 'مسجلة',
                        'App\\Models\\InteractiveCourse' => 'تفاعلية',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'App\\Models\\RecordedCourse' => 'info',
                        'App\\Models\\InteractiveCourse' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('review')
                    ->label('التعليق')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->review)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('حالة الاعتماد')
                    ->options([
                        '1' => ReviewStatus::APPROVED->label(),
                        '0' => ReviewStatus::PENDING->label(),
                    ]),

                SelectFilter::make('rating')
                    ->label('التقييم')
                    ->options([
                        '5' => '5 نجوم',
                        '4' => '4 نجوم',
                        '3' => '3 نجوم',
                        '2' => '2 نجوم',
                        '1' => '1 نجمة',
                    ]),

                SelectFilter::make('reviewable_type')
                    ->label('نوع الدورة')
                    ->options([
                        'App\\Models\\RecordedCourse' => 'دورة مسجلة',
                        'App\\Models\\InteractiveCourse' => 'دورة تفاعلية',
                    ]),

                TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    EditAction::make()->label('تعديل'),
                    Action::make('approve')
                        ->label('اعتماد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => ! $record->is_approved)
                        ->action(fn ($record) => $record->approve()),
                    Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->is_approved)
                        ->action(fn ($record) => $record->reject()),
                    DeleteAction::make()->label('حذف'),
                    RestoreAction::make()->label(__('filament.actions.restore')),
                    ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_selected')
                        ->label('اعتماد المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->approve();
                        }),

                    BulkAction::make('reject_selected')
                        ->label('رفض المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->reject();
                        }),

                    DeleteBulkAction::make(),
                    RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseReviews::route('/'),
            'create' => CreateCourseReview::route('/create'),
            'view' => ViewCourseReview::route('/{record}'),
            'edit' => EditCourseReview::route('/{record}/edit'),
        ];
    }
}
