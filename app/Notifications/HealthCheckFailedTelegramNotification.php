<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Spatie\Health\Checks\Result;
use Spatie\Health\Notifications\CheckFailedNotification as SpatieCheckFailedNotification;

/**
 * Routes Spatie Health failures to Telegram. Registered as the
 * `health.notifications.notifications` first key so Spatie's
 * RunHealthChecksCommand instantiates this subclass (it reads
 * array_key_first of that map). Each failed Result becomes its own
 * `alert_telegram` page so we get one chat message per check, with the
 * check name as the source tag.
 */
class HealthCheckFailedTelegramNotification extends SpatieCheckFailedNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(array $results)
    {
        parent::__construct($results);

        $this->onQueue(config('telegram.queue', 'notifications'));
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable = null): array
    {
        if (! config('telegram.enabled')) {
            return [];
        }

        return [TelegramChannel::class];
    }

    public function toTelegram(mixed $notifiable = null): array
    {
        $lines = [];
        $sources = [];

        foreach ($this->results as $result) {
            if (! $result instanceof Result) {
                continue;
            }

            $checkName = method_exists($result->check, 'getLabel')
                ? $result->check->getLabel()
                : ($result->check->getName() ?? 'check');

            $sources[] = $this->slugify($checkName);

            $msg = trim((string) $result->getNotificationMessage());
            $lines[] = '• '.$checkName.': '.($msg !== '' ? $msg : 'failed');
        }

        $body = empty($lines)
            ? 'Spatie Health reported a failure with no message.'
            : implode("\n", $lines);

        $emoji = "\u{1F6A8}";
        $host = gethostname() ?: 'unknown';
        $timestamp = gmdate('Y-m-d H:i:s').' UTC';
        $sourceTag = 'health-'.(count($sources) === 1 ? $sources[0] : 'multi');

        $text = "{$emoji} [CRIT] {$sourceTag} @ {$host}\n"
            ."{$timestamp}\n\n"
            .$body;

        return [
            'text' => $text,
            'severity' => 'crit',
            'source' => $sourceTag,
            'disable_web_page_preview' => true,
        ];
    }

    private function slugify(string $name): string
    {
        return Str::slug($name) ?: 'unknown';
    }
}
