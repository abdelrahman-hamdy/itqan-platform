<?php

namespace App\Enums;

/**
 * FCM payload `type` values exchanged between the Itqan backend and the
 * mobile CallKit handler.
 *
 * A single source of truth keeps the backend sender and the mobile receiver
 * in lockstep — renaming one side silently broke the other when we used
 * raw strings at every call site.
 *
 * The mobile mirror is at `itqan-mobile/lib/models/enums/push_payload_type.dart`.
 * Any addition here must be mirrored there (and vice versa).
 */
enum PushPayloadType: string
{
    /** Incoming alarm — the target device should start ringing via CallKit. */
    case SessionAlarm = 'session_alarm';

    /** The target answered — remaining devices should stop ringing. */
    case SessionAlarmAnswered = 'session_alarm_answered';

    /** The target declined — caller UI should reflect the refusal. */
    case SessionAlarmDeclined = 'session_alarm_declined';

    /** The caller / server cancelled the ring — dismiss CallKit. */
    case SessionAlarmCancelled = 'session_alarm_cancelled';
}
