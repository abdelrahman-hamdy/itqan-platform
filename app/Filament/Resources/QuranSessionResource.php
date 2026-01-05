<?php

namespace App\Filament\Resources;

use App\Enums\QuranSurah;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Resources\QuranSessionResource\Pages;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Quran Session Resource for Admin Panel
 *
 * Allows admins to view and manage all Quran sessions.
 */
class QuranSessionResource extends BaseResource
{
    protected static ?string $model = QuranSession::class;

    /**
     * Academy relationship path for BaseResource.
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

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

                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'individual' => 'فردية',
                                'group' => 'جماعية',
                                'trial' => 'تجريبية',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(SessionStatus::options())
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('المعلم والحلقة')
                    ->schema([
                        Forms\Components\Select::make('quran_teacher_id')
                            ->relationship('quranTeacher', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: 'معلم #' . $record->id
                            )
                            ->label('المعلم')
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
                            ->relationship('individualCircle', 'id', fn ($query) => $query->with(['student', 'quranTeacher']))
                            ->label('الحلقة الفردية')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $studentName = $record->student
                                    ? trim(($record->student->first_name ?? '') . ' ' . ($record->student->last_name ?? ''))
                                    : 'طالب غير محدد';
                                $teacherName = $record->quranTeacher
                                    ? trim(($record->quranTeacher->first_name ?? '') . ' ' . ($record->quranTeacher->last_name ?? ''))
                                    : 'معلم غير محدد';
                                return $studentName . ' - ' . $teacherName;
                            })
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

                        Forms\Components\Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(60)
                            ->required(),
                    ])->columns(2),

                Section::make('محتوى الجلسة')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->helperText('أهداف ومحتوى الجلسة')
                            ->rows(3),

                        Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(4),
                    ])->columns(2),

                Section::make('الواجب المنزلي')
                    ->schema([
                        // New Memorization Section
                        Toggle::make('sessionHomework.has_new_memorization')
                            ->label('حفظ جديد')
                            ->live()
                            ->default(false),

                        Grid::make(2)
                            ->schema([
                                Select::make('sessionHomework.new_memorization_surah')
                                    ->label('سورة الحفظ الجديد')
                                    ->options(QuranSurah::getAllSurahs())
                                    ->searchable()
                                    ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),

                                TextInput::make('sessionHomework.new_memorization_pages')
                                    ->label('عدد الأوجه')
                                    ->numeric()
                                    ->step(0.5)
                                    ->minValue(0.5)
                                    ->maxValue(10)
                                    ->suffix('وجه')
                                    ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),
                            ])
                            ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),

                        // Review Section
                        Toggle::make('sessionHomework.has_review')
                            ->label('مراجعة')
                            ->live()
                            ->default(false),

                        Grid::make(2)
                            ->schema([
                                Select::make('sessionHomework.review_surah')
                                    ->label('سورة المراجعة')
                                    ->options(QuranSurah::getAllSurahs())
                                    ->searchable()
                                    ->visible(fn ($get) => $get('sessionHomework.has_review')),

                                TextInput::make('sessionHomework.review_pages')
                                    ->label('عدد أوجه المراجعة')
                                    ->numeric()
                                    ->step(0.5)
                                    ->minValue(0.5)
                                    ->maxValue(20)
                                    ->suffix('وجه')
                                    ->visible(fn ($get) => $get('sessionHomework.has_review')),
                            ])
                            ->visible(fn ($get) => $get('sessionHomework.has_review')),

                        // Comprehensive Review Section
                        Toggle::make('sessionHomework.has_comprehensive_review')
                            ->label('مراجعة شاملة')
                            ->live()
                            ->default(false),

                        CheckboxList::make('sessionHomework.comprehensive_review_surahs')
                            ->label('سور المراجعة الشاملة')
                            ->options(QuranSurah::getAllSurahs())
                            ->searchable()
                            ->columns(3)
                            ->visible(fn ($get) => $get('sessionHomework.has_comprehensive_review')),

                        Textarea::make('sessionHomework.additional_instructions')
                            ->label('تعليمات إضافية')
                            ->rows(3)
                            ->placeholder('أي تعليمات أو ملاحظات إضافية للطلاب'),
                    ]),

                Section::make('ملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('session_notes')
                                    ->label('ملاحظات الجلسة')
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

                TextColumn::make('quranTeacher.id')
                    ->label('المعلم')
                    ->formatStateUsing(fn ($record) =>
                        trim(($record->quranTeacher?->first_name ?? '') . ' ' . ($record->quranTeacher?->last_name ?? '')) ?: 'معلم #' . ($record->quranTeacher?->id ?? '-')
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->placeholder('جلسة فردية')
                    ->toggleable(),

                TextColumn::make('student.id')
                    ->label('الطالب')
                    ->formatStateUsing(fn ($record) =>
                        trim(($record->student?->first_name ?? '') . ' ' . ($record->student?->last_name ?? '')) ?: null
                    )
                    ->searchable()
                    ->placeholder('جماعية')
                    ->toggleable(),

                BadgeColumn::make('session_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                        'warning' => 'trial',
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
                    ]),

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->relationship('quranTeacher', 'name')
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
            ->with(['quranTeacher', 'circle', 'student', 'individualCircle', 'academy', 'sessionHomework']);
    }
}
