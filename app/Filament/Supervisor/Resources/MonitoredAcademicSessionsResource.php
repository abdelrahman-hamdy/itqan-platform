<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages\CreateMonitoredAcademicSession;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages\EditMonitoredAcademicSession;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages\ListMonitoredAcademicSessions;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages\ViewMonitoredAcademicSession;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Academic Sessions Resource for Supervisor Panel
 * Shows academic sessions for the supervisor's assigned academic teachers.
 */
class MonitoredAcademicSessionsResource extends BaseSupervisorResource
{
    protected static ?string $model = AcademicSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'جلسات أكاديمية';

    protected static ?string $modelLabel = 'جلسة أكاديمية';

    protected static ?string $pluralModelLabel = 'جلسات أكاديمية';

    protected static string | \UnitEnum | null $navigationGroup = 'الدروس الأكاديمية';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجلسة')
                    ->schema([
                        TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status')
                            ->label('الحالة')
                            ->options(SessionStatus::options())
                            ->required(),

                        DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->required()
                            ->timezone(AcademyContextService::getTimezone()),

                        Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options([
                                30 => '30 دقيقة',
                                45 => '45 دقيقة',
                                60 => '60 دقيقة',
                                90 => '90 دقيقة',
                                120 => '120 دقيقة',
                            ])
                            ->default(60)
                            ->required(),
                    ])->columns(2),

                Section::make('ملاحظات المشرف')
                    ->schema([
                        Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->helperText('ملاحظات من المشرف بعد المراجعة')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(SessionTableColumns::getAcademicSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options())
                    ->placeholder('الكل'),

                SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(array_merge(
                        [SessionStatus::SCHEDULED->value => SessionStatus::SCHEDULED->label()],
                        AttendanceStatus::options()
                    ))
                    ->placeholder('الكل'),

                SelectFilter::make('academic_teacher_id')
                    ->label('المعلم')
                    ->options(fn () => AcademicTeacherProfile::whereIn('id', static::getAssignedAcademicTeacherProfileIds())
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn ($profile) => [
                            $profile->id => $profile->user
                                ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                                : 'معلم #'.$profile->id,
                        ])
                    )
                    ->searchable()
                    ->placeholder('الكل'),

                SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->options(fn () => User::query()
                        ->where('user_type', 'student')
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                        ])
                    )
                    ->searchable()
                    ->placeholder('الكل'),

                SelectFilter::make('academic_individual_lesson_id')
                    ->label('الدرس الفردي')
                    ->options(fn () => AcademicIndividualLesson::query()
                        ->with(['student', 'academicTeacher.user'])
                        ->get()
                        ->mapWithKeys(fn ($lesson) => [
                            $lesson->id => ($lesson->name ?? 'درس #'.$lesson->id)
                                .' - '.trim(($lesson->student?->first_name ?? '').' '.($lesson->student?->last_name ?? '')),
                        ])
                    )
                    ->searchable()
                    ->placeholder('الكل'),

                Filter::make('date_range')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('from')->label('من تاريخ'),
                            DatePicker::make('until')->label('إلى تاريخ'),
                        ]),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '<=', $date))
                    )
                    ->columnSpan(2),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    MeetingActions::viewMeeting('academic'),
                    EditAction::make()->label('تعديل'),
                    Action::make('add_note')
                        ->label('ملاحظة')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->schema([
                            Textarea::make('supervisor_notes')
                                ->label('ملاحظات المشرف')
                                ->rows(4)
                                ->default(fn ($record) => $record->supervisor_notes),
                        ])
                        ->action(fn ($record, array $data) => $record->update([
                            'supervisor_notes' => $data['supervisor_notes'],
                        ])),
                    DeleteAction::make()->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedAcademicTeachers();
    }

    public static function canCreate(): bool
    {
        return static::isSupervisor() && static::hasAssignedAcademicTeachers();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['academicTeacher.user', 'academicIndividualLesson.academicSubject', 'student']);

        $profileIds = static::getAssignedAcademicTeacherProfileIds();

        if (! empty($profileIds)) {
            $query->whereIn('academic_teacher_id', $profileIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoredAcademicSessions::route('/'),
            'create' => CreateMonitoredAcademicSession::route('/create'),
            'view' => ViewMonitoredAcademicSession::route('/{record}'),
            'edit' => EditMonitoredAcademicSession::route('/{record}/edit'),
        ];
    }
}
