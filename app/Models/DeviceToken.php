<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_name',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Drop any rows with this FCM token that belong to a *different* user.
     *
     * FCM tokens are device-bound, not user-bound: when a previous user signs
     * out and a new user signs in on the same device the token doesn't
     * change, so without this cleanup `sendToUser($prev)` keeps reaching the
     * device that now belongs to someone else.
     */
    public static function reassignTokenTo(string $token, int $userId): void
    {
        self::query()
            ->where('token', $token)
            ->where('user_id', '!=', $userId)
            ->delete();
    }
}
