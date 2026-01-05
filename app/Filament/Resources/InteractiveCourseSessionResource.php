<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseSessionResource\Pages;
use App\Filament\Resources\InteractiveCourseSessionResource\RelationManagers;
use App\Models\InteractiveCourseSession;
use App\Services\AcademyContextService;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InteractiveCourseSessionResource extends BaseResource
{
    protected static ?string $model = InteractiveCourseSession::class;

    /**
     * Academy relationship path for BaseResource.
     * InteractiveCourseSession gets academy through course relationship.
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'course.academy';
    }

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'جلسات الدورات التفاعلية';

    protected static ?string $modelLabel = 'جلسة دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'جلسات الدورات التفاعلية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'course',
                'course.assignedTeacher',
                'course.assignedTeacher.user',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title')
                            ->label('الدورة')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),

                        Forms\Components\TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('session_number')
                            ->label('رقم الجلسة')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText('رقم الجلسة ضمن الدورة'),

                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options(SessionStatus::options())
                            ->default(SessionStatus::SCHEDULED->value)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->timezone(fn () => AcademyContextService::getTimezone())
                                    ->displayFormat('Y-m-d H:i'),

                                Forms\Components\Select::make('duration_minutes')
                                    ->label('مدة الجلسة')
                                    ->options(SessionDuration::options())
                                    ->default(60)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('محتوى الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->helperText('أهداف ومحتوى الجلسة')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('الواجبات')
                    ->schema([
                        Forms\Components\Toggle::make('homework_assigned')
                            ->label('يوجد واجب منزلي')
                            ->default(false)
                            ->live(),

                        Forms\Components\Textarea::make('homework_description')
                            ->label('وصف الواجب')
                            ->rows(3)
                            ->visible(fn ($get) => $get('homework_assigned'))
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('homework_file')
                            ->label('ملف الواجب')
                            ->directory('interactive-course-homework')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'])
                            ->visible(fn ($get) => $get('homework_assigned')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('session_notes')
                                    ->label('ملاحظات الجلسة')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('ملاحظات داخلية للإدارة'),

                                Forms\Components\Textarea::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->rows(3)
                                    ->maxLength(2000)
                                    ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('course.title')
                    ->label('الدورة')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('session_number')
                    ->label('رقم الجلسة')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('موعد الجلسة')
                    ->dateTime()
                    ->timezone(fn () => AcademyContextService::getTimezone())
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(SessionStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof SessionStatus) {
                            return $state->label();
                        }
                        $statusEnum = SessionStatus::tryFrom($state);
                        return $statusEnum?->label() ?? $state;
                    }),

                Tables\Columns\IconColumn::make('homework_assigned')
                    ->label('واجب')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('course_id')
                    ->label('الدورة')
                    ->relationship('course', 'title')
                    ->searchable(),

                Tables\Filters\Filter::make('scheduled_today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('scheduled_this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->thisWeek()),

                Tables\Filters\TernaryFilter::make('homework_assigned')
                    ->label('الواجبات')
                    ->placeholder('الكل')
                    ->trueLabel('بها واجبات')
                    ->falseLabel('بدون واجبات'),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                    Tables\Actions\Action::make('start_session')
                        ->label('بدء الجلسة')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (InteractiveCourseSession $record): bool => in_array(
                            $record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status),
                            [SessionStatus::SCHEDULED, SessionStatus::READY]
                        ))
                        ->action(fn (InteractiveCourseSession $record) => $record->markAsOngoing()),

                    Tables\Actions\Action::make('complete_session')
                        ->label('إنهاء الجلسة')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (InteractiveCourseSession $record): bool =>
                            ($record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status)) === SessionStatus::ONGOING
                        )
                        ->action(fn (InteractiveCourseSession $record) => $record->markAsCompleted()),

                    Tables\Actions\Action::make('cancel_session')
                        ->label('إلغاء الجلسة')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn (InteractiveCourseSession $record): bool => in_array(
                            $record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status),
                            [SessionStatus::SCHEDULED, SessionStatus::READY]
                        ))
                        ->requiresConfirmation()
                        ->action(fn (InteractiveCourseSession $record) => $record->markAsCancelled('ألغيت بواسطة المدير', auth()->user(), 'admin')),
                    Tables\Actions\Action::make('join_meeting')
                        ->label('دخول الاجتماع')
                        ->icon('heroicon-o-video-camera')
                        ->url(fn (InteractiveCourseSession $record): string => $record->meeting_link ?? '#')
                        ->openUrlInNewTab()
                        ->visible(fn (InteractiveCourseSession $record): bool => !empty($record->meeting_link)),
                    Tables\Actions\RestoreAction::make()
                        ->label(__('filament.actions.restore')),
                    Tables\Actions\ForceDeleteAction::make()
                        ->label(__('filament.actions.force_delete')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveCourseSessions::route('/'),
            'create' => Pages\CreateInteractiveCourseSession::route('/create'),
            'view' => Pages\ViewInteractiveCourseSession::route('/{record}'),
            'edit' => Pages\EditInteractiveCourseSession::route('/{record}/edit'),
        ];
    }
}
