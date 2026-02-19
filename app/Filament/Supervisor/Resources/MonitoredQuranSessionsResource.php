<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\SessionStatus;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource\Pages\CreateMonitoredQuranSession;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource\Pages\EditMonitoredQuranSession;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource\Pages\ListMonitoredQuranSessions;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource\Pages\ViewMonitoredQuranSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
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
 * Monitored Quran Sessions Resource for Supervisor Panel
 * Shows Quran sessions for the supervisor's assigned Quran teachers.
 */
class MonitoredQuranSessionsResource extends BaseSupervisorResource
{
    protected static ?string $model = QuranSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'جلسات القرآن';

    protected static ?string $modelLabel = 'جلسة قرآن';

    protected static ?string $pluralModelLabel = 'جلسات القرآن';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة القرآن';

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
            ->columns(SessionTableColumns::getQuranSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options())
                    ->placeholder('الكل'),

                SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                    ])
                    ->placeholder('الكل'),

                SelectFilter::make('individual_circle_id')
                    ->label('الحلقة الفردية')
                    ->options(fn () => QuranIndividualCircle::query()
                        ->with(['student', 'quranTeacher'])
                        ->get()
                        ->mapWithKeys(fn ($ic) => [
                            $ic->id => trim(($ic->student?->first_name ?? '').' '.($ic->student?->last_name ?? ''))
                                .' - '.trim(($ic->quranTeacher?->first_name ?? '').' '.($ic->quranTeacher?->last_name ?? '')),
                        ])
                    )
                    ->searchable()
                    ->placeholder('الكل'),

                SelectFilter::make('circle_id')
                    ->label('الحلقة الجماعية')
                    ->options(fn () => QuranCircle::query()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('الكل'),

                SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(fn () => User::whereIn('id', static::getAssignedQuranTeacherIds())
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'معلم #'.$u->id,
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
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    MeetingActions::viewMeeting('quran'),
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
        return static::hasAssignedQuranTeachers();
    }

    public static function canCreate(): bool
    {
        return static::isSupervisor() && static::hasAssignedQuranTeachers();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['quranTeacher', 'circle', 'student', 'individualCircle']);

        $teacherIds = static::getAssignedQuranTeacherIds();

        if (! empty($teacherIds)) {
            $query->whereIn('quran_teacher_id', $teacherIds);
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
            'index' => ListMonitoredQuranSessions::route('/'),
            'create' => CreateMonitoredQuranSession::route('/create'),
            'view' => ViewMonitoredQuranSession::route('/{record}'),
            'edit' => EditMonitoredQuranSession::route('/{record}/edit'),
        ];
    }
}
