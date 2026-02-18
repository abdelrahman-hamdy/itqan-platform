<?php

namespace App\Filament\Academy\Resources;

use App\Enums\SessionStatus;
use App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages\EditInteractiveCourseSession;
use App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages\ListInteractiveCourseSessions;
use App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages\ViewInteractiveCourseSession;
use App\Filament\Pages\ObserveSessionPage;
use App\Filament\Shared\Resources\BaseInteractiveCourseSessionResource;
use App\Models\InteractiveCourse;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interactive Course Session Resource for Academy Panel
 *
 * Academy admins can manage all interactive course sessions in their academy.
 * Sessions are accessed through course relationship (no direct academy_id).
 */
class InteractiveCourseSessionResource extends BaseInteractiveCourseSessionResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 6;

    /**
     * Filter sessions to courses in the current academy.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $academyId = auth()->user()->academy_id;

        return $query->whereHas('course', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }

    /**
     * Session info section with course selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        $academyId = auth()->user()->academy_id;

        return Section::make('معلومات الجلسة الأساسية')
            ->schema([
                Select::make('course_id')
                    ->label('الدورة')
                    ->options(function () use ($academyId) {
                        return InteractiveCourse::where('academy_id', $academyId)
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),

                TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('session_number')
                    ->label('رقم الجلسة')
                    ->required()
                    ->numeric()
                    ->minValue(1),
            ])->columns(2);
    }

    /**
     * Academy admin table actions with observe_meeting and soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                Action::make('observe_meeting')
                    ->label('مراقبة الجلسة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->meeting_room_name
                        && in_array(
                            $record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status),
                            [SessionStatus::READY, SessionStatus::ONGOING]
                        ))
                    ->url(fn ($record): string => ObserveSessionPage::getUrl().'?'.http_build_query([
                        'sessionId' => $record->id,
                        'sessionType' => 'interactive',
                    ]))
                    ->openUrlInNewTab(),
                static::makeStartSessionAction(),
                static::makeCompleteSessionAction(),
                static::makeCancelSessionAction('admin'),
                static::makeJoinMeetingAction(),
                DeleteAction::make()
                    ->label('حذف'),
            ]),
        ];
    }

    /**
     * Bulk actions with soft deletes.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections
    // ========================================

    /**
     * Add notes section for academy admins.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            static::getNotesFormSection(),
        ];
    }

    /**
     * Notes section.
     */
    protected static function getNotesFormSection(): Section
    {
        return Section::make('ملاحظات')
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
            ])
            ->collapsible()
            ->collapsed();
    }

    // ========================================
    // Table Filters Override
    // ========================================

    /**
     * Extended filters with course filter + parent filters.
     */
    protected static function getTableFilters(): array
    {
        $academyId = auth()->user()->academy_id;

        return [
            ...parent::getTableFilters(),

            SelectFilter::make('course_id')
                ->label('الدورة')
                ->options(function () use ($academyId) {
                    return InteractiveCourse::where('academy_id', $academyId)
                        ->pluck('title', 'id')
                        ->toArray();
                })
                ->searchable(),
        ];
    }

    /**
     * Sessions are auto-created with courses - no manual creation.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Override to bypass parent's tenant scoping.
     * InteractiveCourseSession has no direct academy_id column.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()
            ->with([
                'course',
                'course.assignedTeacher',
                'course.assignedTeacher.user',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractiveCourseSessions::route('/'),
            'view' => ViewInteractiveCourseSession::route('/{record}'),
            'edit' => EditInteractiveCourseSession::route('/{record}/edit'),
        ];
    }
}
