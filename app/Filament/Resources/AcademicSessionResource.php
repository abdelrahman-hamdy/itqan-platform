<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Filament\Resources\AcademicSessionResource\Pages;
use App\Filament\Resources\AcademicSessionResource\RelationManagers;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSessionResource extends BaseResource
{
    protected static ?string $model = AcademicSession::class;

    /**
     * Academy relationship path for BaseResource.
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';
    
    protected static ?string $modelLabel = 'جلسة أكاديمية';
    
    protected static ?string $pluralModelLabel = 'الجلسات الأكاديمية';
    
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الجلسة')
                    ->schema([
                        // Hidden academy_id - auto-set from context
                        Forms\Components\Hidden::make('academy_id')
                            ->default(fn () => auth()->user()->academy_id),

                        Forms\Components\TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->label('حالة الجلسة')
                            ->options(SessionStatus::options())
                            ->default(SessionStatus::SCHEDULED->value)
                            ->required(),

                        Forms\Components\Hidden::make('session_type')
                            ->default('individual'),

                        Forms\Components\Select::make('academic_teacher_id')
                            ->relationship('academicTeacher', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user ? trim(($record->user->first_name ?? '') . ' ' . ($record->user->last_name ?? '')) ?: 'معلم #' . $record->id : 'معلم #' . $record->id)
                            ->label('المعلم')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),

                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: 'طالب #' . $record->id)
                            ->label('الطالب')
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),

                        Forms\Components\Hidden::make('academic_subscription_id'),
                    ])->columns(2),

                Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->timezone(AcademyContextService::getTimezone())
                            ->displayFormat('Y-m-d H:i'),

                        Forms\Components\Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(60)
                            ->required(),
                    ])->columns(2),

                Section::make('تفاصيل الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->helperText('أهداف ومحتوى الجلسة')
                            ->rows(3),

                        Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(4),
                    ]),

                Section::make('الواجبات')
                    ->schema([
                        Forms\Components\Toggle::make('homework_assigned')
                            ->label('يوجد واجب منزلي')
                            ->default(false)
                            ->live(),

                        Textarea::make('homework_description')
                            ->label('وصف الواجب')
                            ->rows(3)
                            ->visible(fn ($get) => $get('homework_assigned')),

                        Forms\Components\FileUpload::make('homework_file')
                            ->label('ملف الواجب')
                            ->directory('academic-homework')
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'jpg', 'png'])
                            ->visible(fn ($get) => $get('homework_assigned')),
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

    /**
     * Eager load relationships to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'academy',
                'academicTeacher.user',
                'academicSubscription',
                'student',
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
                
                Tables\Columns\TextColumn::make('academicTeacher.user.id')
                    ->label('المعلم')
                    ->formatStateUsing(fn ($record) =>
                        $record->academicTeacher?->user
                            ? trim(($record->academicTeacher->user->first_name ?? '') . ' ' . ($record->academicTeacher->user->last_name ?? '')) ?: 'معلم #' . $record->academicTeacher->id
                            : 'معلم #' . ($record->academic_teacher_id ?? '-')
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.id')
                    ->label('الطالب')
                    ->formatStateUsing(fn ($record) =>
                        trim(($record->student?->first_name ?? '') . ' ' . ($record->student?->last_name ?? '')) ?: null
                    )
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
                        'secondary' => SessionStatus::SCHEDULED->value,
                        'success' => AttendanceStatus::ATTENDED->value,
                        'danger' => AttendanceStatus::ABSENT->value,
                        'warning' => AttendanceStatus::LATE->value,
                        'primary' => AttendanceStatus::LEFT->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SessionStatus::SCHEDULED->value => 'مجدولة',
                        AttendanceStatus::ATTENDED->value => 'حاضر',
                        AttendanceStatus::ABSENT->value => 'غائب',
                        AttendanceStatus::LATE->value => 'متأخر',
                        AttendanceStatus::LEFT->value => 'غادر مبكراً',
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
                    ->label(__('filament.session_status'))
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label(__('filament.attendance_status'))
                    ->options(AttendanceStatus::options()),

                Tables\Filters\SelectFilter::make('academic_teacher_id')
                    ->label(__('filament.teacher'))
                    ->relationship('academicTeacher.user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('student_id')
                    ->label(__('filament.student'))
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('academic_individual_lesson_id')
                    ->label('الدرس الفردي')
                    ->relationship('academicIndividualLesson', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('scheduled_today')
                    ->label(__('filament.filters.today_sessions'))
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('scheduled_this_week')
                    ->label(__('filament.filters.this_week_sessions'))
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Tables\Filters\Filter::make('scheduled_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filament.filters.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filament.filters.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('filament.filters.from_date') . ': ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('filament.filters.to_date') . ': ' . $data['until'];
                        }
                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                    Tables\Actions\Action::make('start_session')
                        ->label('بدء الجلسة')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (AcademicSession $record): bool =>
                            $record->status instanceof \App\Enums\SessionStatus
                                ? $record->status === \App\Enums\SessionStatus::SCHEDULED
                                : $record->status === SessionStatus::SCHEDULED->value)
                        ->action(function (AcademicSession $record) {
                            $record->update([
                                'status' => SessionStatus::ONGOING->value,
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
                                : $record->status === SessionStatus::ONGOING->value)
                        ->action(function (AcademicSession $record) {
                            $record->update([
                                'status' => SessionStatus::COMPLETED->value,
                                'ended_at' => now(),
                                'actual_duration_minutes' => now()->diffInMinutes($record->started_at),
                                'attendance_status' => AttendanceStatus::ATTENDED->value,
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
                    Tables\Actions\RestoreAction::make()
                        ->label(__('filament.actions.restore')),
                    Tables\Actions\ForceDeleteAction::make()
                        ->label(__('filament.actions.force_delete')),
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