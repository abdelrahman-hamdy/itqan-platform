<?php

namespace App\Filament\Resources;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\StudentProgressResource\Pages;
use App\Models\CourseSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentProgressResource extends BaseResource
{
    protected static ?string $model = CourseSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'تقدم الدورات المسجلة';

    protected static ?string $modelLabel = 'تقدم دورة';

    protected static ?string $pluralModelLabel = 'تقدم الدورات';

    protected static ?string $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'recorded-course-progress';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Student & Course Info
                Forms\Components\Section::make('معلومات الطالب والدورة')
                    ->description('بيانات الطالب والدورة المسجلة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('student_id')
                                    ->label('الطالب')
                                    ->relationship(
                                        'student',
                                        'name',
                                        fn (Builder $query) => $query->whereHas('studentProfile')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('recorded_course_id')
                                    ->label('الدورة المسجلة')
                                    ->relationship('recordedCourse', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),

                // Section 2: Progress Tracking
                Forms\Components\Section::make('تتبع التقدم')
                    ->description('إحصائيات إكمال الدورة')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('progress_percentage')
                                    ->label('نسبة الإكمال')
                                    ->suffix('%')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0),

                                Forms\Components\TextInput::make('completed_lessons')
                                    ->label('الدروس المكتملة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),

                                Forms\Components\TextInput::make('total_lessons')
                                    ->label('إجمالي الدروس')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                            ]),

                        Forms\Components\DateTimePicker::make('last_accessed_at')
                            ->label('آخر دخول'),
                    ]),

                // Section 3: Certificate
                Forms\Components\Section::make('الشهادة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('certificate_issued')
                                    ->label('تم إصدار الشهادة'),

                                Forms\Components\DateTimePicker::make('completion_date')
                                    ->label('تاريخ الإكمال'),
                            ]),
                    ])
                    ->collapsible(),

                // Hidden field to ensure only recorded courses
                Forms\Components\Hidden::make('course_type')
                    ->default('recorded'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('recordedCourse.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->recordedCourse?->title),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('نسبة الإكمال')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        $state > 0 => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('lessons_progress')
                    ->label('الدروس')
                    ->getStateUsing(fn ($record) => "{$record->completed_lessons}/{$record->total_lessons}")
                    ->description(fn ($record) => 'درس مكتمل'),

                Tables\Columns\IconColumn::make('certificate_issued')
                    ->label('شهادة')
                    ->boolean()
                    ->trueIcon('heroicon-o-academic-cap')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_accessed_at')
                    ->label('آخر دخول')
                    ->since()
                    ->sortable()
                    ->placeholder('لم يدخل بعد'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('last_accessed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('completion_status')
                    ->label('حالة الإكمال')
                    ->options([
                        'completed' => 'مكتمل (100%)',
                        'in_progress' => 'قيد التقدم',
                        'not_started' => 'لم يبدأ',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'completed' => $query->where('progress_percentage', '>=', 100),
                            'in_progress' => $query->where('progress_percentage', '>', 0)->where('progress_percentage', '<', 100),
                            'not_started' => $query->where('progress_percentage', 0),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('recorded_course_id')
                    ->label('الدورة')
                    ->relationship('recordedCourse', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_certificate')
                    ->label('حاصل على شهادة')
                    ->query(fn (Builder $query) => $query->where('certificate_issued', true)),

                Tables\Filters\Filter::make('last_week')
                    ->label('نشط هذا الأسبوع')
                    ->query(fn (Builder $query) => $query->where('last_accessed_at', '>=', now()->subWeek())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\Action::make('markComplete')
                    ->label('تحديد كمكتمل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تحديد كمكتمل')
                    ->modalDescription('سيتم تحديد هذه الدورة كمكتملة بنسبة 100%. هل أنت متأكد؟')
                    ->action(fn (CourseSubscription $record) => $record->markAsCompleted())
                    ->visible(fn (CourseSubscription $record) => ! $record->isCompleted()),
                Tables\Actions\Action::make('issueCertificate')
                    ->label('إصدار شهادة')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(fn (CourseSubscription $record) => $record->issueCertificateForCourse())
                    ->visible(fn (CourseSubscription $record) => $record->can_earn_certificate),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
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
            'index' => Pages\ListStudentProgress::route('/'),
            'create' => Pages\CreateStudentProgress::route('/create'),
            'view' => Pages\ViewStudentProgress::route('/{record}'),
            'edit' => Pages\EditStudentProgress::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('course_type', 'recorded')
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->with(['student', 'recordedCourse']);
    }
}
