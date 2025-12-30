<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSessionResource\Pages;
use App\Filament\Resources\AcademicSessionResource\RelationManagers;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Services\AcademyContextService;
use App\Enums\SessionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\AttendanceStatus;

class AcademicSessionResource extends Resource
{
    protected static ?string $model = AcademicSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';
    
    protected static ?string $modelLabel = 'جلسة أكاديمية';
    
    protected static ?string $pluralModelLabel = 'الجلسات الأكاديمية';
    
    protected static ?string $navigationGroup = 'الإدارة الأكاديمية';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->label('الأكاديمية')
                            ->required()
                            ->disabled()
                            ->default(fn () => auth()->user()->academy_id),

                        Forms\Components\Select::make('academic_teacher_id')
                            ->relationship('academicTeacher', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user ? trim(($record->user->first_name ?? '') . ' ' . ($record->user->last_name ?? '')) ?: $record->user->name : 'معلم #' . $record->id)
                            ->label('المعلم')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('academic_subscription_id')
                            ->relationship('academicSubscription', 'subscription_code')
                            ->label('الاشتراك')
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(),

                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: $record->name ?? 'طالب #' . $record->id)
                            ->label('الطالب')
                            ->searchable()
                            ->preload()
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
                
                Tables\Columns\TextColumn::make('academicTeacher.user.name')
                    ->label('المعلم')
                    ->searchable(),
                
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
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}