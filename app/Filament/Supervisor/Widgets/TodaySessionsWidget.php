<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\SessionStatus;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TodaySessionsWidget extends BaseWidget
{
    protected static bool $isDiscoverable = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'جلسات اليوم';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->searchable(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->limit(30),

                TextColumn::make('type_label')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'قرآن' => 'success',
                        'أكاديمي' => 'warning',
                        'دورة' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('teacher_name')
                    ->label('المعلم'),

                TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('H:i')
                    ->timezone(AcademyContextService::getTimezone()),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(SessionStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof SessionStatus) {
                            return $state->label();
                        }
                        $status = SessionStatus::tryFrom($state);

                        return $status?->label() ?? (string) $state;
                    }),
            ])
            ->defaultSort('scheduled_at', 'asc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('لا توجد جلسات اليوم')
            ->emptyStateDescription('لا توجد جلسات مجدولة لهذا اليوم')
            ->emptyStateIcon('heroicon-o-calendar')
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('عرض الكل')
                    ->url(MonitoredAllSessionsResource::getUrl('index'))
                    ->icon('heroicon-o-arrow-right')
                    ->color('gray'),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        // Return empty query if no profile
        if (! $profile) {
            return QuranSession::query()->whereRaw('1 = 0');
        }

        $quranTeacherIds = $profile->getAssignedQuranTeacherIds();
        $academicTeacherIds = $profile->getAssignedAcademicTeacherIds();
        $interactiveCourseIds = $profile->getDerivedInteractiveCourseIds();

        // Get academic teacher profile IDs
        $academicProfileIds = [];
        if (! empty($academicTeacherIds)) {
            $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
                ->pluck('id')->toArray();
        }

        // Build union query for all session types using raw SQL
        $unionParts = [];
        $bindings = [];
        $academyId = AcademyContextService::getCurrentAcademyId() ?? 1;
        $today = today()->toDateString();

        // Quran sessions
        if (! empty($quranTeacherIds)) {
            $placeholders = implode(',', array_fill(0, count($quranTeacherIds), '?'));
            $unionParts[] = "
                SELECT
                    id,
                    session_code,
                    title,
                    'قرآن' as type_label,
                    scheduled_at,
                    status,
                    (SELECT name FROM users WHERE users.id = (SELECT user_id FROM quran_teacher_profiles WHERE quran_teacher_profiles.id = qs.quran_teacher_id)) as teacher_name
                FROM quran_sessions qs
                WHERE quran_teacher_id IN ({$placeholders})
                AND DATE(scheduled_at) = ?
                AND deleted_at IS NULL
                AND academy_id = ?
            ";
            $bindings = array_merge($bindings, $quranTeacherIds, [$today, $academyId]);
        }

        // Academic sessions
        if (! empty($academicProfileIds)) {
            $placeholders = implode(',', array_fill(0, count($academicProfileIds), '?'));
            $unionParts[] = "
                SELECT
                    id,
                    session_code,
                    title,
                    'أكاديمي' as type_label,
                    scheduled_at,
                    status,
                    (SELECT name FROM users WHERE users.id = (SELECT user_id FROM academic_teacher_profiles WHERE academic_teacher_profiles.id = acs.academic_teacher_id)) as teacher_name
                FROM academic_sessions acs
                WHERE academic_teacher_id IN ({$placeholders})
                AND DATE(scheduled_at) = ?
                AND deleted_at IS NULL
                AND academy_id = ?
            ";
            $bindings = array_merge($bindings, $academicProfileIds, [$today, $academyId]);
        }

        // Interactive course sessions (note: uses session_number instead of session_code)
        if (! empty($interactiveCourseIds)) {
            $placeholders = implode(',', array_fill(0, count($interactiveCourseIds), '?'));
            $unionParts[] = "
                SELECT
                    ics.id,
                    CONCAT('ICS-', ics.id) as session_code,
                    COALESCE(ics.title, CONCAT('جلسة ', ics.session_number)) as title,
                    'دورة' as type_label,
                    ics.scheduled_at,
                    ics.status,
                    (SELECT name FROM users WHERE users.id = (SELECT user_id FROM academic_teacher_profiles WHERE academic_teacher_profiles.id = (SELECT assigned_teacher_id FROM interactive_courses WHERE interactive_courses.id = ics.course_id))) as teacher_name
                FROM interactive_course_sessions ics
                INNER JOIN interactive_courses ic ON ics.course_id = ic.id AND ic.academy_id = ? AND ic.deleted_at IS NULL
                WHERE ics.course_id IN ({$placeholders})
                AND DATE(ics.scheduled_at) = ?
                AND ics.deleted_at IS NULL
            ";
            $bindings = array_merge($bindings, [$academyId], $interactiveCourseIds, [$today]);
        }

        // Return empty query if no union parts
        if (empty($unionParts)) {
            return QuranSession::query()->whereRaw('1 = 0');
        }

        // Build the complete union query as a subquery
        $unionSql = '('.implode(') UNION ALL (', $unionParts).')';

        // Use DB::table with raw subquery to avoid Eloquent's global scopes
        // We need to return an Eloquent Builder, so we use a model but override the table
        return QuranSession::query()
            ->withoutGlobalScopes()
            ->from(DB::raw("({$unionSql}) as today_sessions"))
            ->setBindings($bindings);
    }
}
