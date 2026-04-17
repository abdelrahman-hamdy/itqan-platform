<?php

namespace App\Filament\Widgets;

use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Super-admin-only visibility into sessions with poor audio quality.
 *
 * Reads the `telemetry_summary` JSON column populated by
 * SummarizeSessionTelemetryJob on `room_finished`. Highlights rows that match
 * any of:
 *
 *   - p50_erl_db < 5    — echo-return-loss low ⇒ speaker-to-mic coupling
 *                         (teacher isn't on headphones)
 *   - rnnoise_failures  — the WASM noise processor dropped out (each event
 *                         is an audible glitch for the other side)
 *   - reconnects > 2    — flaky signalling or network
 *
 * Not shown on any teacher/academy surface — end users never see these
 * numbers (they're internal operational signal only).
 *
 * Only QuranSession is surfaced right now because quran is the dominant
 * session type in our telemetry; academic + interactive have equivalent
 * data on their own `telemetry_summary` columns and can be added as sibling
 * widgets later if a need appears.
 */
class LowAudioQualitySessionsWidget extends BaseWidget
{
    protected static ?string $heading = 'جلسات بصدى عالٍ أو انقطاعات (قرآن)';

    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->label('الموعد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('quranTeacher.name')
                    ->label('المعلم')
                    ->placeholder(fn (QuranSession $r) => '#'.$r->quran_teacher_id),

                TextColumn::make('telemetry_summary.p50_erl_db')
                    ->label('ERL (dB)')
                    ->badge()
                    ->color(fn ($state) => $state !== null && $state < 5 ? 'danger' : 'gray')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : (string) $state),

                TextColumn::make('telemetry_summary.p50_erle_db')
                    ->label('ERLE (dB)')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : (string) $state),

                TextColumn::make('telemetry_summary.reconnects')
                    ->label('إعادة اتصال')
                    ->badge()
                    ->color(fn ($state) => (int) $state > 2 ? 'warning' : 'gray')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : (string) $state),

                TextColumn::make('telemetry_summary.rnnoise_failures')
                    ->label('أعطال RNNoise')
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'danger' : 'gray')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : (string) $state),

                TextColumn::make('telemetry_summary.max_rtt_ms')
                    ->label('أعلى RTT (ms)')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : (string) $state),
            ])
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->defaultSort('scheduled_at', 'desc')
            ->paginated(false);
    }

    private function query(): Builder
    {
        return QuranSession::query()
            ->withoutGlobalScopes()
            ->whereNotNull('telemetry_summary')
            ->whereDate('scheduled_at', '>=', now()->subDays(2))
            ->where(function (Builder $q) {
                $q->whereRaw("CAST(JSON_EXTRACT(telemetry_summary, '$.p50_erl_db') AS SIGNED) < 5")
                    ->orWhereRaw("CAST(JSON_EXTRACT(telemetry_summary, '$.rnnoise_failures') AS UNSIGNED) > 0")
                    ->orWhereRaw("CAST(JSON_EXTRACT(telemetry_summary, '$.reconnects') AS UNSIGNED) > 2");
            })
            ->with(['quranTeacher'])
            ->limit(50);
    }
}
