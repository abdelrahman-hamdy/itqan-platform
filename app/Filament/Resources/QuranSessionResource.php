<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranSessionResource\Pages;
use App\Models\QuranSession;
use App\Enums\SessionStatus;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\SubscriptionStatus;

/**
 * Quran Session Resource for Admin Panel
 *
 * Allows admins to view and manage all Quran sessions.
 */
class QuranSessionResource extends Resource
{
    protected static ?string $model = QuranSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'جلسات القرآن';

    protected static ?string $modelLabel = 'جلسة قرآن';

    protected static ?string $pluralModelLabel = 'جلسات القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled(),

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'individual' => 'فردية',
                                'group' => 'جماعية',
                                'trial' => 'تجريبية',
                                'makeup' => 'تعويضية',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(SessionStatus::options())
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('المعلم والحلقة')
                    ->schema([
                        Forms\Components\Select::make('quran_teacher_id')
                            ->relationship('quranTeacher', 'id')
                            ->label('المعلم')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name)
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('circle_id')
                            ->relationship('circle', 'name')
                            ->label('الحلقة الجماعية')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('session_type') === 'group'),

                        Forms\Components\Select::make('individual_circle_id')
                            ->relationship('individualCircle', 'id')
                            ->label('الحلقة الفردية')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->student?->name ?? 'حلقة فردية')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('session_type') === 'individual'),

                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('session_type') === 'individual'),
                    ])->columns(2),

                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->timezone(AcademyContextService::getTimezone())
                            ->required(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('المدة (دقيقة)')
                            ->numeric()
                            ->default(60)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('تتبع التقدم')
                    ->schema([
                        Forms\Components\TextInput::make('current_surah')
                            ->label('السورة الحالية'),

                        Forms\Components\TextInput::make('current_page')
                            ->label('الصفحة الحالية')
                            ->numeric(),

                        Forms\Components\Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(3),

                        Forms\Components\Textarea::make('homework_details')
                            ->label('تفاصيل الواجب')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('quranTeacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->placeholder('جلسة فردية')
                    ->toggleable(),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->placeholder('جماعية')
                    ->toggleable(),

                BadgeColumn::make('session_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'makeup' => 'تعويضية',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                        'warning' => 'trial',
                        'info' => 'makeup',
                    ]),

                TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('Y-m-d H:i')
                    ->timezone(AcademyContextService::getTimezone())
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' د')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof SessionStatus) {
                            return $state->label();
                        }
                        $status = SessionStatus::tryFrom($state);
                        return $status?->label() ?? $state;
                    })
                    ->colors(SessionStatus::colorOptions()),

                TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
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

                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'makeup' => 'تعويضية',
                    ]),

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->relationship('quranTeacher.user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),

                Tables\Filters\Filter::make('completed')
                    ->label('المكتملة')
                    ->query(fn (Builder $query): Builder => $query->where('status', SessionStatus::COMPLETED->value)),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranSessions::route('/'),
            'create' => Pages\CreateQuranSession::route('/create'),
            'view' => Pages\ViewQuranSession::route('/{record}'),
            'edit' => Pages\EditQuranSession::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['quranTeacher.user', 'circle', 'student', 'individualCircle', 'academy']);
    }
}
