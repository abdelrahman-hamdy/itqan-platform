<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicIndividualLessonResource\Pages;
use App\Filament\Resources\AcademicSessionResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubject;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Academic Individual Lesson Resource for Admin Panel
 *
 * Allows admins to view and manage all private academic lessons.
 */
class AcademicIndividualLessonResource extends BaseResource
{
    protected static ?string $model = AcademicIndividualLesson::class;

    /**
     * Tenant ownership relationship for Filament multi-tenancy.
     */
    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

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
                            ->label('الطالب')
                            ->options(function () {
                                return \App\Models\User::where('user_type', 'student')
                                    ->with('studentProfile')
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        // display_name already includes student code if available
                                        return [$user->id => $user->studentProfile?->display_name ?? $user->name];
                                    });
                            })
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

                Forms\Components\Section::make('أهداف التعلم')
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
                            ->collapsed()
                            ->columnSpanFull(),
                    ]),

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

                static::getAcademyColumn(),

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

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (AcademicIndividualLesson $record): string => AcademicSessionResource::getUrl('index', [
                            'tableFilters[academic_individual_lesson_id][value]' => $record->id,
                        ])),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                    Tables\Actions\RestoreAction::make()
                        ->label('استعادة'),
                    Tables\Actions\ForceDeleteAction::make()
                        ->label('حذف نهائي'),
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
