<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\CourseReviewResource\Pages;
use App\Models\CourseReview;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseReviewResource extends BaseResource
{
    protected static ?string $model = CourseReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'تقييمات الدورات';

    protected static ?string $modelLabel = 'تقييم دورة';

    protected static ?string $pluralModelLabel = 'تقييمات الدورات';

    protected static ?string $navigationGroup = 'التقييمات والمراجعات';

    protected static ?int $navigationSort = 2;

    /**
     * Get the Eloquent query with soft deletes included
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات التقييم')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('الطالب')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('reviewable_type')
                            ->label('نوع الدورة')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'App\\Models\\RecordedCourse' => 'دورة مسجلة',
                                'App\\Models\\InteractiveCourse' => 'دورة تفاعلية',
                                default => $state,
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('rating')
                            ->label('التقييم')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->required()
                            ->suffix('/ 5'),

                        Forms\Components\Textarea::make('review')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewable.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewable_type')
                    ->label('نوع الدورة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'App\\Models\\RecordedCourse' => 'مسجلة',
                        'App\\Models\\InteractiveCourse' => 'تفاعلية',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'App\\Models\\RecordedCourse' => 'info',
                        'App\\Models\\InteractiveCourse' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->color('warning'),

                Tables\Columns\TextColumn::make('review')
                    ->label('التعليق')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->review),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status->label())
                    ->color(fn ($record) => $record->status->color())
                    ->icon(fn ($record) => $record->status->icon()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_approved')
                    ->label('حالة الاعتماد')
                    ->options([
                        '1' => ReviewStatus::APPROVED->label(),
                        '0' => ReviewStatus::PENDING->label(),
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

                Tables\Filters\SelectFilter::make('reviewable_type')
                    ->label('نوع الدورة')
                    ->options([
                        'App\\Models\\RecordedCourse' => 'دورة مسجلة',
                        'App\\Models\\InteractiveCourse' => 'دورة تفاعلية',
                    ]),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_approved)
                    ->action(fn ($record) => $record->approve()),

                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_approved)
                    ->action(fn ($record) => $record->reject()),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('اعتماد المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->approve();
                        }),

                    Tables\Actions\BulkAction::make('reject_selected')
                        ->label('رفض المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->reject();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
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
            'index' => Pages\ListCourseReviews::route('/'),
            'create' => Pages\CreateCourseReview::route('/create'),
            'view' => Pages\ViewCourseReview::route('/{record}'),
            'edit' => Pages\EditCourseReview::route('/{record}/edit'),
        ];
    }
}
