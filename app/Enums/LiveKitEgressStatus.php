<?php

namespace App\Enums;

/**
 * Status values reported by the LiveKit Egress API
 * (https://docs.livekit.io/home/egress/overview/#egress-status).
 *
 * These are external API values — keep the string cases in lockstep with
 * LiveKit's protocol definitions, not our internal RecordingStatus.
 */
enum LiveKitEgressStatus: string
{
    case STARTING = 'EGRESS_STARTING';
    case ACTIVE = 'EGRESS_ACTIVE';
    case ENDING = 'EGRESS_ENDING';
    case COMPLETE = 'EGRESS_COMPLETE';
    case FAILED = 'EGRESS_FAILED';
    case ABORTED = 'EGRESS_ABORTED';
    case LIMIT_REACHED = 'EGRESS_LIMIT_REACHED';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETE, self::FAILED, self::ABORTED, self::LIMIT_REACHED => true,
            default => false,
        };
    }

    public function isFailure(): bool
    {
        return match ($this) {
            self::FAILED, self::ABORTED, self::LIMIT_REACHED => true,
            default => false,
        };
    }
}
