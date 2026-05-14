#!/usr/bin/env bash
# =============================================================================
# cert-watchdog.sh — Watches the LiveKit conference.itqanway.com Let's Encrypt
# cert and pages Telegram via itqan-alert when it's getting close to expiry.
# Deploy: scp to root@31.97.126.52:/root/cert-watchdog.sh and chmod +x.
# Cron (root): 0 6 * * * /root/cert-watchdog.sh >/dev/null 2>&1
# =============================================================================
set -u

ALERT_BIN="${ALERT_BIN:-/usr/local/bin/itqan-alert}"
CERT_PATH="${CERT_PATH:-/opt/livekit/conference.itqanway.com/certbot/conf/live/conference.itqanway.com/cert.pem}"
WARN_DAYS="${WARN_DAYS:-14}"
CRIT_DAYS="${CRIT_DAYS:-3}"
RENEW_LOG="${RENEW_LOG:-/opt/livekit/conference.itqanway.com/certbot-renewal.log}"

if [[ ! -r "$CERT_PATH" ]]; then
    "$ALERT_BIN" medium "cert-livekit" "Cert file not readable: $CERT_PATH"
    exit 0
fi

# `openssl x509 -enddate` returns e.g. "notAfter=May 11 12:00:00 2026 GMT".
END_DATE_RAW="$(openssl x509 -enddate -noout -in "$CERT_PATH" 2>/dev/null | sed 's/notAfter=//')"
if [[ -z "$END_DATE_RAW" ]]; then
    "$ALERT_BIN" medium "cert-livekit" "Failed to parse cert expiry for $CERT_PATH"
    exit 0
fi

END_TS="$(date -d "$END_DATE_RAW" +%s 2>/dev/null || true)"
if [[ -z "$END_TS" ]]; then
    "$ALERT_BIN" medium "cert-livekit" "Could not parse end-date '$END_DATE_RAW'"
    exit 0
fi

NOW_TS="$(date +%s)"
DAYS_LEFT=$(( (END_TS - NOW_TS) / 86400 ))

if (( DAYS_LEFT <= CRIT_DAYS )); then
    "$ALERT_BIN" crit "cert-livekit" "Cert ${CERT_PATH} expires in ${DAYS_LEFT}d (crit <=${CRIT_DAYS}d)"
elif (( DAYS_LEFT <= WARN_DAYS )); then
    "$ALERT_BIN" medium "cert-livekit" "Cert ${CERT_PATH} expires in ${DAYS_LEFT}d (warn <=${WARN_DAYS}d)"
fi

# Look at the most recent renew run — if it ended with "Failed" or
# "Error", page crit so we know auto-renew is broken before the cert
# actually expires.
if [[ -r "$RENEW_LOG" ]]; then
    if tail -n 50 "$RENEW_LOG" 2>/dev/null | grep -qiE 'failed|error'; then
        "$ALERT_BIN" crit "cert-livekit" "Recent letsencrypt renew log shows failure — inspect ${RENEW_LOG}"
    fi
fi
