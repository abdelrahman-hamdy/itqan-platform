<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;
use App\Models\InteractiveCourseSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InteractiveCourseSessionResource extends BaseAcademicTeacherResource
{
    protected static ?string $model = InteractiveCourseSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'جلسات الدورات التفاعلية';

    protected static ?string $modelLabel = 'جلسة دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'جلسات الدورات التفاعلية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 2;

    /**
     * Filter sessions to only show those for courses taught by current teacher
     * Override to prevent academy_id filtering on interactive_course_sessions table
     * (which doesn't have academy_id column - it gets academy through course relationship)
     */
    public static function getEloquentQuery(): Builder
    {
        // Get base model query directly to bypass parent's academy_id filter
        // which doesn't work for interactive_course_sessions (no academy_id column)
        $query = static::getModel()::query();

        $teacherProfile = static::getCurrentAcademicTeacherProfile();
        $teacherAcademy = static::getCurrentTeacherAcademy();

        if ($teacherProfile) {
            // Filter by both teacher AND academy through course relationship
            $query->whereHas('course', function ($query) use ($teacherProfile, $teacherAcademy) {
                $query->where('assigned_teacher_id', $teacherProfile->id);

                // Also filter by academy through course
                if ($teacherAcademy) {
                    $query->where('academy_id', $teacherAcademy->id);
                }
            });
        } elseif ($teacherAcademy) {
            // If no teacher profile but has academy, filter by academy only
            $query->whereHas('course', function ($query) use ($teacherAcademy) {
                $query->where('academy_id', $teacherAcademy->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        // Note: academy_id is not a column in interactive_course_sessions table
                        // Academy is determined through the course relationship

                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title', function ($query) {
                                $teacherProfile = static::getCurrentAcademicTeacherProfile();
                                if ($teacherProfile) {
                                    $query->where('assigned_teacher_id', $teacherProfile->id);
                                }
                            })
                            ->label('الدورة')
                            ->required()
                            ->searchable()
                            ->preload(),

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
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('التوقيت والحالة')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->native(false)
                            ->seconds(false),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(180)
                            ->default(90)
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options([
                                'scheduled' => 'مجدولة',
                                'ready' => 'جاهزة للبدء',
                                'ongoing' => 'جارية',
                                'completed' => 'مكتملة',
                                'cancelled' => 'ملغية',
                            ])
                            ->default('scheduled')
                            ->required(),

                        Forms\Components\TextInput::make('meeting_link')
                            ->label('رابط الاجتماع')
                            ->url()
                            ->helperText('سيتم إنشاء رابط LiveKit تلقائياً عند بدء الجلسة'),

                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('تسجيل الجلسة')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('الواجبات والمواد')
                    ->schema([
                        Forms\Components\Toggle::make('homework_assigned')
                            ->label('يوجد واجب منزلي')
                            ->default(false)
                            ->live(),

                        Forms\Components\Textarea::make('homework_description')
                            ->label('وصف الواجب')
                            ->rows(3)
                            ->visible(fn ($get) => $get('homework_assigned')),

                        Forms\Components\FileUpload::make('homework_file')
                            ->label('ملف الواجب')
                            ->directory('interactive-course-homework')
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'jpg', 'png'])
                            ->visible(fn ($get) => $get('homework_assigned')),
                    ])->columns(2),

                Forms\Components\Section::make('الحضور والمشاركة')
                    ->schema([
                        Forms\Components\TextInput::make('attendance_count')
                            ->label('عدد الحضور')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يتم التحديث تلقائياً من سجلات الحضور'),
                    ]),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'scheduled',
                        'info' => 'ready',
                        'primary' => 'ongoing',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\SessionStatus
                        ? $state->label()
                        : match ($state) {
                            'scheduled' => 'مجدولة',
                            'ready' => 'جاهزة',
                            'ongoing' => 'جارية',
                            'completed' => 'مكتملة',
                            'cancelled' => 'ملغية',
                            default => (string) $state,
                        }
                    ),

                Tables\Columns\TextColumn::make('attendance_count')
                    ->label('عدد الحضور')
                    ->numeric()
                    ->sortable(),

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
                    ->options([
                        'scheduled' => 'مجدولة',
                        'ready' => 'جاهزة',
                        'ongoing' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                    ]),

                Tables\Filters\SelectFilter::make('course_id')
                    ->label('الدورة')
                    ->relationship('course', 'title', function ($query) {
                        $teacherProfile = static::getCurrentAcademicTeacherProfile();
                        if ($teacherProfile) {
                            $query->where('assigned_teacher_id', $teacherProfile->id);
                        }
                    })
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('join_meeting')
                    ->label('دخول الاجتماع')
                    ->icon('heroicon-o-video-camera')
                    ->url(fn (InteractiveCourseSession $record): string => $record->meeting_link ?? '#')
                    ->openUrlInNewTab()
                    ->visible(fn (InteractiveCourseSession $record): bool => !empty($record->meeting_link)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
