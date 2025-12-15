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
use App\Services\AcademyContextService;
use App\Enums\SessionStatus;

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
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),

                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->options(fn () => \App\Models\User::query()
                                ->where('user_type', 'student')
                                ->get()
                                ->mapWithKeys(fn ($user) => [
                                    $user->id => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name ?? 'طالب #' . $user->id
                                ])
                            )
                            ->searchable()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),

                        Forms\Components\TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'individual' => 'فردية',
                            ])
                            ->default('individual')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('الجلسات الأكاديمية فردية فقط حالياً')
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

                        Forms\Components\Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(4),
                    ]),

                Forms\Components\Section::make('التوقيت والحالة')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->timezone(AcademyContextService::getTimezone())
                            ->displayFormat('Y-m-d H:i'),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('مدة الجلسة (بالدقائق)')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(120)
                            ->default(60)
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options(SessionStatus::options())
                            ->default(SessionStatus::SCHEDULED->value)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('الواجبات')
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
                            ->directory('academic-homework')
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'jpg', 'png'])
                            ->visible(fn ($get) => $get('homework_assigned')),
                    ]),

                Forms\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Forms\Components\TextInput::make('participants_count')
                            ->label('عدد المشاركين')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يتم التحديث تلقائياً'),
                    ])->columns(2),
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
                    ->timezone(fn ($record) => $record->academy->timezone->value)
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
                        $status = SessionStatus::tryFrom($state);
                        return $status?->label() ?? (string) $state;
                    }),

                Tables\Columns\BadgeColumn::make('attendance_status')
                    ->label('الحضور')
                    ->colors([
                        'secondary' => 'scheduled',
                        'success' => 'attended',
                        'danger' => 'absent',
                        'warning' => 'late',
                        'primary' => 'leaved',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'مجدولة',
                        'attended' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                        default => $state,
                    }),

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
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options([
                        'scheduled' => 'مجدولة',
                        'attended' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                    ]),

                Tables\Filters\SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->options(fn () => \App\Models\User::query()
                        ->where('user_type', 'student')
                        ->whereNotNull('name')
                        ->pluck('name', 'id')
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('scheduled_today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('scheduled_this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('start_session')
                        ->label('بدء الجلسة')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (AcademicSession $record): bool =>
                            $record->status instanceof \App\Enums\SessionStatus
                                ? $record->status === \App\Enums\SessionStatus::SCHEDULED
                                : $record->status === 'scheduled')
                        ->action(function (AcademicSession $record) {
                            $record->update([
                                'status' => \App\Enums\SessionStatus::ONGOING,
                                'started_at' => now(),
                            ]);
                        }),
                    Tables\Actions\Action::make('complete_session')
                        ->label('إنهاء الجلسة')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (AcademicSession $record): bool =>
                            $record->status instanceof \App\Enums\SessionStatus
                                ? $record->status === \App\Enums\SessionStatus::ONGOING
                                : $record->status === 'ongoing')
                        ->action(function (AcademicSession $record) {
                            $record->update([
                                'status' => \App\Enums\SessionStatus::COMPLETED,
                                'ended_at' => now(),
                                'actual_duration_minutes' => now()->diffInMinutes($record->started_at),
                                'attendance_status' => 'attended',
                            ]);
                            // Update subscription usage
                            $record->updateSubscriptionUsage();
                        }),
                    Tables\Actions\Action::make('join_meeting')
                        ->label('دخول الاجتماع')
                        ->icon('heroicon-o-video-camera')
                        ->url(fn (AcademicSession $record): string => $record->meeting_link ?? '#')
                        ->openUrlInNewTab()
                        ->visible(fn (AcademicSession $record): bool => !empty($record->meeting_link)),
                ]),
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
            'create' => Pages\CreateAcademicSession::route('/create'),
            'view' => Pages\ViewAcademicSession::route('/{record}'),
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
