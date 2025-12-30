<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicIndividualLessonResource\Pages;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubject;
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
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

/**
 * Academic Individual Lesson Resource for Admin Panel
 *
 * Allows admins to view and manage all private academic lessons.
 */
class AcademicIndividualLessonResource extends Resource
{
    protected static ?string $model = AcademicIndividualLesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الدروس الفردية';

    protected static ?string $modelLabel = 'درس فردي';

    protected static ?string $pluralModelLabel = 'الدروس الفردية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدرس الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('lesson_code')
                            ->label('رمز الدرس')
                            ->disabled(),

                        Forms\Components\TextInput::make('name')
                            ->label('اسم الدرس')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدرس')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('academic_teacher_id')
                            ->relationship('academicTeacher', 'id')
                            ->label('المعلم')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name)
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('academic_subject_id')
                            ->relationship('academicSubject', 'name')
                            ->label('المادة')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('academic_grade_level_id')
                            ->relationship('academicGradeLevel', 'name')
                            ->label('المستوى الدراسي')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('إعدادات الجلسات')
                    ->schema([
                        Forms\Components\TextInput::make('total_sessions')
                            ->label('عدد الجلسات الكلي')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('sessions_scheduled')
                            ->label('الجلسات المجدولة')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('sessions_completed')
                            ->label('الجلسات المكتملة')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('sessions_remaining')
                            ->label('الجلسات المتبقية')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('default_duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->default(60)
                            ->minValue(15)
                            ->maxValue(180)
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('حالة الدرس')
                            ->options([
                                SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                                SubscriptionStatus::ACTIVE->value => 'نشط',
                                SessionStatus::COMPLETED->value => 'مكتمل',
                                SessionStatus::CANCELLED->value => 'ملغي',
                            ])
                            ->default(SubscriptionStatus::PENDING->value)
                            ->required(),

                        Forms\Components\TextInput::make('progress_percentage')
                            ->label('نسبة الإنجاز')
                            ->suffix('%')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('تسجيل الجلسات')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('تاريخ البدء')
                            ->timezone(AcademyContextService::getTimezone()),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->timezone(AcademyContextService::getTimezone()),

                        Forms\Components\DateTimePicker::make('last_session_at')
                            ->label('آخر جلسة')
                            ->timezone(AcademyContextService::getTimezone())
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('أهداف التعلم والمواد')
                    ->schema([
                        Forms\Components\Repeater::make('learning_objectives')
                            ->label('أهداف التعلم')
                            ->schema([
                                Forms\Components\TextInput::make('objective')
                                    ->label('الهدف')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة هدف')
                            ->collapsible()
                            ->collapsed(),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),

                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lesson_code')
                    ->label('رمز الدرس')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('اسم الدرس')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('academicTeacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

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
                    ->sortable(),

                TextColumn::make('progress_percentage')
                    ->label('التقدم')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 80 => 'success',
                        (float) $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => SubscriptionStatus::PENDING->value,
                        'success' => SubscriptionStatus::ACTIVE->value,
                        'gray' => SessionStatus::COMPLETED->value,
                        'danger' => SessionStatus::CANCELLED->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                        SubscriptionStatus::ACTIVE->value => 'نشط',
                        SessionStatus::COMPLETED->value => 'مكتمل',
                        SessionStatus::CANCELLED->value => 'ملغي',
                        default => $state,
                    }),

                TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                        SubscriptionStatus::ACTIVE->value => 'نشط',
                        SessionStatus::COMPLETED->value => 'مكتمل',
                        SessionStatus::CANCELLED->value => 'ملغي',
                    ]),

                Tables\Filters\SelectFilter::make('academic_teacher_id')
                    ->label('المعلم')
                    ->relationship('academicTeacher.user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('academic_subject_id')
                    ->label('المادة')
                    ->relationship('academicSubject', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('active')
                    ->label('النشطة فقط')
                    ->query(fn (Builder $query): Builder => $query->where('status', SubscriptionStatus::ACTIVE->value)),

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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListAcademicIndividualLessons::route('/'),
            'create' => Pages\CreateAcademicIndividualLesson::route('/create'),
            'view' => Pages\ViewAcademicIndividualLesson::route('/{record}'),
            'edit' => Pages\EditAcademicIndividualLesson::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['academicTeacher.user', 'student', 'academicSubject', 'academicGradeLevel', 'academy']);
    }
}
