<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AcademicIndividualLessonResource extends Resource
{
    protected static ?string $model = AcademicIndividualLesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?string $navigationLabel = 'الدروس الفردية';

    protected static ?string $modelLabel = 'درس فردي';

    protected static ?string $pluralModelLabel = 'الدروس الفردية';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدرس الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الدرس')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدرس')
                            ->rows(3),

                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->relationship('student', 'first_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('academic_subject_id')
                            ->label('المادة')
                            ->options(
                                AcademicSubject::where('academy_id', $teacherProfile?->academy_id ?? 0)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('academic_grade_level_id')
                            ->label('المستوى الدراسي')
                            ->options(
                                AcademicGradeLevel::where('academy_id', $teacherProfile?->academy_id ?? 0)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
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
                                'pending' => 'قيد الانتظار',
                                'active' => 'نشط',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('تسجيل الجلسات')
                            ->default(false),
                    ])
                    ->columns(2),

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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $teacherProfile = $user->academicTeacherProfile;

                return $query->where('academic_teacher_id', $teacherProfile?->id ?? 0);
            })
            ->columns([
                Tables\Columns\TextColumn::make('lesson_code')
                    ->label('رمز الدرس')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدرس')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('academicSubject.name')
                    ->label('المادة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('academicGradeLevel.name')
                    ->label('المستوى')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sessions_completed')
                    ->label('الجلسات المكتملة')
                    ->suffix(fn (AcademicIndividualLesson $record): string => " / {$record->total_sessions}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('نسبة الإنجاز')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn (string $state): string => match (true) {
                        (float) $state >= 80 => 'success',
                        (float) $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'gray' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),

                Tables\Filters\SelectFilter::make('academic_subject_id')
                    ->label('المادة')
                    ->relationship('academicSubject', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد دروس فردية')
            ->emptyStateDescription('لم يتم إنشاء أي دروس فردية بعد.')
            ->emptyStateIcon('heroicon-o-academic-cap');
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
            'index' => Pages\ListAcademicIndividualLessons::route('/'),
            'create' => Pages\CreateAcademicIndividualLesson::route('/create'),
            'view' => Pages\ViewAcademicIndividualLesson::route('/{record}'),
            'edit' => Pages\EditAcademicIndividualLesson::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->isAcademicTeacher();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        return parent::getEloquentQuery()
            ->where('academic_teacher_id', $teacherProfile?->id ?? 0);
    }
}
