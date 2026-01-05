<?php

namespace App\Filament\Teacher\Widgets;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\QuranSession;
use Filament\Facades\Filament;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentSessionsWidget extends BaseWidget
{
    // Prevent auto-discovery - not needed on dashboard
    protected static bool $isDiscoverable = false;

    protected static ?string $heading = 'الجلسات الأخيرة';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            $query = QuranSession::query()->whereRaw('1 = 0'); // Return no results
        } else {
            $teacherProfileId = $user->quranTeacherProfile->id;
            $userId = $user->id;

            $query = QuranSession::query()
                ->where(function ($q) use ($teacherProfileId, $userId) {
                    // Include both teacher profile ID (group sessions) and user ID (individual sessions)
                    $q->where('quran_teacher_id', $teacherProfileId)
                        ->orWhere('quran_teacher_id', $userId);
                })
                ->with(['student', 'subscription', 'circle', 'individualCircle'])
                ->latest('scheduled_at');
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->weight(FontWeight::Bold)
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الجلسة')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('session_type')
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
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => SessionStatus::SCHEDULED->value,
                        'info' => SessionStatus::ONGOING->value,
                        'success' => SessionStatus::COMPLETED->value,
                        'danger' => SessionStatus::CANCELLED->value,
                        'gray' => AttendanceStatus::ABSENT->value,
                    ])
                    ->formatStateUsing(function ($state): string {
                        $statusValue = $state instanceof \App\Enums\SessionStatus ? $state->value : (string) $state;

                        return match ($statusValue) {
                            SessionStatus::UNSCHEDULED->value => 'غير مجدولة',
                            SessionStatus::SCHEDULED->value => 'مجدولة',
                            SessionStatus::READY->value => 'جاهزة للبدء',
                            SessionStatus::ONGOING->value => 'جارية',
                            SessionStatus::COMPLETED->value => 'مكتملة',
                            SessionStatus::CANCELLED->value => 'ملغية',
                            AttendanceStatus::ABSENT->value => 'غياب الطالب',
                            default => $statusValue,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn (QuranSession $record): string => route('filament.teacher.resources.quran-sessions.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record->id,
                    ])
                    ),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
