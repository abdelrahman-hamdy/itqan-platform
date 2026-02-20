<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\SessionStatus;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages\CreateMonitoredInteractiveCourseSession;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages\EditMonitoredInteractiveCourseSession;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages\ListMonitoredInteractiveCourseSessions;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages\ViewMonitoredInteractiveCourseSession;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
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
 * Monitored Interactive Course Sessions Resource for Supervisor Panel
 * Shows interactive course sessions for the supervisor's derived interactive courses.
 * Note: InteractiveCourseSession has no direct academy_id - we bypass parent query scoping.
 */
class MonitoredInteractiveCourseSessionsResource extends BaseSupervisorResource
{
    protected static ?string $model = InteractiveCourseSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'جلسات الدورات';

    protected static ?string $modelLabel = 'جلسة دورة';

    protected static ?string $pluralModelLabel = 'جلسات الدورات';

    protected static string | \UnitEnum | null $navigationGroup = 'الدورات التفاعلية';

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
            ->columns(SessionTableColumns::getInteractiveCourseSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options())
                    ->placeholder('الكل'),

                SelectFilter::make('course_id')
                    ->label('الدورة')
                    ->options(fn () => InteractiveCourse::whereIn('id', static::getDerivedInteractiveCourseIds())
                        ->pluck('title', 'id')
                        ->toArray()
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
                    MeetingActions::viewMeeting('interactive'),
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
        return static::hasDerivedInteractiveCourses();
    }

    public static function canCreate(): bool
    {
        return static::isSupervisor() && static::hasDerivedInteractiveCourses();
    }

    /**
     * InteractiveCourseSession has no direct academy_id column.
     * Bypass parent::getEloquentQuery() to avoid SQL error and scope by course IDs instead.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = InteractiveCourseSession::query()
            ->with(['course.assignedTeacher.user', 'course.subject']);

        $courseIds = static::getDerivedInteractiveCourseIds();

        if (! empty($courseIds)) {
            $query->whereIn('course_id', $courseIds);
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
            'index' => ListMonitoredInteractiveCourseSessions::route('/'),
            'create' => CreateMonitoredInteractiveCourseSession::route('/create'),
            'view' => ViewMonitoredInteractiveCourseSession::route('/{record}'),
            'edit' => EditMonitoredInteractiveCourseSession::route('/{record}/edit'),
        ];
    }
}
