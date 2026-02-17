<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SubscriptionAccessLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_type',
        'subscription_id',
        'user_id',
        'platform',
        'action',
        'resource_type',
        'resource_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the subscription that this log belongs to (polymorphic).
     */
    public function subscription(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who accessed the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant this log belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
