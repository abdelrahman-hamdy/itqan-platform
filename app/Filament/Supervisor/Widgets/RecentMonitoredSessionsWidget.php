<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\SessionStatus;
use App\Filament\Supervisor\Resources\MonitoredSessionsResource;
use App\Models\QuranSession;
use Filament\Facades\Filament;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentMonitoredSessionsWidget extends BaseWidget
{
    // Prevent auto-discovery - not needed on main dashboard
    protected static bool $isDiscoverable = false;

    protected static ?string $heading = 'الجلسات الأخيرة';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (!$profile) {
            $query = QuranSession::query()->whereRaw('1 = 0');
        } else {
            $academyId = $profile->academy_id;
            $assignedTeachers = $profile->assigned_teachers ?? [];

            $query = QuranSession::query()
                ->where('academy_id', $academyId)
                ->with(['quranTeacher.user', 'circle', 'student'])
                ->latest('scheduled_at');

            if (!empty($assignedTeachers)) {
                $query->whereIn('quran_teacher_id', $assignedTeachers);
            }
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                TextColumn::make('quranTeacher.user.name')
                    ->label('المعلم')
                    ->searchable(),

                TextColumn::make('circle.name_ar')
                    ->label('الحلقة')
                    ->limit(20)
                    ->placeholder('فردية'),

                BadgeColumn::make('session_type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                        'warning' => 'trial',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'makeup' => 'تعويضية',
                        default => $state,
                    }),

                TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(SessionStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof SessionStatus) {
                            return $state->label();
                        }
                        $status = SessionStatus::tryFrom($state);
                        return $status?->label() ?? $state;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn (QuranSession $record): string => MonitoredSessionsResource::getUrl('view', [
                        'record' => $record->id,
                    ])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
