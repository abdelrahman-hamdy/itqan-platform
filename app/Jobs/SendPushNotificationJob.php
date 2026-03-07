<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = []
    ) {
        $this->onQueue('notifications');
    }

    public function handle(FcmService $fcmService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('SendPushNotificationJob: user not found', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        if ($user->deviceTokens()->count() === 0) {
            return;
        }

        $result = $fcmService->sendToUser($user, $this->title, $this->body, $this->data);

        if ($result['sent'] > 0) {
            Log::debug('SendPushNotificationJob: push sent', [
                'user_id' => $this->userId,
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'invalidated' => $result['invalidated'],
            ]);
        }
    }
}
