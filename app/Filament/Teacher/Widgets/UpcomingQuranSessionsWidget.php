<?php

namespace App\Filament\Teacher\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Enums\SessionStatus;
use App\Models\QuranSession;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class UpcomingQuranSessionsWidget extends BaseWidget
{
    // Prevent auto-display on dashboard - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected function getTableHeading(): string
    {
        return 'الجلسات القادمة';
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        return $table
            ->query(
                QuranSession::query()
                    ->where('quran_teacher_id', $teacherProfile?->id ?? 0)
                    ->where('scheduled_at', '>=', now())
                    ->active()
                    ->orderBy('scheduled_at', 'asc')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->placeholder('غير محدد')
                    ->searchable(),

                TextColumn::make('session_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'individual' => 'primary',
                        'group' => 'success',
                        'trial' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('D d M - H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->scheduled_at->isToday() ? 'success' : 'gray')
                    ->description(fn ($record) => $record->scheduled_at->isToday() ? 'اليوم' : ($record->scheduled_at->isTomorrow() ? 'غداً' : '')),

                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof SessionStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof SessionStatus ? $state->color() : 'gray'),
            ])
            ->deferFilters(false)
            ->recordActions([
                Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.teacher.resources.quran-sessions.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record->id,
                    ]))
                    ->color('primary'),
            ])
            ->emptyStateHeading('لا توجد جلسات قادمة')
            ->emptyStateDescription('ستظهر هنا الجلسات المجدولة القادمة')
            ->emptyStateIcon('heroicon-o-calendar')
            ->paginated(false);
    }
}
