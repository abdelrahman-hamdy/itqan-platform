<?php

namespace App\Filament\Concerns;

use App\Models\BaseSubscription;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Renders the "subscription expired with leftover sessions" banner as a
 * Filament view-page subheading. Mirrors the banner shown to students and
 * mobile clients; predicate lives on BaseSubscription so all surfaces
 * agree on when to show it.
 */
trait RendersExpiredLeftoverBanner
{
    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        if (! $record instanceof BaseSubscription || ! $record->hasExpiredWithLeftoverSessions()) {
            return null;
        }

        $remaining = (int) ($record->sessions_remaining ?? 0);

        return trans_choice('subscriptions.expired_with_leftover_body', $remaining, ['count' => $remaining]);
    }
}
