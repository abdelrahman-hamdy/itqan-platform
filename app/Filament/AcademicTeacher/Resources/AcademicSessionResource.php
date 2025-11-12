<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\RelationManagers;
use App\Models\AcademicSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSessionResource extends BaseAcademicTeacherResource
{
    protected static ?string $model = AcademicSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';

    protected static ?string $modelLabel = 'جلسة أكاديمية';

    protected static ?string $pluralModelLabel = 'الجلسات الأكاديمية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 1;

    /**
     * Override query to show only sessions for current academic teacher
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $teacherProfile = static::getCurrentAcademicTeacherProfile();

        if ($teacherProfile) {
            $query->where('academic_teacher_id', $teacherProfile->id);
        }

        return $query;
    }

    /**
     * Academic teachers can create sessions
     */
    public static function canCreate(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        // Hidden fields for auto-assignment
                        Forms\Components\Hidden::make('academy_id')
                            ->default(fn () => static::getCurrentTeacherAcademy()?->id),

                        Forms\Components\Hidden::make('academic_teacher_id')
                            ->default(fn () => static::getCurrentAcademicTeacherProfile()?->id),

                        Forms\Components\Select::make('academic_subscription_id')
                            ->relationship('academicSubscription', 'subscription_code')
                            ->label('الاشتراك')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'individual' => 'فردية',
                                'interactive_course' => 'دورة تفاعلية',
                            ])
                            ->default('individual')
                            ->required(),
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

                        Forms\Components\TagsInput::make('lesson_objectives')
                            ->label('أهداف الدرس')
                            ->separator(','),

                        Forms\Components\Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(4),

                        Forms\Components\TagsInput::make('learning_outcomes')
                            ->label('نواتج التعلم')
                            ->separator(','),
                    ]),

                Forms\Components\Section::make('التوقيت والحالة')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->min(30)
                            ->max(120)
                            ->default(60)
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options([
                                'scheduled' => 'مجدولة',
                                'ongoing' => 'جارية',
                                'completed' => 'مكتملة',
                                'cancelled' => 'ملغية',
                                'rescheduled' => 'معاد جدولتها',
                            ])
                            ->default('scheduled')
                            ->required(),

                        Forms\Components\Select::make('location_type')
                            ->label('نوع المكان')
                            ->options([
                                'online' => 'عبر الإنترنت',
                                'physical' => 'حضوري',
                                'hybrid' => 'مختلط',
                            ])
                            ->default('online')
                            ->required(),

                        Forms\Components\TextInput::make('meeting_link')
                            ->label('رابط الاجتماع')
                            ->url(),

                        Forms\Components\Toggle::make('is_auto_generated')
                            ->label('تم إنشاؤها تلقائياً')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('الواجبات والتقييم')
                    ->schema([
                        Forms\Components\Textarea::make('homework_description')
                            ->label('وصف الواجب')
                            ->rows(3),

                        Forms\Components\FileUpload::make('homework_file')
                            ->label('ملف الواجب')
                            ->directory('academic-homework')
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'jpg', 'png']),

                        Forms\Components\TextInput::make('session_grade')
                            ->label('درجة الجلسة')
                            ->numeric()
                            ->min(0)
                            ->max(10)
                            ->step(0.1),

                        Forms\Components\Textarea::make('session_notes')
                            ->label('ملاحظات الجلسة')
                            ->rows(3),

                        Forms\Components\Textarea::make('teacher_feedback')
                            ->label('تقييم المعلم')
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('الحضور والمشاركة')
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('حالة الحضور')
                            ->options([
                                'scheduled' => 'مجدولة',
                                'present' => 'حاضر',
                                'absent' => 'غائب',
                                'late' => 'متأخر',
                                'partial' => 'حضور جزئي',
                            ])
                            ->default('scheduled'),

                        Forms\Components\TextInput::make('participants_count')
                            ->label('عدد المشاركين')
                            ->numeric()
                            ->min(0)
                            ->default(0),

                        Forms\Components\Textarea::make('attendance_notes')
                            ->label('ملاحظات الحضور')
                            ->rows(2),
                    ])->columns(3),
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

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable(),

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
                        'primary' => 'scheduled',
                        'success' => 'ongoing',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'warning' => 'rescheduled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'مجدولة',
                        'ongoing' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'rescheduled' => 'معاد جدولتها',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('attendance_status')
                    ->label('الحضور')
                    ->colors([
                        'secondary' => 'scheduled',
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'late',
                        'primary' => 'partial',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'مجدولة',
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'partial' => 'جزئي',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('session_grade')
                    ->label('الدرجة')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('hasHomework')
                    ->label('واجب')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->homework_description) || !empty($record->homework_file)),

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
                        'ongoing' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'rescheduled' => 'معاد جدولتها',
                    ]),

                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options([
                        'scheduled' => 'مجدولة',
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'partial' => 'جزئي',
                    ]),

                Tables\Filters\SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->relationship('student', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('scheduled_today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('scheduled_this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('join_meeting')
                    ->label('دخول الاجتماع')
                    ->icon('heroicon-o-video-camera')
                    ->url(fn (AcademicSession $record): string => $record->meeting_link ?? '#')
                    ->openUrlInNewTab()
                    ->visible(fn (AcademicSession $record): bool => !empty($record->meeting_link)),
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
            'index' => Pages\ListAcademicSessions::route('/'),
            'view' => Pages\ViewAcademicSession::route('/{record}'),
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
