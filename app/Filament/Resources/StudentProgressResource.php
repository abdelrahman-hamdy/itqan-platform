<?php

namespace App\Filament\Resources;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\StudentProgressResource\Pages\EditStudentProgress;
use App\Filament\Resources\StudentProgressResource\Pages\ListStudentProgress;
use App\Filament\Resources\StudentProgressResource\Pages\ViewStudentProgress;
use App\Models\CourseSubscription;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentProgressResource extends BaseResource
{
    protected static ?string $model = CourseSubscription::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'تقدم الدورات المسجلة';

    protected static ?string $modelLabel = 'تقدم دورة';

    protected static ?string $pluralModelLabel = 'تقدم الدورات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $slug = 'recorded-course-progress';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section 1: Student & Course Info
                Section::make('معلومات الطالب والدورة')
                    ->description('بيانات الطالب والدورة المسجلة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->relationship(
                                        'student',
                                        'name',
                                        fn (Builder $query) => $query->whereHas('studentProfile')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('recorded_course_id')
                                    ->label('الدورة المسجلة')
                                    ->relationship('recordedCourse', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),

                // Section 2: Progress Tracking
                Section::make('تتبع التقدم')
                    ->description('إحصائيات إكمال الدورة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('progress_percentage')
                                    ->label('نسبة الإكمال')
                                    ->suffix('%')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0),

                                TextInput::make('completed_lessons')
                                    ->label('الدروس المكتملة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),

                                TextInput::make('total_lessons')
                                    ->label('إجمالي الدروس')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                            ]),

                        DateTimePicker::make('last_accessed_at')
                            ->label('آخر دخول'),
                    ]),

                // Section 3: Certificate
                Section::make('الشهادة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('certificate_issued')
                                    ->label('تم إصدار الشهادة'),

                                DateTimePicker::make('completion_date')
                                    ->label('تاريخ الإكمال'),
                            ]),
                    ])
                    ->collapsible(),

                // Hidden field to ensure only recorded courses
                Hidden::make('course_type')
                    ->default('recorded'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('recordedCourse.title')
                    ->label('الدورة')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->recordedCourse?->title),

                TextColumn::make('progress_percentage')
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

                TextColumn::make('lessons_progress')
                    ->label('الدروس')
                    ->getStateUsing(fn ($record) => "{$record->completed_lessons}/{$record->total_lessons}")
                    ->description(fn ($record) => 'درس مكتمل'),

                IconColumn::make('certificate_issued')
                    ->label('شهادة')
                    ->boolean()
                    ->trueIcon('heroicon-o-academic-cap')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_accessed_at')
                    ->label('آخر دخول')
                    ->since()
                    ->sortable()
                    ->placeholder('لم يدخل بعد'),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('last_accessed_at', 'desc')
            ->filters([
                SelectFilter::make('completion_status')
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

                SelectFilter::make('recorded_course_id')
                    ->label('الدورة')
                    ->relationship('recordedCourse', 'title')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('certificate_issued')
                    ->label('الشهادة')
                    ->placeholder('الكل')
                    ->trueLabel('حاصل على شهادة')
                    ->falseLabel('بدون شهادة'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    EditAction::make()->label('تعديل'),
                    Action::make('markComplete')
                        ->label('تحديد كمكتمل')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تحديد كمكتمل')
                        ->modalDescription('سيتم تحديد هذه الدورة كمكتملة بنسبة 100%. هل أنت متأكد؟')
                        ->action(fn (CourseSubscription $record) => $record->markAsCompleted())
                        ->visible(fn (CourseSubscription $record) => ! $record->isCompleted()),
                    Action::make('issueCertificate')
                        ->label('إصدار شهادة')
                        ->icon('heroicon-o-academic-cap')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(fn (CourseSubscription $record) => $record->issueCertificateForCourse())
                        ->visible(fn (CourseSubscription $record) => $record->can_earn_certificate),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
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
            'index' => ListStudentProgress::route('/'),
            'view' => ViewStudentProgress::route('/{record}'),
            'edit' => EditStudentProgress::route('/{record}/edit'),
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
