#!/usr/bin/env bash
# =============================================================================
# disk-watchdog-app.sh — App-server disk monitor → Telegram via itqan-alert.
# Mirror of the LiveKit VPS version, tagged source=disk-app so the inbox can
# distinguish the two hosts.
# Deploy: scp to root@72.62.92.156:/root/disk-watchdog.sh and chmod +x.
# Cron (root): */5 * * * * /root/disk-watchdog.sh >/dev/null 2>&1
# =============================================================================
set -u

ALERT_BIN="${ALERT_BIN:-/usr/local/bin/itqan-alert}"
WARN_THRESHOLD="${WARN_THRESHOLD:-80}"
CRIT_THRESHOLD="${CRIT_THRESHOLD:-90}"
if [[ -n "${THRESHOLD:-}" ]]; then
    WARN_THRESHOLD="$THRESHOLD"
    CRIT_THRESHOLD="$THRESHOLD"
fi

# /var/www holds the Laravel deploy; / is the OS root. Both matter — a
# filled-up / will brick MySQL/Redis even if /var/www has headroom.
MOUNTS=("/" "/var/www")

check_mount() {
    local mount="$1"
    if [[ ! -d "$mount" ]]; then
        return 0
    fi

    local used
    used="$(df -P "$mount" 2>/dev/null | awk 'NR==2 {gsub("%","",$5); print $5}')"

    if [[ -z "$used" ]]; then
        return 0
    fi

    if (( used >= CRIT_THRESHOLD )); then
        "$ALERT_BIN" crit "disk-app" "${mount} usage ${used}% (crit threshold ${CRIT_THRESHOLD}%)"
    elif (( used >= WARN_THRESHOLD )); then
        "$ALERT_BIN" medium "disk-app" "${mount} usage ${used}% (warn threshold ${WARN_THRESHOLD}%)"
    fi
}

for mount in "${MOUNTS[@]}"; do
    check_mount "$mount"
done
