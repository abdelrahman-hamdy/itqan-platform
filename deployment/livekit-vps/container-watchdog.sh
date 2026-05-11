#!/usr/bin/env bash
# =============================================================================
# container-watchdog.sh — Checks that required LiveKit containers are running
# and pages Telegram if any container is missing OR has restarted since the
# previous tick. Tracks RestartCount in /tmp/container-watchdog-state.
# Deploy: scp to root@31.97.126.52:/root/container-watchdog.sh and chmod +x.
# Cron (root): */2 * * * * /root/container-watchdog.sh >/dev/null 2>&1
# =============================================================================
set -u

ALERT_BIN="${ALERT_BIN:-/usr/local/bin/itqan-alert}"
STATE_FILE="${STATE_FILE:-/tmp/container-watchdog-state}"
REQUIRED=("livekit-server" "livekit-egress" "livekit-redis")

if ! command -v docker >/dev/null 2>&1; then
    "$ALERT_BIN" crit "container-livekit" "docker CLI missing — container watchdog cannot run"
    exit 0
fi

declare -A current_restart_counts=()

for name in "${REQUIRED[@]}"; do
    # docker inspect returns blank when the container doesn't exist.
    raw="$(docker inspect --format '{{.State.Status}}|{{.RestartCount}}' "$name" 2>/dev/null || true)"
    if [[ -z "$raw" ]]; then
        "$ALERT_BIN" crit "container-livekit" "Container ${name} not found on this host"
        continue
    fi

    status="${raw%%|*}"
    restart_count="${raw##*|}"

    if [[ "$status" != "running" ]]; then
        "$ALERT_BIN" crit "container-livekit" "Container ${name} status=${status}"
    fi

    current_restart_counts["$name"]="$restart_count"
done

# Persist the state so the next tick can spot a restart that happened in
# between runs even when the container is "running" again by now.
if [[ -f "$STATE_FILE" ]]; then
    while IFS='=' read -r prev_name prev_count; do
        [[ -z "$prev_name" ]] && continue
        cur="${current_restart_counts[$prev_name]:-}"
        if [[ -n "$cur" && "$cur" -gt "$prev_count" ]]; then
            "$ALERT_BIN" crit "container-livekit" "Container ${prev_name} restarted — RestartCount ${prev_count} → ${cur}"
        fi
    done < "$STATE_FILE"
fi

# Rewrite the state file atomically.
tmpfile="$(mktemp "${STATE_FILE}.XXXX")"
for name in "${!current_restart_counts[@]}"; do
    printf '%s=%s\n' "$name" "${current_restart_counts[$name]}" >> "$tmpfile"
done
mv "$tmpfile" "$STATE_FILE"
