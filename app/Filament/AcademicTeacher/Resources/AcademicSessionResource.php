<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\SessionDuration;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;
use App\Models\AcademicSession;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AcademicSessionResource extends Resource
{
    protected static ?string $model = AcademicSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?string $modelLabel = 'جلسة أكاديمية';

    protected static ?string $pluralModelLabel = 'جميع الجلسات';

    protected static ?string $navigationLabel = 'جميع الجلسات';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان الجلسة')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('session_type')
                                    ->label('نوع الجلسة')
                                    ->options([
                                        'individual' => 'درس فردي',
                                        'interactive_course' => 'دورة تفاعلية',
                                    ])
                                    ->required()
                                    ->reactive(),

                                Forms\Components\Select::make('student_id')
                                    ->label('الطالب')
                                    ->relationship('student', 'name')
                                    ->searchable()
                                    ->required()
                                    ->reactive(),

                                Forms\Components\DateTimePicker::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('duration_minutes')
                                    ->label('مدة الجلسة (بالدقائق)')
                                    ->options(SessionDuration::options())
                                    ->default(function (Forms\Get $get) {
                                        if ($get('session_type') === 'individual') {
                                            // Get duration from student's academic subscription package
                                            $studentId = $get('student_id');
                                            if ($studentId) {
                                                $student = \App\Models\User::find($studentId);
                                                $subscription = $student?->academicSubscriptions()?->active()?->first();

                                                return $subscription?->package?->session_duration_minutes ?? 45;
                                            }

                                            return 45; // Default for individual sessions
                                        }

                                        return 60; // Default for interactive courses
                                    })
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        if ($get('session_type') === 'individual') {
                                            // Auto-update duration when student changes for individual sessions
                                            $studentId = $get('student_id');
                                            if ($studentId) {
                                                $student = \App\Models\User::find($studentId);
                                                $subscription = $student?->academicSubscriptions()?->active()?->first();
                                                $duration = $subscription?->package?->session_duration_minutes ?? 45;
                                                $set('duration_minutes', $duration);
                                            }
                                        }
                                    })
                                    ->required()
                                    ->disabled(fn (Forms\Get $get): bool => $get('session_type') === 'individual'
                                    )
                                    ->dehydrated()
                                    ->helperText(fn (Forms\Get $get): ?string => $get('session_type') === 'individual'
                                            ? 'المدة محددة بناءً على الباقة المشترك بها'
                                            : 'اختر المدة المناسبة للدورة التفاعلية'
                                    ),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->rows(3),

                        Forms\Components\Textarea::make('lesson_objectives')
                            ->label('أهداف الدرس')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        return $table
            ->query(
                AcademicSession::query()
                    ->where('academic_teacher_id', $teacherProfile?->id ?? 0)
                    ->with(['student', 'academicIndividualLesson', 'interactiveCourseSession'])
                    ->latest('scheduled_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الجلسة')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (AcademicSession $record): ?string {
                        return $record->title;
                    }),

                Tables\Columns\TextColumn::make('session_type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'blue',
                        'interactive_course' => 'green',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'درس فردي',
                        'interactive_course' => 'دورة تفاعلية',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('التاريخ والوقت')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('غير مجدول'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'unscheduled',
                        'warning' => 'scheduled',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'info' => 'ongoing',
                        'primary' => 'ready',
                    ])
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SessionStatus ? $state->label() : $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'unscheduled' => 'غير مجدولة',
                        'scheduled' => 'مجدولة',
                        'ready' => 'جاهزة للبدء',
                        'ongoing' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                    ]),

                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'درس فردي',
                        'interactive_course' => 'دورة تفاعلية',
                    ]),

                Tables\Filters\Filter::make('scheduled_today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('upcoming')
                    ->label('الجلسات القادمة')
                    ->query(fn (Builder $query): Builder => $query->where('scheduled_at', '>', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),

                Tables\Actions\Action::make('join_meeting')
                    ->label('دخول الجلسة')
                    ->icon('heroicon-o-video-camera')
                    ->color('success')
                    ->visible(fn (AcademicSession $record): bool => in_array($record->status->value ?? $record->status, ['scheduled', 'ready', 'ongoing'])
                        && $record->scheduled_at
                        && $record->scheduled_at->isBefore(now()->addMinutes(15))
                    )
                    ->url(fn (AcademicSession $record): string => route('teacher.sessions.show', [
                        'subdomain' => Auth::user()->academy->subdomain ?? 'itqan-academy',
                        'sessionId' => $record->id,
                    ])
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk actions can be added here if needed
                ]),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->emptyStateHeading('لا توجد جلسات')
            ->emptyStateDescription('لم يتم إنشاء أي جلسات بعد.')
            ->emptyStateIcon('heroicon-o-calendar-days');
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
            ->where('academic_teacher_id', $teacherProfile?->id ?? 0)
            ->with(['student', 'academicIndividualLesson', 'interactiveCourseSession']);
    }
}
