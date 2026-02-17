<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages\EditInteractiveCourseSession;
use App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages\ListInteractiveCourseSessions;
use App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages\ViewInteractiveCourseSession;
use App\Filament\Shared\Resources\BaseInteractiveCourseSessionResource;
use App\Models\InteractiveCourse;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interactive Course Session Resource for Academy Panel
 *
 * Academy admins can manage all interactive course sessions in their academy.
 * Sessions are accessed through course relationship (no direct academy_id).
 */
class InteractiveCourseSessionResource extends BaseInteractiveCourseSessionResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'إدارة التعليم الأكاديمي';

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
     * Academy admin table actions with session control.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),

                static::makeStartSessionAction(),
                static::makeCompleteSessionAction(),
                static::makeCancelSessionAction('admin'),
                static::makeJoinMeetingAction(),
            ]),
        ];
    }

    /**
     * Bulk actions for academy admins.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
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
