<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\MeetingAttendanceResource\Pages;
use App\Filament\Resources\MeetingAttendanceResource as SuperAdminMeetingAttendanceResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class MeetingAttendanceResource extends SuperAdminMeetingAttendanceResource
{
    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        $academyId = Filament::getTenant()?->id;

        return static::getModel()::query()
            ->with(['session', 'user'])
            ->where(function ($query) use ($academyId) {
                // Quran sessions (session_type: individual, group, etc.)
                $query->where(function ($q) use ($academyId) {
                    $q->whereNotIn('session_type', ['academic', 'interactive'])
                        ->whereIn('session_id', QuranSession::where('academy_id', $academyId)->select('id'));
                })
                    ->orWhere(function ($q) use ($academyId) {
                        $q->where('session_type', 'academic')
                            ->whereIn('session_id', AcademicSession::where('academy_id', $academyId)->select('id'));
                    })
                    ->orWhere(function ($q) use ($academyId) {
                        $q->where('session_type', 'interactive')
                            ->whereIn('session_id', InteractiveCourseSession::whereHas('course', fn ($q) => $q->where('academy_id', $academyId))->select('id'));
                    });
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetingAttendances::route('/'),
            'view' => Pages\ViewMeetingAttendance::route('/{record}'),
            'edit' => Pages\EditMeetingAttendance::route('/{record}/edit'),
        ];
    }
}
