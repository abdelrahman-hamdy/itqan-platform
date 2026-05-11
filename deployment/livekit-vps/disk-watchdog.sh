#!/usr/bin/env bash
# =============================================================================
# disk-watchdog.sh — LiveKit VPS disk monitor → Telegram via itqan-alert
# Deploy: scp to root@31.97.126.52:/root/disk-watchdog.sh and chmod +x.
# Cron (root): */5 * * * * /root/disk-watchdog.sh >/dev/null 2>&1
# =============================================================================
set -u

ALERT_BIN="${ALERT_BIN:-/usr/local/bin/itqan-alert}"
WARN_THRESHOLD="${WARN_THRESHOLD:-80}"
CRIT_THRESHOLD="${CRIT_THRESHOLD:-90}"
# Threshold override (e.g. THRESHOLD=1) forces the warn path during manual
# testing — useful for the W6 verification step in the deploy plan.
if [[ -n "${THRESHOLD:-}" ]]; then
    WARN_THRESHOLD="$THRESHOLD"
    CRIT_THRESHOLD="$THRESHOLD"
fi

MOUNTS=("/" "/opt")

check_mount() {
    local mount="$1"
    if [[ ! -d "$mount" ]]; then
        return 0
    fi

    # df -P forces POSIX columns; awk pulls the "Use%" field and strips '%'.
    local used
    used="$(df -P "$mount" 2>/dev/null | awk 'NR==2 {gsub("%","",$5); print $5}')"

    if [[ -z "$used" ]]; then
        return 0
    fi

    if (( used >= CRIT_THRESHOLD )); then
        "$ALERT_BIN" crit "disk-livekit" "${mount} usage ${used}% (crit threshold ${CRIT_THRESHOLD}%)"
    elif (( used >= WARN_THRESHOLD )); then
        "$ALERT_BIN" medium "disk-livekit" "${mount} usage ${used}% (warn threshold ${WARN_THRESHOLD}%)"
    fi
}

for mount in "${MOUNTS[@]}"; do
    check_mount "$mount"
done
