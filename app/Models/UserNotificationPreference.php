<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'email_enabled',
        'database_enabled',
        'browser_enabled',
    ];

    protected $casts = [
        'category' => NotificationCategory::class,
        'email_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'browser_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all preferences for a user, keyed by category.
     *
     * @return array<string, self>
     */
    public static function getForUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->get()
            ->keyBy(fn ($pref) => $pref->category->value)
            ->all();
    }

    /**
     * Check if a specific channel is enabled for a user and category.
     * Returns true if no preference exists (default: all enabled).
     */
    public static function isChannelEnabled(int $userId, NotificationCategory $category, string $channel): bool
    {
        $columnName = $channel.'_enabled';

        $preference = static::where('user_id', $userId)
            ->where('category', $category->value)
            ->first();

        // Default to enabled if no preference set
        if (! $preference) {
            return true;
        }

        return (bool) ($preference->{$columnName} ?? true);
    }

    /**
     * Set or update preferences for a user and category.
     */
    public static function setForUser(
        int $userId,
        NotificationCategory $category,
        bool $emailEnabled = true,
        bool $databaseEnabled = true,
        bool $browserEnabled = true
    ): self {
        return static::updateOrCreate(
            ['user_id' => $userId, 'category' => $category->value],
            [
                'email_enabled' => $emailEnabled,
                'database_enabled' => $databaseEnabled,
                'browser_enabled' => $browserEnabled,
            ]
        );
    }
}
